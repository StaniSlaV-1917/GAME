<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Кто-то отреагировал на ваш пост.
 * Phase 4 / Batch A.
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
        return ['database'];
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
            // Превью для дропдауна — заголовок поста (или начало body, если без title)
            'preview'    => mb_substr(
                (string) ($this->post->title ?: $this->post->body),
                0,
                160
            ),
        ];
    }
}
