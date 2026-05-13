<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameKey;
use Illuminate\Http\Request;

class AdminGameKeysController extends Controller
{
    /**
     * GET /admin/games/{game}/keys
     * Список всех ключей игры.
     */
    public function index(Game $game)
    {
        $keys = $game->keys()
            ->with(['issuedToUser:id,fullname,email', 'order:id,order_date,status'])
            ->orderBy('is_issued')
            ->orderBy('id')
            ->get()
            ->map(fn (GameKey $key) => [
                'id'         => $key->id,
                'key_code'   => $key->key_code,
                'is_issued'  => $key->is_issued,
                'issued_at'  => $key->issued_at?->toIso8601String(),
                'issued_to'  => $key->issuedToUser ? [
                    'id'       => $key->issuedToUser->id,
                    'fullname' => $key->issuedToUser->fullname,
                    'email'    => $key->issuedToUser->email,
                ] : null,
                'order_id'   => $key->order_id,
                'created_at' => $key->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'total'     => $keys->count(),
            'available' => $keys->where('is_issued', false)->count(),
            'issued'    => $keys->where('is_issued', true)->count(),
            'keys'      => $keys->values(),
        ]);
    }

    /**
     * POST /admin/games/{game}/keys
     * Добавить один или несколько ключей (по одному на строку или через запятую).
     * Body: { keys: "KEY1\nKEY2\nKEY3" }  или  { keys: ["KEY1","KEY2"] }
     */
    public function store(Request $request, Game $game)
    {
        $request->validate([
            'keys' => 'required',
        ]);

        $raw = $request->input('keys');

        // Принимаем строку (ключи через \n, \r\n или запятую) или массив
        if (is_array($raw)) {
            $codes = $raw;
        } else {
            // Разбиваем по переносам строк и запятым
            $codes = preg_split('/[\r\n,]+/', (string) $raw);
        }

        $codes = collect($codes)
            ->map(fn ($c) => trim($c))
            ->filter(fn ($c) => $c !== '')
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return response()->json(['message' => 'Ключи не могут быть пустыми'], 422);
        }

        // Исключаем уже существующие для этой игры
        $existing = GameKey::where('game_id', $game->id)
            ->whereIn('key_code', $codes)
            ->pluck('key_code')
            ->flip();

        $added    = 0;
        $skipped  = [];
        $now      = now();

        foreach ($codes as $code) {
            if ($existing->has($code)) {
                $skipped[] = $code;
                continue;
            }
            GameKey::create([
                'game_id'  => $game->id,
                'key_code' => $code,
            ]);
            $added++;
        }

        return response()->json([
            'message' => "Добавлено {$added} ключей",
            'added'   => $added,
            'skipped' => $skipped,
        ], 201);
    }

    /**
     * DELETE /admin/games/{game}/keys/{key}
     * Удалить ключ (только если не выдан).
     */
    public function destroy(Game $game, GameKey $key)
    {
        if ($key->game_id !== $game->id) {
            return response()->json(['message' => 'Ключ не принадлежит этой игре'], 403);
        }

        if ($key->is_issued) {
            return response()->json(['message' => 'Нельзя удалить уже выданный ключ'], 422);
        }

        $key->delete();

        return response()->json(null, 204);
    }
}
