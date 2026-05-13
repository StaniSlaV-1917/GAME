<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use App\Services\GameKeyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminOrderController extends Controller
{
    public function __construct(private readonly GameKeyService $keyService) {}

    // Список всех заказов с пользователем, играми и крипто-платежами.
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
        $previousStatus = $order->status;
        $order->status  = $data['status'];
        $order->save();

        $order->load(['user', 'items.game']);

        // Выдаём ключи при переходе в статус 'paid' (если ранее не был paid/shipped/completed)
        if ($data['status'] === 'paid' && !in_array($previousStatus, ['paid', 'shipped', 'completed'])) {
            try {
                $this->keyService->issueForOrder($order);
            } catch (\Throwable $e) {
                // Не прерываем ответ, ключи можно перевыдать вручную
            }
        }

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
