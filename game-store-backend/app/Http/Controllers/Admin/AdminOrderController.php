<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    // Список всех заказов с пользователем и играми
    public function index(Request $request)
    {
        $orders = Order::with([
                'user',
                'items.game',
            ])
            ->orderByDesc('order_date')
            ->get();

        return response()->json($orders);
    }

    // Обновление статуса заказа
    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => 'required|in:created,paid,shipped,completed,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $order->status = $data['status'];
        $order->save();

        return response()->json([
            'message' => 'Статус заказа обновлён',
            'order'   => $order->load(['user', 'items.game']),
        ]);
    }
}
