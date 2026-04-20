<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    public int    $code;
    public string $userName;
    public string $newEmail;

    public function __construct(int $code, string $userName = '', string $newEmail = '')
    {
        $this->code     = $code;
        $this->userName = $userName;
        $this->newEmail = $newEmail;
    }

    public function build()
    {
        return $this->view('emails.email-change')
                    ->subject('Подтверждение смены email — GameStore');
    }
}
