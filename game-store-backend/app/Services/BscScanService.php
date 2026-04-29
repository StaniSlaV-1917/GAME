<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pay/A.2 — клиент BscScan API для USDT BEP-20 на BSC blockchain.
 *
 * USDT BEP-20 contract: 0x55d398326f99059fF775485246999027B3197955 (18 decimals).
 *
 * Без API key: 5 req/sec, лимит 100k req/day.
 * С API key (BSCSCAN_API_KEY): тот же rate, но больше квоты.
 *
 * Если адрес BSC не задан в config — сервис не используется (worker
 * пропускает USDT_BEP20 pending'и).
 *
 * Документация: https://docs.bscscan.com/api-endpoints/accounts#get-a-list-of-bep-20-token-transfer-events-by-address
 */
class BscScanService
{
    public const USDT_BEP20_CONTRACT = '0x55d398326f99059fF775485246999027B3197955';
    private const BASE_URL = 'https://api.bscscan.com/api';

    private readonly ?string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.crypto.bscscan_api_key');
    }

    /**
     * Получить последние BEP-20 транзакции (USDT) на указанный адрес.
     *
     * @return array  массив с полями:
     *   hash, from, to, value (raw, 18 decimals для USDT BEP-20),
     *   contractAddress, timeStamp, blockNumber, confirmations
     */
    public function getRecentIncomingBep20(
        string $toAddress,
        string $contract = self::USDT_BEP20_CONTRACT,
        int $startblock = 0
    ): array {
        $params = [
            'module'          => 'account',
            'action'          => 'tokentx',
            'address'         => $toAddress,
            'contractaddress' => $contract,
            'startblock'      => $startblock,
            'endblock'        => 99999999,
            'sort'            => 'desc',
            'page'            => 1,
            'offset'          => 50,
        ];

        if ($this->apiKey) {
            $params['apikey'] = $this->apiKey;
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get(self::BASE_URL, $params);

            if (!$response->successful()) {
                Log::warning('[BscScan] non-200', ['status' => $response->status()]);
                return [];
            }

            $body = $response->json();
            // BscScan возвращает {status:"1", message:"OK", result:[...]}
            // status "0" значит "no transactions found" или ошибка
            if (($body['status'] ?? null) !== '1') {
                if (($body['message'] ?? '') !== 'No transactions found') {
                    Log::warning('[BscScan] error response', ['body' => $body]);
                }
                return [];
            }

            // Фильтруем только входящие (на наш адрес)
            $items = $body['result'] ?? [];
            $lowerAddress = strtolower($toAddress);
            return array_filter($items, function ($tx) use ($lowerAddress) {
                return strtolower($tx['to'] ?? '') === $lowerAddress;
            });
        } catch (\Throwable $e) {
            Log::error('[BscScan] request failed', [
                'address' => $toAddress,
                'error'   => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * USDT BEP-20 имеет 18 decimals (нестандартно — обычно USDT = 6).
     * Преобразование raw → human-friendly.
     */
    public static function rawToUsdt(string $rawValue, int $decimals = 18): float
    {
        // Большие числа: используем bcdiv для точности
        $divisor = bcpow('10', (string) $decimals);
        return (float) bcdiv($rawValue, $divisor, 8);
    }
}
