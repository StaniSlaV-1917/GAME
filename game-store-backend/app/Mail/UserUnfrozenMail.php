<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Уведомление пользователю о размораживании аккаунта.
 */
class UserUnfrozenMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;

    public function __construct(string $userName)
    {
        $this->userName = $userName;
    }

    public function build()
    {
        return $this->view('emails.user-unfrozen')
                    ->subject('Заморозка снята — GameStore');
    }
}
