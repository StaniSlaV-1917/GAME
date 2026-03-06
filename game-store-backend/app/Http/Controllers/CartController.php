<?php

namespace App\Http\Controllers;

use App\Models\Game;
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
        return $this->buildCartResponse($request);
    }

    // POST /api/cart/add
    public function add(Request $request)
    {
        $data = $request->validate([
            'game_id' => 'required|integer|exists:games,id',
        ]);

        $cart = $request->session()->get('cart', []);
        $gameId = $data['game_id'];

        $cart[$gameId] = ($cart[$gameId] ?? 0) + 1;

        $request->session()->put('cart', $cart);
        
        return $this->buildCartResponse($request);
    }

    // POST /api/cart/update
    public function update(Request $request)
    {
        $data = $request->validate([
            'game_id'  => 'required|integer|exists:games,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $request->session()->get('cart', []);
        $gameId = $data['game_id'];
        $quantity = $data['quantity'];

        if (isset($cart[$gameId])) {
            $cart[$gameId] = $quantity;
        }

        $request->session()->put('cart', $cart);

        return $this->buildCartResponse($request);
    }

    // POST /api/cart/remove
    public function remove(Request $request)
    {
        $data = $request->validate([
            'game_id' => 'required|integer',
        ]);

        $cart = $request->session()->get('cart', []);
        $gameId = $data['game_id'];

        unset($cart[$gameId]);

        $request->session()->put('cart', $cart);

        return $this->buildCartResponse($request);
    }

    private function buildCartResponse(Request $request)
    {
        $cart = $request->session()->get('cart', []);

        if (empty($cart)) {
            return response()->json(['items' => [], 'total' => 0]);
        }

        $ids = array_keys($cart);
        $games = Game::whereIn('id', $ids)->get()->keyBy('id');

        $items = [];
        $total = 0;

        foreach ($cart as $gameId => $quantity) {
            $game = $games->get($gameId);

            if ($game) {
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
        }
        
        usort($items, fn($a, $b) => $a['id'] <=> $b['id']);

        return response()->json([
            'items' => $items,
            'total' => $total,
        ]);
    }
}
