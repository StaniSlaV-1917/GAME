<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pay/A — клиент TronGrid API.
 *
 * Используется для:
 *   • получения списка TRC-20 транзакций на recipient-адрес
 *   • проверки confirmations (block height)
 *
 * USDT TRC-20 контракт: TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t
 *
 * Без API key: ~5 req/sec rate limit.
 * С API key (TRONGRID_API_KEY): 100 req/sec, бесплатно.
 *
 * Документация: https://developers.tron.network/reference/get-trc20-transaction-info-by-account-address
 */
class TronGridService
{
    public const USDT_TRC20_CONTRACT = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
    private const BASE_URL = 'https://api.trongrid.io';

    private readonly ?string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        // Если ключ передан в конструктор (для тестов) — используем его,
        // иначе берём из config (services.crypto.trongrid_api_key).
        $this->apiKey = $apiKey ?? config('services.crypto.trongrid_api_key');
    }

    /**
     * Получить последние TRC-20 транзакции на указанный адрес.
     *
     * @param  string  $toAddress  TRC-20 адрес получателя (наш кошелёк)
     * @param  string  $contract   contract address токена (USDT по умолчанию)
     * @param  int     $limit      макс 200 (ограничение API)
     * @param  ?int    $minTimestampMs  только транзакции после этого момента (ms)
     * @return array   массив транзакций в raw-формате TronGrid
     */
    public function getRecentIncomingTrc20(
        string $toAddress,
        string $contract = self::USDT_TRC20_CONTRACT,
        int $limit = 50,
        ?int $minTimestampMs = null
    ): array {
        $params = [
            'only_to'                => true,
            'only_confirmed'         => true,
            'limit'                  => min($limit, 200),
            'contract_address'       => $contract,
            'order_by'               => 'block_timestamp,desc',
        ];

        if ($minTimestampMs !== null) {
            $params['min_timestamp'] = $minTimestampMs;
        }

        try {
            $response = $this->http()
                ->get(self::BASE_URL . "/v1/accounts/{$toAddress}/transactions/trc20", $params);

            if (!$response->successful()) {
                Log::warning('[TronGrid] non-200 response', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return [];
            }

            return $response->json('data') ?? [];
        } catch (\Throwable $e) {
            Log::error('[TronGrid] request failed', [
                'address' => $toAddress,
                'error'   => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Преобразование raw-amount (с 6 десятичных знаков) → human-friendly USDT.
     * TronGrid возвращает value как строку без точки: "12473821" = 12.473821 USDT.
     */
    public static function rawToUsdt(string $rawValue, int $decimals = 6): float
    {
        $divisor = (float) (10 ** $decimals);
        return (float) bcdiv($rawValue, (string) (10 ** $decimals), $decimals)
            ?: ((float) $rawValue / $divisor);
    }

    /**
     * Сравнение сумм с допуском на decimal-точность.
     * Возвращает true если |a - b| < epsilon.
     */
    public static function amountsMatch(float $a, float $b, float $epsilon = 0.0000005): bool
    {
        return abs($a - $b) < $epsilon;
    }

    /**
     * HTTP-клиент с TRON-PRO-API-KEY header (если ключ задан).
     */
    private function http()
    {
        $client = Http::timeout(15)->acceptJson();
        if ($this->apiKey) {
            $client = $client->withHeaders(['TRON-PRO-API-KEY' => $this->apiKey]);
        }
        return $client;
    }
}
