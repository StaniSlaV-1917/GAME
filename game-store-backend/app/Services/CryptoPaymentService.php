<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pay/A — высокоуровневый сервис создания и закрытия крипто-платежей.
 *
 * Создание (createPending):
 *   1. Берём цену в рублях
 *   2. Конвертируем в USDT по текущему курсу
 *   3. Добавляем уникальный дробный «хвост» (6 знаков после запятой)
 *      чтобы на кошельке отличить два одновременных платежа без memo:
 *      базовая сумма 12.50 → реальная сумма 12.473821
 *   4. Проверяем uniqueness против активных pending'ов
 *   5. Записываем pending_payment с TTL 15 минут
 *
 * Закрытие (markConfirmed):
 *   • вызывается из CheckPendingPayments command после матча в blockchain
 *   • атомарно через DB::transaction чтобы исключить double-confirm
 *
 * Истечение (markExpiredStaleOnes):
 *   • переводит pending'и с expires_at в прошлом → status='expired'
 */
class CryptoPaymentService
{
    public function __construct(
        private readonly ExchangeRateService $exchange
    ) {}

    /**
     * Создать новое окно оплаты в USDT TRC-20.
     *
     * @param  User    $user
     * @param  float   $amountRub        сумма заказа в рублях
     * @param  array   $metadata         что покупает (snapshot корзины)
     * @param  ?int    $orderId          если уже создан Order
     * @return Payment
     */
    public function createPending(
        User $user,
        float $amountRub,
        array $metadata = [],
        ?int $orderId = null
    ): Payment {
        if ($amountRub <= 0) {
            throw new \InvalidArgumentException('Сумма должна быть > 0');
        }

        $rate = $this->exchange->usdtToRub();
        $baseAmount = round($amountRub / $rate, 2); // округляем до копеек USDT
        $recipient = (string) Config::get('services.crypto.tron_recipient_address');
        $ttlMin = (int) Config::get('services.crypto.payment_ttl_minutes', 15);

        if (!$recipient) {
            throw new \RuntimeException('Не настроен адрес TRON-кошелька получателя.');
        }

        // Генерируем уникальную дробную часть. Пробуем до 10 раз —
        // конфликт практически невозможен (1M вариантов, активных
        // payment'ов единицы), но защищаемся.
        $amountCrypto = null;
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $tail = random_int(100000, 999999) / 1000000; // 0.100000–0.999999
            $candidate = round($baseAmount + $tail, 6);

            // Проверяем что нет активного pending с такой же суммой
            // и тем же адресом (чтобы worker мог однозначно матчить).
            $exists = Payment::where('recipient_address', $recipient)
                ->where('amount_crypto', $candidate)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->exists();

            if (!$exists) {
                $amountCrypto = $candidate;
                break;
            }
        }

        if ($amountCrypto === null) {
            throw new \RuntimeException('Не удалось сгенерировать уникальную сумму платежа. Попробуйте ещё раз.');
        }

        $payment = Payment::create([
            'user_id'           => $user->id,
            'order_id'          => $orderId,
            'crypto_currency'   => 'USDT_TRC20',
            'amount_rub'        => $amountRub,
            'amount_crypto'     => $amountCrypto,
            'exchange_rate'     => $rate,
            'recipient_address' => $recipient,
            'status'            => 'pending',
            'expires_at'        => now()->addMinutes($ttlMin),
            'metadata'          => $metadata,
        ]);

        Log::info('[Payment] created', [
            'payment_id'    => $payment->id,
            'user_id'       => $user->id,
            'amount_rub'    => $amountRub,
            'amount_crypto' => $amountCrypto,
            'rate'          => $rate,
            'expires_at'    => $payment->expires_at->toIso8601String(),
        ]);

        return $payment;
    }

    /**
     * Атомарно отметить платёж как confirmed.
     *
     * @param  Payment  $payment
     * @param  string   $txHash         hash матчнутой транзакции
     * @param  int      $confirmations  кол-во confirmation'ов на момент матча
     * @return bool                     true если состояние изменилось
     */
    public function markConfirmed(Payment $payment, string $txHash, int $confirmations = 1): bool
    {
        return DB::transaction(function () use ($payment, $txHash, $confirmations) {
            // Перечитываем под locked (защита от race condition с другим worker'ом)
            $fresh = Payment::lockForUpdate()->find($payment->id);
            if (!$fresh || $fresh->status !== 'pending') {
                return false; // уже confirmed/expired кем-то ещё
            }

            $fresh->update([
                'status'           => 'confirmed',
                'transaction_hash' => $txHash,
                'confirmations'    => $confirmations,
                'confirmed_at'     => now(),
            ]);

            Log::warning('[Payment] CONFIRMED', [
                'payment_id'    => $fresh->id,
                'user_id'       => $fresh->user_id,
                'amount_crypto' => $fresh->amount_crypto,
                'tx_hash'       => $txHash,
            ]);

            return true;
        });
    }

    /**
     * Перевести pending'ы с истёкшим TTL → status='expired'.
     * Возвращает кол-во обновлённых записей.
     */
    public function markExpiredStaleOnes(): int
    {
        return Payment::staleAndExpired()->update(['status' => 'expired']);
    }
}
