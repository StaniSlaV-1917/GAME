<?php

namespace App\Notifications;

use App\Mail\InAppNotificationMail;
use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

/**
 * Кто-то отреагировал на ваш пост.
 * Phase 4 / Batch A — database channel.
 * Phase 4 / Batch B — добавлен mail channel с агрессивным throttle
 * (виральные посты могут собирать десятки реакций — не спамим email).
 */
class NewReactionOnYourPost extends Notification
{
    use Queueable;

    public function __construct(
        public User $actor,
        public Post $post,
        public string $emojiId
    ) {}

    public function via($notifiable): array
    {
        $channels = ['database'];

        if (
            !empty($notifiable->email)
            && ($notifiable->notify_email_reaction ?? true)
        ) {
            // Throttle на пару (юзер, пост) — один email в 30 мин на
            // каждый пост даже если он соберёт сотню реакций.
            $key = "mail_dedupe:{$notifiable->id}:reaction.created:{$this->post->id}";
            if (Cache::add($key, true, now()->addMinutes(30))) {
                $channels[] = 'mail';
            }
        }

        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'event'      => 'reaction.created',
            'post_id'    => $this->post->id,
            'post_title' => $this->post->title,
            'emoji'      => $this->emojiId,
            'actor'      => [
                'id'       => $this->actor->id,
                'username' => $this->actor->username,
                'fullname' => $this->actor->fullname,
                'avatar'   => $this->actor->avatar,
            ],
            'preview'    => mb_substr(
                (string) ($this->post->title ?: $this->post->body),
                0,
                160
            ),
        ];
    }

    public function toMail($notifiable): InAppNotificationMail
    {
        $actorName = $this->actor->fullname ?: $this->actor->username ?: 'Кто-то';
        $postTitle = $this->post->title ? "«{$this->post->title}»" : 'ваш пост';

        return new InAppNotificationMail([
            'subject'    => "Новая реакция — GameStore",
            'title'      => "{$this->emojiId} Новая реакция на ваш пост",
            'recipient'  => $notifiable->fullname,
            'intro'      => "{$actorName} отреагировал {$this->emojiId} на {$postTitle}.",
            'preview'    => null, // для реакции отдельного контента нет
            'cta_url'    => config('app.frontend_url') . "/post/{$this->post->id}",
            'cta_label'  => 'К посту →',
            'pref_label' => 'Уведомления о реакциях',
        ]);
    }
}
