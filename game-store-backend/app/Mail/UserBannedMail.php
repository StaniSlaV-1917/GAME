<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Уведомление пользователю о бане его аккаунта администрацией.
 * Отправляется из AdminUserController::ban().
 */
class UserBannedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;
    public string $reason;
    public string $bannedAt;

    public function __construct(string $userName, string $reason, string $bannedAt)
    {
        $this->userName = $userName;
        $this->reason   = $reason;
        $this->bannedAt = $bannedAt;
    }

    public function build()
    {
        return $this->view('emails.user-banned')
                    ->subject('Аккаунт заблокирован — GameStore');
    }
}
