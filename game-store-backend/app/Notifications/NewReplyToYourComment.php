<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Кто-то ответил на ваш комментарий.
 * Phase 4 / Batch A.
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
        return ['database'];
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
}
