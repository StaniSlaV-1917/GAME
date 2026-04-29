<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pay/A — курс USDT/RUB.
 *
 * Источник: Binance public API (без ключа, бесплатно, 1200 req/min).
 * Endpoint: https://api.binance.com/api/v3/ticker/price?symbol=USDTRUB
 *
 * Кэшируем 60 сек чтобы не дёргать API на каждое создание платежа.
 * Курс фиксируется в Payment'е при создании — последующие колебания
 * не влияют на пользователя.
 *
 * Если Binance недоступен (502/network) — fallback на CoinGecko.
 */
class ExchangeRateService
{
    /**
     * Получить курс 1 USDT в рублях.
     * @return float  например 91.50 → 1 USDT = 91.50 ₽
     */
    public function usdtToRub(): float
    {
        return Cache::remember('exchange_rate.usdt_rub', 60, function () {
            // Primary: Binance
            try {
                $response = Http::timeout(8)
                    ->get('https://api.binance.com/api/v3/ticker/price', [
                        'symbol' => 'USDTRUB',
                    ]);

                if ($response->successful() && isset($response['price'])) {
                    $rate = (float) $response['price'];
                    if ($rate > 1) {  // sanity: 1 USDT ≠ 0
                        return $rate;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[ExchangeRate] Binance failed, trying CoinGecko', [
                    'error' => $e->getMessage(),
                ]);
            }

            // Fallback: CoinGecko
            try {
                $response = Http::timeout(8)
                    ->get('https://api.coingecko.com/api/v3/simple/price', [
                        'ids'           => 'tether',
                        'vs_currencies' => 'rub',
                    ]);

                if ($response->successful() && isset($response['tether']['rub'])) {
                    return (float) $response['tether']['rub'];
                }
            } catch (\Throwable $e) {
                Log::error('[ExchangeRate] CoinGecko also failed', [
                    'error' => $e->getMessage(),
                ]);
            }

            // Last-resort fallback — захардкоженный безопасный курс.
            // Лучше слишком высокий (юзер платит больше USDT) чем слишком
            // низкий (мы получим меньше за заказ). 100 ₽/USDT — выше
            // реального курса, но не слишком завышен.
            Log::error('[ExchangeRate] All sources failed, using fallback 100');
            return 100.0;
        });
    }

    /**
     * Конвертировать рубли в USDT по текущему курсу.
     * Возвращает float с 8 знаков после запятой (TRC-20 поддерживает 6).
     */
    public function rubToUsdt(float $rubAmount): float
    {
        $rate = $this->usdtToRub();
        if ($rate <= 0) {
            throw new \RuntimeException('Не удалось получить курс USDT/RUB');
        }
        return round($rubAmount / $rate, 6);
    }
}
