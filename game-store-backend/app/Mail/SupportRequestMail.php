<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public string  $userEmail;
    public string  $problemPath;
    public string  $body;
    public ?string $userName;

    public function __construct(
        string  $userEmail,
        string  $problemPath,
        string  $body,
        ?string $userName = null
    ) {
        $this->userEmail   = $userEmail;
        $this->problemPath = $problemPath;
        $this->body        = $body;
        $this->userName    = $userName;
    }

    public function build()
    {
        return $this->view('emails.support-request')
                    ->replyTo($this->userEmail, $this->userName ?? $this->userEmail)
                    ->subject('Обращение в поддержку: ' . $this->problemPath);
    }
}
