<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\BscScanService;
use App\Services\CryptoPaymentService;
use App\Services\TronGridService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pay/A — фоновый матчинг крипто-платежей с blockchain.
 *
 * Поддерживает 3 валюты:
 *   • USDT_TRC20 — TronGrid /v1/.../transactions/trc20 (USDT contract filter)
 *   • TRX        — TronGrid /v1/.../transactions с TransferContract фильтром
 *   • USDT_BEP20 — BscScan /api?action=tokentx (USDT BEP-20 contract)
 *
 * Группирует pending'и по (currency, recipient_address) — для каждой
 * группы один API call, потом матчим в памяти. Это экономит rate limit
 * когда много активных pending'ов.
 */
class CheckPendingPayments extends Command
{
    protected $signature = 'payments:check-pending
                            {--verbose-output : подробный лог}';

    protected $description = 'Скан blockchain (Tron + BSC), матч транзакций к pending crypto-платежам';

    public function handle(
        TronGridService $tron,
        BscScanService $bsc,
        CryptoPaymentService $payments
    ): int {
        $verbose = $this->option('verbose-output');

        // 1. Пометить просроченные
        $expiredCount = $payments->markExpiredStaleOnes();
        if ($expiredCount > 0) {
            Log::info('[PaymentsWorker] expired stale pendings', ['count' => $expiredCount]);
        }

        // 2. Все активные pending'и
        $pendings = Payment::pending()
            ->orderBy('created_at')
            ->limit(100)
            ->get();

        if ($pendings->isEmpty()) {
            if ($verbose) $this->line('Нет активных pending.');
            return self::SUCCESS;
        }

        $minConfirmations = (int) config('services.crypto.min_confirmations', 1);

        // 3. Группируем по (currency, address) — один API-call на группу
        $byCurrencyAddr = $pendings->groupBy(fn ($p) => $p->crypto_currency . ':' . $p->recipient_address);

        $matchedTotal = 0;

        foreach ($byCurrencyAddr as $key => $group) {
            [$currency, $address] = explode(':', $key, 2);
            $oldest = $group->min('created_at');
            $minTsMs = (int) ($oldest?->subMinutes(5)?->getTimestampMs() ?? 0);

            $matched = match ($currency) {
                'USDT_TRC20' => $this->matchTrc20($tron, $payments, $address, $group, $minTsMs, $minConfirmations, $verbose),
                'TRX'        => $this->matchTrx($tron, $payments, $address, $group, $minTsMs, $minConfirmations, $verbose),
                'USDT_BEP20' => $this->matchBep20($bsc, $payments, $address, $group, $minConfirmations, $verbose),
                default      => 0,
            };

            $matchedTotal += $matched;
        }

        if ($matchedTotal > 0) {
            Log::warning('[PaymentsWorker] matched + confirmed', ['count' => $matchedTotal]);
        } elseif ($verbose) {
            $this->line('Совпадений нет. Pending: ' . $pendings->count());
        }

        return self::SUCCESS;
    }

    /** Матчинг USDT TRC-20 транзакций. */
    private function matchTrc20(
        TronGridService $tron,
        CryptoPaymentService $payments,
        string $address,
        $group,
        int $minTsMs,
        int $minConfirmations,
        bool $verbose
    ): int {
        $txs = $tron->getRecentIncomingTrc20(
            toAddress: $address,
            limit: 100,
            minTimestampMs: $minTsMs
        );
        if (empty($txs)) {
            if ($verbose) $this->line("[TRC20 {$address}] 0 транзакций");
            return 0;
        }

        $matched = 0;
        foreach ($group as $pending) {
            $expected = (float) $pending->amount_crypto;
            foreach ($txs as $tx) {
                if (!isset($tx['value'], $tx['transaction_id'])) continue;
                $usdt = TronGridService::rawToUsdt(
                    (string) $tx['value'],
                    (int) ($tx['token_info']['decimals'] ?? 6)
                );
                if (!TronGridService::amountsMatch($usdt, $expected)) continue;

                $hash = $tx['transaction_id'];
                if (Payment::where('transaction_hash', $hash)->exists()) continue;

                if ($payments->markConfirmed($pending, $hash, $minConfirmations)) {
                    $matched++;
                    $this->info("✓ TRC-20 #{$pending->id} → {$usdt} USDT (tx {$hash})");
                }
                break;
            }
        }
        return $matched;
    }

    /** Матчинг native TRX-переводов. */
    private function matchTrx(
        TronGridService $tron,
        CryptoPaymentService $payments,
        string $address,
        $group,
        int $minTsMs,
        int $minConfirmations,
        bool $verbose
    ): int {
        $txs = $tron->getRecentIncomingTrx(
            toAddress: $address,
            limit: 100,
            minTimestampMs: $minTsMs
        );
        if (empty($txs)) {
            if ($verbose) $this->line("[TRX {$address}] 0 транзакций");
            return 0;
        }

        $matched = 0;
        foreach ($group as $pending) {
            $expected = (float) $pending->amount_crypto;
            foreach ($txs as $tx) {
                $transfer = TronGridService::extractTrxTransfer($tx);
                if (!$transfer) continue;
                $trx = TronGridService::rawToTrx($transfer['value']);
                if (!TronGridService::amountsMatch($trx, $expected)) continue;

                $hash = $tx['txID'] ?? ($tx['transaction_id'] ?? null);
                if (!$hash) continue;
                if (Payment::where('transaction_hash', $hash)->exists()) continue;

                if ($payments->markConfirmed($pending, $hash, $minConfirmations)) {
                    $matched++;
                    $this->info("✓ TRX #{$pending->id} → {$trx} TRX (tx {$hash})");
                }
                break;
            }
        }
        return $matched;
    }

    /** Матчинг USDT BEP-20 (BSC). */
    private function matchBep20(
        BscScanService $bsc,
        CryptoPaymentService $payments,
        string $address,
        $group,
        int $minConfirmations,
        bool $verbose
    ): int {
        $txs = $bsc->getRecentIncomingBep20($address);
        if (empty($txs)) {
            if ($verbose) $this->line("[BEP20 {$address}] 0 транзакций");
            return 0;
        }

        $matched = 0;
        foreach ($group as $pending) {
            $expected = (float) $pending->amount_crypto;
            foreach ($txs as $tx) {
                if (!isset($tx['value'], $tx['hash'])) continue;
                // USDT BEP-20 на BSC имеет 18 decimals
                $usdt = BscScanService::rawToUsdt(
                    (string) $tx['value'],
                    (int) ($tx['tokenDecimal'] ?? 18)
                );
                if (!TronGridService::amountsMatch($usdt, $expected)) continue;

                $hash = $tx['hash'];
                if (Payment::where('transaction_hash', $hash)->exists()) continue;

                if ($payments->markConfirmed($pending, $hash, $minConfirmations)) {
                    $matched++;
                    $this->info("✓ BEP-20 #{$pending->id} → {$usdt} USDT (tx {$hash})");
                }
                break;
            }
        }
        return $matched;
    }
}
