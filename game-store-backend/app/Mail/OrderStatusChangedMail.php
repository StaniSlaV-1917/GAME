<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order  $order;
    public string $userName;
    public string $statusLabel;

    // Человекочитаемые метки статусов
    private static array $labels = [
        'created'   => 'Создан',
        'paid'      => 'Оплачен',
        'shipped'   => 'Отправлен',
        'completed' => 'Выполнен',
        'cancelled' => 'Отменён',
    ];

    public function __construct(Order $order, string $userName = '')
    {
        $this->order       = $order;
        $this->userName    = $userName;
        $this->statusLabel = self::$labels[$order->status] ?? $order->status;
    }

    public function build()
    {
        return $this->view('emails.order-status-changed')
                    ->subject('Статус заказа #' . $this->order->id . ' изменён — GameStore');
    }
}
