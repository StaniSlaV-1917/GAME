<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Уведомление пользователю о снятии бана.
 */
class UserUnbannedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;

    public function __construct(string $userName)
    {
        $this->userName = $userName;
    }

    public function build()
    {
        return $this->view('emails.user-unbanned')
                    ->subject('Бан снят — GameStore');
    }
}
