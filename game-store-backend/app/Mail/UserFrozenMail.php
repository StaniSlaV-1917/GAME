<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Уведомление пользователю о заморозке аккаунта.
 * Заморозка мягче бана: юзер может логиниться и читать, но не
 * может создавать контент (посты/комменты/реакции).
 */
class UserFrozenMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;
    public string $reason;
    public string $frozenAt;

    public function __construct(string $userName, string $reason, string $frozenAt)
    {
        $this->userName = $userName;
        $this->reason   = $reason;
        $this->frozenAt = $frozenAt;
    }

    public function build()
    {
        return $this->view('emails.user-frozen')
                    ->subject('Аккаунт заморожен — GameStore');
    }
}
