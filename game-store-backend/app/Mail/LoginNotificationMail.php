<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;
    public string $ip;
    public string $browser;
    public string $os;
    public string $loginTime;
    public string $loginMethod; // 'password' или 'code'

    public function __construct(
        string $userName,
        string $ip,
        string $browser,
        string $os,
        string $loginTime,
        string $loginMethod = 'password'
    ) {
        $this->userName    = $userName;
        $this->ip          = $ip;
        $this->browser     = $browser;
        $this->os          = $os;
        $this->loginTime   = $loginTime;
        $this->loginMethod = $loginMethod;
    }

    public function build()
    {
        return $this->view('emails.login-notification')
                    ->subject('Новый вход в аккаунт — GameStore');
    }
}
