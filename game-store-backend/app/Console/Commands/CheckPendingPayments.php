<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\CryptoPaymentService;
use App\Services\TronGridService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pay/A — фоновый матчинг крипто-платежей с blockchain.
 *
 * Логика:
 *   1. expire'ним pending'ы с истёкшим TTL → status='expired'
 *   2. Берём активные pending → для каждого получаем последние
 *      TRC-20 транзакции на recipient-адрес (TronGrid)
 *   3. Ищем транзакцию с amount === pending.amount_crypto
 *   4. Найдено → markConfirmed (атомарно через DB::transaction)
 *
 * Запуск: каждые 30 секунд через Kernel.php scheduler
 *   $schedule->command('payments:check-pending')->everyThirtySeconds()
 *
 * Worker процесс на Fly: `php artisan schedule:work` (см. fly.toml).
 */
class CheckPendingPayments extends Command
{
    protected $signature = 'payments:check-pending
                            {--verbose-output : подробный лог каждого pending}';

    protected $description = 'Скан blockchain, матч транзакций к pending crypto-платежам';

    public function handle(
        TronGridService $tron,
        CryptoPaymentService $payments
    ): int {
        $verbose = $this->option('verbose-output');

        // Шаг 1 — пометить просроченные
        $expiredCount = $payments->markExpiredStaleOnes();
        if ($expiredCount > 0) {
            Log::info('[PaymentsWorker] expired stale pendings', ['count' => $expiredCount]);
            $this->line("Expired stale pendings: {$expiredCount}");
        }

        // Шаг 2 — все активные pending USDT_TRC20 (MVP только TRC-20)
        $pendings = Payment::pending()
            ->where('crypto_currency', 'USDT_TRC20')
            ->orderBy('created_at')
            ->limit(100)
            ->get();

        if ($pendings->isEmpty()) {
            if ($verbose) $this->line('Нет активных pending — пропускаю TronGrid query.');
            return self::SUCCESS;
        }

        // Шаг 3 — группируем pending'и по recipient_address (обычно один,
        // но на случай мультикошельков). Для каждого адреса ОДИН раз
        // дёргаем TronGrid и матчим все pending'и в памяти.
        $byAddress = $pendings->groupBy('recipient_address');

        $matchedCount = 0;
        $minConfirmations = (int) config('services.crypto.min_confirmations', 1);

        foreach ($byAddress as $address => $group) {
            // min_timestamp — самый старый pending для этого адреса минус
            // 5 мин буфер (вдруг транзакция чуть раньше создана). Это
            // даёт TronGrid'у узкое окно для эффективного скана.
            $oldest = $group->min('created_at');
            $minTsMs = (int) ($oldest?->subMinutes(5)?->getTimestampMs() ?? 0);

            $txs = $tron->getRecentIncomingTrc20(
                toAddress: $address,
                limit: 100,
                minTimestampMs: $minTsMs
            );

            if (empty($txs)) {
                if ($verbose) $this->line("[{$address}] TronGrid вернул 0 транзакций");
                continue;
            }

            // Матч: для каждого pending проверяем все транзакции
            foreach ($group as $pending) {
                /** @var Payment $pending */
                $expected = (float) $pending->amount_crypto;

                foreach ($txs as $tx) {
                    // tx structure (TronGrid v1):
                    //   transaction_id, value, from, to, token_info: {symbol, decimals, address}
                    if (!isset($tx['value'], $tx['transaction_id'])) continue;
                    $rawValue = (string) $tx['value'];
                    $decimals = (int) ($tx['token_info']['decimals'] ?? 6);
                    $usdt     = TronGridService::rawToUsdt($rawValue, $decimals);

                    if (!TronGridService::amountsMatch($usdt, $expected)) continue;

                    // Сумма совпала — проверяем что hash ещё не использован
                    // другим pending (защита от случайного двойного учёта;
                    // unique-индекс на transaction_hash тоже защитит, но
                    // сообщение лучше явное).
                    $hash = $tx['transaction_id'];
                    $alreadyUsed = Payment::where('transaction_hash', $hash)->exists();
                    if ($alreadyUsed) {
                        Log::info('[PaymentsWorker] tx already attached', [
                            'pending_id' => $pending->id,
                            'tx_hash'    => $hash,
                        ]);
                        continue;
                    }

                    // Подтверждаем
                    if ($payments->markConfirmed($pending, $hash, $minConfirmations)) {
                        $matchedCount++;
                        $this->info("✓ Confirmed payment #{$pending->id} → {$usdt} USDT (tx {$hash})");
                    }
                    break; // переходим к следующему pending
                }
            }
        }

        if ($matchedCount > 0) {
            Log::warning('[PaymentsWorker] matched + confirmed', ['count' => $matchedCount]);
        } elseif ($verbose) {
            $this->line('Совпадений нет. Pending: ' . $pendings->count());
        }

        return self::SUCCESS;
    }
}
