<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order  $order;
    public string $userName;

    public function __construct(Order $order, string $userName = '')
    {
        $this->order    = $order;
        $this->userName = $userName;
    }

    public function build()
    {
        return $this->view('emails.order-created')
                    ->subject('Заказ #' . $this->order->id . ' оформлен — GameStore');
    }
}
