<?php

namespace App\Services;

use App\Mail\GameKeyMail;
use App\Models\GameKey;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Атомарно выдаёт ключи из game_keys для всех позиций заказа
 * и отправляет email с ключами покупателю.
 *
 * Логика «нет ключей»:
 *   – Если у игры в таблице game_keys нет ни одной записи → ключ не управляется,
 *     игра считается доступной (старое поведение без ключей).
 *   – Если записи есть, но все is_issued = true → ключ недоступен → фиксируем
 *     в логах и отдельно уведомляем по email что ключ ожидается.
 */
class GameKeyService
{
    /**
     * Выдать ключи для заказа.
     * Вызывается только один раз при переходе заказа в статус 'paid'.
     * Защита от двойной выдачи — через SELECT FOR UPDATE.
     *
     * @return array{issued: array, missing: array}
     */
    public function issueForOrder(Order $order): array
    {
        $order->load(['items.game', 'user']);

        $issued  = [];
        $missing = [];

        DB::transaction(function () use ($order, &$issued, &$missing) {
            foreach ($order->items as $item) {
                $game     = $item->game;
                $quantity = max(1, (int) $item->quantity);

                for ($i = 0; $i < $quantity; $i++) {
                    // SELECT FOR UPDATE гарантирует атомарность — никакой другой
                    // процесс не заберёт тот же ключ в параллельной транзакции.
                    $key = GameKey::where('game_id', $game->id)
                        ->where('is_issued', false)
                        ->lockForUpdate()
                        ->orderBy('id')
                        ->first();

                    if ($key) {
                        $key->update([
                            'is_issued' => true,
                            'issued_to' => $order->user_id,
                            'order_id'  => $order->id,
                            'issued_at' => now(),
                        ]);

                        $issued[] = [
                            'title' => $game->title,
                            'key'   => $key->key_code,
                        ];

                        Log::info('[GameKey] issued', [
                            'key_id'   => $key->id,
                            'game_id'  => $game->id,
                            'order_id' => $order->id,
                            'user_id'  => $order->user_id,
                        ]);
                    } else {
                        // Проверяем: у игры вообще есть записи ключей или нет?
                        $hasAnyKeys = GameKey::where('game_id', $game->id)->exists();
                        if ($hasAnyKeys) {
                            // Ключи управляются, но закончились
                            $missing[] = $game->title;

                            Log::warning('[GameKey] out of stock', [
                                'game_id'  => $game->id,
                                'game'     => $game->title,
                                'order_id' => $order->id,
                            ]);
                        }
                        // Если ключей нет вообще — игра без управления ключами, пропускаем
                    }
                }
            }
        });

        // Отправляем email если есть что отправить (и ключи, и/или уведомление о нехватке)
        if (!empty($issued) || !empty($missing)) {
            try {
                $user = $order->user;
                if ($user && $user->email) {
                    Mail::to($user->email)->send(new GameKeyMail(
                        userName:     $user->fullname ?? '',
                        orderId:      $order->id,
                        issuedKeys:   $issued,
                        missingGames: $missing
                    ));
                }
            } catch (\Throwable $e) {
                Log::error('[GameKey] email send failed', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return ['issued' => $issued, 'missing' => $missing];
    }

    /**
     * Проверить, доступны ли ключи для списка игр.
     * Возвращает массив game_id, у которых ключи управляются, но закончились.
     *
     * @param int[] $gameIds
     * @return int[]  game_id с нулевым остатком
     */
    public function getOutOfStockIds(array $gameIds): array
    {
        if (empty($gameIds)) {
            return [];
        }

        // Игры у которых есть хотя бы один ключ в таблице
        $managedIds = GameKey::whereIn('game_id', $gameIds)
            ->distinct('game_id')
            ->pluck('game_id')
            ->flip(); // для быстрого hasKey()

        if ($managedIds->isEmpty()) {
            return [];
        }

        // Из управляемых — те у которых есть хотя бы один доступный ключ
        $availableIds = GameKey::whereIn('game_id', $managedIds->keys())
            ->where('is_issued', false)
            ->distinct('game_id')
            ->pluck('game_id')
            ->flip();

        // Out of stock = managed но не в available
        return $managedIds->keys()
            ->filter(fn ($id) => !$availableIds->has($id))
            ->values()
            ->all();
    }
}
