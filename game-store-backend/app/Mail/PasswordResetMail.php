<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public int    $code;
    public string $userName;

    public function __construct(int $code, string $userName = '')
    {
        $this->code     = $code;
        $this->userName = $userName;
    }

    public function build()
    {
        return $this->view('emails.password-reset')
                    ->subject('Сброс пароля — GameStore');
    }
}
