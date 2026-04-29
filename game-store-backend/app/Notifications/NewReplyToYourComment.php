<?php

namespace App\Notifications;

use App\Mail\InAppNotificationMail;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

/**
 * Кто-то ответил на ваш комментарий.
 * Phase 4 / Batch A — database channel.
 * Phase 4 / Batch B — добавлен mail channel с throttle.
 */
class NewReplyToYourComment extends Notification
{
    use Queueable;

    public function __construct(
        public Comment $reply,
        public Comment $parent
    ) {}

    public function via($notifiable): array
    {
        // database + broadcast (без throttle — мгновенный push)
        // + mail (с throttle 30 мин на одну пару юзер↔коммент)
        $channels = ['database', 'broadcast'];

        if (
            !empty($notifiable->email)
            && ($notifiable->notify_email_reply ?? true)
        ) {
            $key = "mail_dedupe:{$notifiable->id}:comment.reply:{$this->parent->id}";
            if (Cache::add($key, true, now()->addMinutes(30))) {
                $channels[] = 'mail';
            }
        }

        return $channels;
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function toDatabase($notifiable): array
    {
        $author = $this->reply->author;
        $post   = $this->reply->post;
        return [
            'event'             => 'comment.reply',
            'comment_id'        => $this->reply->id,
            'parent_comment_id' => $this->parent->id,
            'post_id'           => $this->reply->post_id,
            'post_title'        => $post?->title,
            'actor'             => [
                'id'       => $author->id,
                'username' => $author->username,
                'fullname' => $author->fullname,
                'avatar'   => $author->avatar,
            ],
            'preview'        => mb_substr($this->reply->body, 0, 280),
            'parent_preview' => mb_substr((string) $this->parent->body, 0, 200),
        ];
    }

    public function toMail($notifiable): InAppNotificationMail
    {
        $author = $this->reply->author;
        $post   = $this->reply->post;
        $actorName = $author?->fullname ?: $author?->username ?: 'Кто-то';
        $postTitle = $post?->title ? " в обсуждении «{$post->title}»" : '';

        return new InAppNotificationMail([
            'subject'        => "Ответ на ваш комментарий — GameStore",
            'title'          => '↪ Новый ответ на ваш комментарий',
            'recipient'      => $notifiable->fullname,
            'intro'          => "{$actorName} ответил на ваш комментарий{$postTitle}.",
            'preview'        => mb_substr($this->reply->body, 0, 280),
            'parent_preview' => mb_substr((string) $this->parent->body, 0, 200),
            'cta_url'        => config('app.frontend_url') . "/post/{$this->reply->post_id}",
            'cta_label'      => 'К обсуждению →',
            'pref_label'     => 'Уведомления об ответах',
        ]);
    }
}
