<?php

namespace App\Notifications;

use App\Mail\InAppNotificationMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

/**
 * Кто-то подписался на вас.
 * Phase 4 / Batch A — database channel.
 * Phase 4 / Batch B — добавлен mail channel.
 *
 * Throttle здесь без context_id — просто per-user раз в 30 мин:
 * если за час 20 человек подписалось — приходит один email,
 * остальные только в database (видны в bell-dropdown).
 */
class NewFollower extends Notification
{
    use Queueable;

    public function __construct(public User $follower) {}

    public function via($notifiable): array
    {
        // database + broadcast (мгновенный push) + mail (throttle 30 мин per user)
        $channels = ['database', 'broadcast'];

        if (
            !empty($notifiable->email)
            && ($notifiable->notify_email_follower ?? true)
        ) {
            $key = "mail_dedupe:{$notifiable->id}:follow.created";
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

    public function toMail($notifiable): InAppNotificationMail
    {
        $actorName = $this->follower->fullname ?: $this->follower->username ?: 'Кто-то';
        $profileUrl = $this->follower->username
            ? config('app.frontend_url') . "/u/{$this->follower->username}"
            : config('app.frontend_url') . "/feed";

        return new InAppNotificationMail([
            'subject'    => "Новый подписчик — GameStore",
            'title'      => '⚔ Новый подписчик',
            'recipient'  => $notifiable->fullname,
            'intro'      => "{$actorName} подписался на вас. Теперь ваши новые посты появятся в его ленте.",
            'preview'    => null,
            'cta_url'    => $profileUrl,
            'cta_label'  => 'К профилю подписчика →',
            'pref_label' => 'Уведомления о новых подписчиках',
        ]);
    }
}
