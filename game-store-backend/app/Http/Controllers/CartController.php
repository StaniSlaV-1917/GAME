<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // GET /api/cart
    public function index(Request $request)
    {
        // Просто возвращаем актуальное состояние корзины
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

        // Увеличиваем количество товара или добавляем новый
        $cart[$gameId] = ($cart[$gameId] ?? 0) + 1;

        $request->session()->put('cart', $cart);
        
        // Возвращаем обновленное состояние корзины
        return $this->buildCartResponse($request);
    }

    // POST /api/cart/update
    public function update(Request $request)
    {
        $data = $request->validate([
            'game_id'  => 'required|integer|exists:games,id',
            'quantity' => 'required|integer|min:1', // Количество должно быть 1 или больше
        ]);

        $cart = $request->session()->get('cart', []);
        $gameId = $data['game_id'];
        $quantity = $data['quantity'];

        // Устанавливаем точное количество для товара
        if (isset($cart[$gameId])) {
            $cart[$gameId] = $quantity;
        }

        $request->session()->put('cart', $cart);

        // Возвращаем обновленное состояние корзины, которое ждет фронтенд
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

        // Возвращаем обновленное состояние корзины
        return $this->buildCartResponse($request);
    }

    /**
     * Приватный метод для сборки ответа с содержимым корзины.
     * Используется всеми методами контроллера, чтобы избежать дублирования кода.
     */
    private function buildCartResponse(Request $request)
    {
        $cart = $request->session()->get('cart', []);

        if (empty($cart)) {
            return response()->json(['items' => [], 'total' => 0]);
        }

        $ids = array_keys($cart);
        // Получаем игры и индексируем по ID для быстрого доступа
        $games = Game::whereIn('id', $ids)->get()->keyBy('id');

        $items = [];
        $total = 0;

        foreach ($cart as $gameId => $quantity) {
            $game = $games->get($gameId);

            // Добавляем товар, только если он все еще существует в БД
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
        
        // Сортируем, чтобы порядок товаров в корзине был предсказуемым
        usort($items, fn($a, $b) => $a['id'] <=> $b['id']);

        return response()->json([
            'items' => $items,
            'total' => $total,
        ]);
    }
}
