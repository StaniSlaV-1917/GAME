<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Кто-то подписался на вас.
 * Phase 4 / Batch A.
 */
class NewFollower extends Notification
{
    use Queueable;

    public function __construct(public User $follower) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'event' => 'follow.created',
            'actor' => [
                'id'       => $this->follower->id,
                'username' => $this->follower->username,
                'fullname' => $this->follower->fullname,
                'avatar'   => $this->follower->avatar,
            ],
        ];
    }
}
