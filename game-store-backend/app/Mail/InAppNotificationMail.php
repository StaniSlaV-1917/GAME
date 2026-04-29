<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Phase 4 / Batch B — generic email-дубль in-app нотификации.
 *
 * Один Mailable + один blade-шаблон обслуживает все 4 типа событий
 * (comment / reply / reaction / follower) — отличаются заголовком,
 * intro-строкой, превью и CTA-ссылкой. Это сокращает дублирование
 * в 4 раза и даёт единый дизайн.
 *
 * Использование (из Notification класса toMail):
 *   return (new InAppNotificationMail([
 *     'subject'     => 'Новый комментарий — GameStore',
 *     'title'       => '💬 Новый комментарий',
 *     'recipient'   => $notifiable->fullname,
 *     'intro'       => 'Vasya прокомментировал ваш пост «Заголовок»',
 *     'preview'     => 'Текст коммента до 280 символов...',
 *     'parent_preview' => 'Текст родительского коммента (только для reply)',
 *     'cta_url'     => 'https://frontend/post/123',
 *     'cta_label'   => 'К посту →',
 *     'pref_label'  => 'Уведомления о комментариях',  // что отключить чтобы перестало приходить
 *   ]))->subject(...);
 */
class InAppNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function build()
    {
        return $this->view('emails.in-app-notification')
                    ->subject($this->payload['subject'] ?? 'Новое уведомление — GameStore')
                    ->with($this->payload);
    }
}
