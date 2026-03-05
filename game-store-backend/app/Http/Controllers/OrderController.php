<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // POST /api/orders
    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Необходима авторизация'], 401);
        }

        $cart = $request->session()->get('cart', []);
        if (empty($cart)) {
            return response()->json(['message' => 'Корзина пуста'], 400);
        }

        $ids = array_keys($cart);
        $games = Game::whereIn('id', $ids)->get();

        if ($games->isEmpty()) {
            return response()->json(['message' => 'Игры не найдены'], 400);
        }

        $total = 0;
        $itemsData = [];

        foreach ($games as $g) {
            $quantity = $cart[$g->id] ?? 0;
            if ($quantity <= 0) {
                continue;
            }
            $sum = $g->price * $quantity;
            $total += $sum;

            $itemsData[] = [
                'game_id'  => $g->id,
                'quantity' => $quantity,
                'price'    => $g->price,
            ];
        }

        if ($total <= 0) {
            return response()->json(['message' => 'Корзина пуста'], 400);
        }

        DB::beginTransaction();

        try {
            $order = Order::create([
                'user_id' => $user->id,
                'status'  => 'created',
                'total'   => $total,
                'order_date' => now(),
            ]);

            foreach ($itemsData as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'game_id'  => $item['game_id'],
                    'quantity' => $item['quantity'],
                    'price'    => $item['price'],
                ]);
            }

            $request->session()->forget('cart');

            DB::commit();

            return response()->json([
                'message' => 'Заказ успешно оформлен',
                'order_id' => $order->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Ошибка при оформлении заказа',
            ], 500);
        }
    }

    // GET /api/orders
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Необходима авторизация'], 401);
        }

        $orders = Order::where('user_id', $user->id)
            ->orderByDesc('order_date')
            ->with(['items.game'])
            ->get();

        return response()->json($orders);
    }
}
