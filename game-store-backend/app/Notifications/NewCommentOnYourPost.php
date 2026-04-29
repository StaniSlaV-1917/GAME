<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Кто-то прокомментировал ваш пост.
 * Phase 4 / Batch A.
 */
class NewCommentOnYourPost extends Notification
{
    use Queueable;

    public function __construct(public Comment $comment) {}

    public function via($notifiable): array
    {
        return ['database'];
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
}
