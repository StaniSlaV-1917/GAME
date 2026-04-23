<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\CartItem;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // POST /api/cart/sync
    // Новый метод для синхронизации корзины из localStorage
    public function sync(Request $request)
    {
        $data = $request->validate([
            'game_ids'   => 'present|array',
            'game_ids.*' => 'integer|exists:games,id',
        ]);

        $gameIds = $data['game_ids'];

        if (empty($gameIds)) {
            return response()->json(['items' => [], 'total' => 0]);
        }

        // Получаем игры и индексируем по ID для быстрого доступа
        $games = Game::whereIn('id', $gameIds)->get();

        $items = [];
        $total = 0;

        // Считаем количество каждого товара
        $quantities = array_count_values($gameIds);

        foreach ($games as $game) {
            $quantity = $quantities[$game->id] ?? 1;
            $sum = $game->price * $quantity;
            $items[] = [
                'id'       => $game->id,
                'title'    => $game->title,
                'genre'    => $game->genre,
                'platform' => $game->platform,
                'image'    => $game->image,
                'price'    => $game->price,
                'quantity' => $quantity,
                'sum'      => $sum,
            ];
            $total += $sum;
        }

        // Сортируем, чтобы порядок товаров в корзине был предсказуемым
        usort($items, fn($a, $b) => $a['id'] <=> $b['id']);

        return response()->json([
            'items' => $items,
            'total' => $total,
        ]);
    }

    // GET /api/cart
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['items' => [], 'total' => 0]);
        }

        $cartItems = CartItem::where('user_id', $user->id)
            ->with('game')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['items' => [], 'total' => 0]);
        }

        $items = [];
        $total = 0;

        foreach ($cartItems as $cartItem) {
            $game = $cartItem->game;
            if ($game) {
                $sum = $game->price * $cartItem->quantity;
                $items[] = [
                    'id'       => $game->id,
                    'title'    => $game->title,
                    'genre'    => $game->genre,
                    'platform' => $game->platform,
                    'image'    => $game->image,
                    'price'    => $game->price,
                    'quantity' => $cartItem->quantity,
                    'sum'      => $sum,
                ];
                $total += $sum;
            }
        }

        usort($items, fn($a, $b) => $a['id'] <=> $b['id']);

        return response()->json([
            'items' => $items,
            'total' => $total,
        ]);
    }

    // POST /api/cart/add
    public function add(Request $request)
    {
        $data = $request->validate([
            'game_id' => 'required|integer|exists:games,id',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $gameId = $data['game_id'];

        $cartItem = CartItem::where('user_id', $user->id)
            ->where('game_id', $gameId)
            ->first();

        if ($cartItem) {
            $cartItem->quantity += 1;
            $cartItem->save();
        } else {
            CartItem::create([
                'user_id' => $user->id,
                'game_id' => $gameId,
                'quantity' => 1,
            ]);
        }

        return $this->index($request);
    }

    // POST /api/cart/update
    public function update(Request $request)
    {
        $data = $request->validate([
            'game_id'  => 'required|integer|exists:games,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $gameId = $data['game_id'];
        $quantity = $data['quantity'];

        $cartItem = CartItem::where('user_id', $user->id)
            ->where('game_id', $gameId)
            ->first();

        if ($cartItem) {
            $cartItem->quantity = $quantity;
            $cartItem->save();
        }

        return $this->index($request);
    }

    // POST /api/cart/remove
    public function remove(Request $request)
    {
        $data = $request->validate([
            'game_id' => 'required|integer',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $gameId = $data['game_id'];

        CartItem::where('user_id', $user->id)
            ->where('game_id', $gameId)
            ->delete();

        return $this->index($request);
    }

    // POST /api/cart/clear
    public function clear(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        CartItem::where('user_id', $user->id)->delete();

        return response()->json(['items' => [], 'total' => 0]);
    }
}
