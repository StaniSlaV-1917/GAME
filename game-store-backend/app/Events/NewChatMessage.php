<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Phase 4 / Batch D — broadcast event для нового сообщения в чате.
 *
 * Летит на private channel `chat-room.{id}` через Reverb. Все active
 * participants чата (Echo.private('chat-room.123').listen('.NewChatMessage'))
 * получают payload и обновляют thread мгновенно.
 *
 * broadcastAs() задаёт имя event'а БЕЗ namespace (default было бы
 * 'App\Events\NewChatMessage', а Echo привычно слушает короткое имя
 * с точкой `.NewChatMessage`).
 */
class NewChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

    public function __construct(Message $message)
    {
        // Загружаем sender'а заранее чтобы payload содержал инфу о юзере
        $this->message = $message->loadMissing('sender:id,fullname,username,avatar,role');
    }

    /**
     * На какой channel пушим.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("chat-room.{$this->message->chat_room_id}"),
        ];
    }

    /**
     * Краткое имя event'а для Echo.listen('.NewChatMessage').
     */
    public function broadcastAs(): string
    {
        return 'NewChatMessage';
    }

    /**
     * Payload который попадёт во фронт.
     */
    public function broadcastWith(): array
    {
        return [
            'id'                  => $this->message->id,
            'chat_room_id'        => $this->message->chat_room_id,
            'sender_id'           => $this->message->sender_id,
            'body'                => $this->message->body,
            'attachments'         => $this->message->attachments,
            'reply_to_message_id' => $this->message->reply_to_message_id,
            'is_edited'           => (bool) $this->message->is_edited,
            'created_at'          => $this->message->created_at?->toIso8601String(),
            'sender' => $this->message->sender ? [
                'id'       => $this->message->sender->id,
                'fullname' => $this->message->sender->fullname,
                'username' => $this->message->sender->username,
                'avatar'   => $this->message->sender->avatar,
                'role'     => $this->message->sender->role,
            ] : null,
        ];
    }
}
