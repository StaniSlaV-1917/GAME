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
     * Получить последние native TRX-транзакции на адрес.
     * Endpoint отличается от TRC-20: /v1/accounts/{addr}/transactions
     * с фильтром only_to + only_confirmed. Возвращает все типы транзакций,
     * нам нужны type='TransferContract'.
     *
     * @return array  массив TRX-transfer'ов с полями:
     *   transaction_id, raw_data.contract[0].parameter.value.{amount,to_address}
     */
    public function getRecentIncomingTrx(
        string $toAddress,
        int $limit = 50,
        ?int $minTimestampMs = null
    ): array {
        $params = [
            'only_to'        => true,
            'only_confirmed' => true,
            'limit'          => min($limit, 200),
            'order_by'       => 'block_timestamp,desc',
        ];

        if ($minTimestampMs !== null) {
            $params['min_timestamp'] = $minTimestampMs;
        }

        try {
            $response = $this->http()
                ->get(self::BASE_URL . "/v1/accounts/{$toAddress}/transactions", $params);

            if (!$response->successful()) {
                Log::warning('[TronGrid TRX] non-200 response', [
                    'status' => $response->status(),
                ]);
                return [];
            }

            $data = $response->json('data') ?? [];

            // Фильтруем только TransferContract (нативные TRX-переводы)
            return array_filter($data, function ($tx) {
                $contract = $tx['raw_data']['contract'][0] ?? null;
                return ($contract['type'] ?? null) === 'TransferContract';
            });
        } catch (\Throwable $e) {
            Log::error('[TronGrid TRX] request failed', [
                'address' => $toAddress,
                'error'   => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Извлечь raw amount + to_address из TRX-транзакции.
     * @return array{value: string, to: string}|null
     */
    public static function extractTrxTransfer(array $tx): ?array
    {
        $contract = $tx['raw_data']['contract'][0] ?? null;
        if (($contract['type'] ?? null) !== 'TransferContract') return null;
        $params = $contract['parameter']['value'] ?? null;
        if (!isset($params['amount'], $params['to_address'])) return null;

        return [
            'value' => (string) $params['amount'],
            'to'    => (string) $params['to_address'],
        ];
    }

    /**
     * Преобразование raw-amount (с N десятичных знаков) → human-friendly.
     * TronGrid возвращает value как строку без точки:
     *   "12473821" → 12.473821 (USDT/TRX, decimals=6)
     */
    public static function rawToUsdt(string $rawValue, int $decimals = 6): float
    {
        $divisor = (float) (10 ** $decimals);
        return (float) bcdiv($rawValue, (string) (10 ** $decimals), $decimals)
            ?: ((float) $rawValue / $divisor);
    }

    /** Алиас для TRX (decimals=6). */
    public static function rawToTrx(string $rawValue): float
    {
        return self::rawToUsdt($rawValue, 6);
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
