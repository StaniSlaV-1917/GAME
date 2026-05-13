<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GameKeyMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string $userName     Имя получателя
     * @param int    $orderId      Номер заказа
     * @param array  $issuedKeys   [ ['title' => 'Игра', 'key' => 'XXXX-XXXX'], ... ]
     * @param array  $missingGames [ 'Игра без ключа', ... ] — игры, у которых не нашлось ключей
     */
    public function __construct(
        public readonly string $userName,
        public readonly int    $orderId,
        public readonly array  $issuedKeys,
        public readonly array  $missingGames = []
    ) {}

    public function build()
    {
        return $this->view('emails.game-key')
                    ->subject('Ключи к играм — заказ #' . $this->orderId . ' — GameStore');
    }
}
