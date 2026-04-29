<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pay/A — высокоуровневый сервис создания и закрытия крипто-платежей.
 *
 * Поддерживает 3 валюты:
 *   • USDT_TRC20 — Tron, address начинается с T..., 6 decimals
 *   • TRX        — Tron, тот же address что USDT_TRC20, 6 decimals
 *   • USDT_BEP20 — BSC, отдельный 0x... address, 18 decimals (raw),
 *                  но мы оперируем 6 знаками для unique-tail (как у других)
 *
 * Уникальный дробный «хвост» одинаковый для всех валют (random
 * 0.100000–0.999999), различается только base amount (в каждой валюте свой)
 * и recipient address (Tron для TRC20/TRX, BSC для BEP20).
 *
 * Список доступных валют формируется на основе configured адресов:
 * если config('services.crypto.bsc_recipient_address') пустой — BEP20
 * не доступен.
 */
class CryptoPaymentService
{
    public function __construct(
        private readonly ExchangeRateService $exchange
    ) {}

    /**
     * Какие валюты сейчас включены (на основе настроенных адресов).
     * Используется во фронте для генерации селектора.
     *
     * @return array<string,array{label:string,network:string,address:string,decimals:int}>
     */
    public function availableCurrencies(): array
    {
        $tron = (string) Config::get('services.crypto.tron_recipient_address');
        $bsc  = (string) Config::get('services.crypto.bsc_recipient_address');

        $list = [];
        if ($tron !== '') {
            $list['USDT_TRC20'] = [
                'label'    => 'USDT (TRC-20)',
                'network'  => 'Tron (TRC-20)',
                'address'  => $tron,
                'decimals' => 6,
            ];
            $list['TRX'] = [
                'label'    => 'TRX',
                'network'  => 'Tron',
                'address'  => $tron,
                'decimals' => 6,
            ];
        }
        if ($bsc !== '') {
            $list['USDT_BEP20'] = [
                'label'    => 'USDT (BEP-20)',
                'network'  => 'BSC (BEP-20)',
                'address'  => $bsc,
                'decimals' => 6,  // мы оперируем 6 знаками для уникальности,
                                   // фактическое value на BSC хранится в 18, но
                                   // юзеру показываем округление до 6 (для
                                   // унификации UX)
            ];
        }

        return $list;
    }

    /**
     * Создать новое окно оплаты.
     *
     * @param  string  $currency  USDT_TRC20 | TRX | USDT_BEP20
     */
    public function createPending(
        User $user,
        float $amountRub,
        string $currency = 'USDT_TRC20',
        array $metadata = [],
        ?int $orderId = null
    ): Payment {
        if ($amountRub <= 0) {
            throw new \InvalidArgumentException('Сумма должна быть > 0');
        }

        $available = $this->availableCurrencies();
        if (!isset($available[$currency])) {
            throw new \InvalidArgumentException(
                "Валюта {$currency} не поддерживается или адрес не настроен."
            );
        }

        // Конвертация в нужную крипту по текущему курсу
        [$rate, $baseAmount] = match ($currency) {
            'USDT_TRC20', 'USDT_BEP20' => [
                $this->exchange->usdtToRub(),
                round($amountRub / $this->exchange->usdtToRub(), 2),
            ],
            'TRX' => [
                $this->exchange->trxToRub(),
                round($amountRub / $this->exchange->trxToRub(), 2),
            ],
            default => throw new \InvalidArgumentException("Unknown currency {$currency}"),
        };

        $recipient = $available[$currency]['address'];
        $ttlMin = (int) Config::get('services.crypto.payment_ttl_minutes', 15);

        // Генерируем уникальную дробную часть. Проверяем uniqueness против
        // активных pending'ов в той же валюте на том же адресе.
        $amountCrypto = null;
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $tail = random_int(100000, 999999) / 1000000; // 0.100000–0.999999
            $candidate = round($baseAmount + $tail, 6);

            $exists = Payment::where('crypto_currency', $currency)
                ->where('recipient_address', $recipient)
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
            throw new \RuntimeException('Не удалось сгенерировать уникальную сумму. Попробуйте ещё раз.');
        }

        $payment = Payment::create([
            'user_id'           => $user->id,
            'order_id'          => $orderId,
            'crypto_currency'   => $currency,
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
            'currency'      => $currency,
            'amount_rub'    => $amountRub,
            'amount_crypto' => $amountCrypto,
            'rate'          => $rate,
        ]);

        return $payment;
    }

    /**
     * Атомарно отметить платёж как confirmed.
     */
    public function markConfirmed(Payment $payment, string $txHash, int $confirmations = 1): bool
    {
        return DB::transaction(function () use ($payment, $txHash, $confirmations) {
            $fresh = Payment::lockForUpdate()->find($payment->id);
            if (!$fresh || $fresh->status !== 'pending') {
                return false;
            }

            $fresh->update([
                'status'           => 'confirmed',
                'transaction_hash' => $txHash,
                'confirmations'    => $confirmations,
                'confirmed_at'     => now(),
            ]);

            // Если payment связан с Order — каскадно обновляем его статус.
            // status='created' (только что создан вместе с payment) →
            // 'paid' (deal sealed, юзер заплатил).
            // Не трогаем shipped/completed/cancelled — те выставляются
            // вручную админом через admin/orders.
            if ($fresh->order_id) {
                $order = Order::find($fresh->order_id);
                if ($order && $order->status === 'created') {
                    $order->update(['status' => 'paid']);
                }
            }

            Log::warning('[Payment] CONFIRMED', [
                'payment_id'    => $fresh->id,
                'order_id'      => $fresh->order_id,
                'user_id'       => $fresh->user_id,
                'currency'      => $fresh->crypto_currency,
                'amount_crypto' => $fresh->amount_crypto,
                'tx_hash'       => $txHash,
            ]);

            return true;
        });
    }

    /**
     * Перевести pending'ы с истёкшим TTL → status='expired'.
     */
    public function markExpiredStaleOnes(): int
    {
        return Payment::staleAndExpired()->update(['status' => 'expired']);
    }
}
