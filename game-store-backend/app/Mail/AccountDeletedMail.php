<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Финальное уведомление об удалении аккаунта администрацией.
 * Отправляется ДО фактического delete (чтобы у нас ещё был email).
 */
class AccountDeletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;

    public function __construct(string $userName)
    {
        $this->userName = $userName;
    }

    public function build()
    {
        return $this->view('emails.account-deleted')
                    ->subject('Аккаунт удалён — GameStore');
    }
}
