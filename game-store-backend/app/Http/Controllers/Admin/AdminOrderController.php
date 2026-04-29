<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminOrderController extends Controller
{
    // Список всех заказов с пользователем, играми и крипто-платежами.
    // Pay/A.3 — добавлен latestPayment чтобы в admin-таблице можно было
    // показывать инфо о платеже (валюта, сумма крипты, статус, tx hash).
    // Старые заказы (созданные до Pay/A) не имеют payments → null.
    public function index(Request $request)
    {
        $orders = Order::with([
                'user',
                'items.game',
                'latestPayment',
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

        $order->load(['user', 'items.game']);

        // Отправляем email-уведомление об изменении статуса (если включено у пользователя)
        try {
            if ($order->user && $order->user->email && $order->user->notify_order_status !== false) {
                Mail::to($order->user->email)->send(
                    new OrderStatusChangedMail($order, $order->user->fullname ?? '')
                );
            }
        } catch (\Throwable $e) {
            // Не прерываем ответ, если письмо не отправилось
        }

        return response()->json([
            'message' => 'Статус заказа обновлён',
            'order'   => $order,
        ]);
    }
}
