<?php

namespace App\Notifications;

use App\Mail\InAppNotificationMail;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

/**
 * Кто-то прокомментировал ваш пост.
 * Phase 4 / Batch A — database channel.
 * Phase 4 / Batch B — добавлен mail channel с throttle.
 */
class NewCommentOnYourPost extends Notification
{
    use Queueable;

    public function __construct(public Comment $comment) {}

    /**
     * Каналы доставки. Database всегда. Mail — если у юзера включена pref
     * notify_email_comment, есть email и не сработал throttle (один email
     * в 30 мин на одну пару юзер↔пост).
     */
    public function via($notifiable): array
    {
        $channels = ['database'];

        if (
            !empty($notifiable->email)
            && ($notifiable->notify_email_comment ?? true)
        ) {
            $key = "mail_dedupe:{$notifiable->id}:comment.created:{$this->comment->post_id}";
            // add() ставит ключ только если его не было — атомарная защита
            // от спама email при шквале комментариев на один пост.
            if (Cache::add($key, true, now()->addMinutes(30))) {
                $channels[] = 'mail';
            }
        }

        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        $author = $this->comment->author;
        $post   = $this->comment->post;
        return [
            'event'      => 'comment.created',
            'comment_id' => $this->comment->id,
            'post_id'    => $this->comment->post_id,
            'post_title' => $post?->title,
            'actor'      => [
                'id'       => $author->id,
                'username' => $author->username,
                'fullname' => $author->fullname,
                'avatar'   => $author->avatar,
            ],
            'preview'    => mb_substr($this->comment->body, 0, 280),
        ];
    }

    public function toMail($notifiable): InAppNotificationMail
    {
        $author = $this->comment->author;
        $post   = $this->comment->post;
        $actorName = $author?->fullname ?: $author?->username ?: 'Кто-то';
        $postTitle = $post?->title ? "«{$post->title}»" : 'ваш пост';

        return new InAppNotificationMail([
            'subject'    => "Новый комментарий — GameStore",
            'title'      => '💬 Новый комментарий',
            'recipient'  => $notifiable->fullname,
            'intro'      => "{$actorName} прокомментировал {$postTitle}.",
            'preview'    => mb_substr($this->comment->body, 0, 280),
            'cta_url'    => config('app.frontend_url') . "/post/{$this->comment->post_id}",
            'cta_label'  => 'К посту →',
            'pref_label' => 'Уведомления о комментариях',
        ]);
    }
}
