<?php

namespace App\Services;

use App\Models\ChatParticipant;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Phase 4 / Batch D — высокоуровневый chat-сервис.
 *
 * Отделено от контроллера чтобы тесты могли мокать создание DM/markRead
 * без дёрганья HTTP-слоя. Также используется future Phase 4/G (Telegram-бот
 * как participant в чате).
 */
class ChatService
{
    /**
     * Найти существующий direct-чат между двумя юзерами или создать новый.
     * Гарантирует что между парой users есть только ОДИН direct-чат
     * (через двойной exists-чек на participants).
     *
     * @return ChatRoom
     */
    public function createOrFindDm(User $userA, User $userB): ChatRoom
    {
        if ($userA->id === $userB->id) {
            throw new \InvalidArgumentException('Нельзя создать DM с самим собой.');
        }

        // Ищем существующий direct-чат где оба участника
        $existing = ChatRoom::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userA->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userB->id))
            ->first();

        if ($existing) {
            return $existing;
        }

        // Создаём новый
        return DB::transaction(function () use ($userA, $userB) {
            $room = ChatRoom::create([
                'type'            => 'direct',
                'name'            => null,
                'created_by'      => $userA->id,
                'last_message_at' => null,
            ]);

            ChatParticipant::create([
                'chat_room_id' => $room->id,
                'user_id'      => $userA->id,
                'role'         => 'member',
                'joined_at'    => now(),
            ]);
            ChatParticipant::create([
                'chat_room_id' => $room->id,
                'user_id'      => $userB->id,
                'role'         => 'member',
                'joined_at'    => now(),
            ]);

            return $room;
        });
    }

    /**
     * Отправить сообщение в чат от имени юзера.
     * Обновляет last_message_at чата (для сортировки списка).
     */
    public function sendMessage(
        ChatRoom $room,
        User $sender,
        string $body,
        ?int $replyToId = null
    ): Message {
        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException('Сообщение не может быть пустым.');
        }
        if (mb_strlen($body) > 4000) {
            throw new \InvalidArgumentException('Сообщение слишком длинное (макс. 4000 символов).');
        }

        // Проверка участия — только participants могут писать
        $isParticipant = $room->participants()
            ->where('user_id', $sender->id)
            ->whereNull('left_at')
            ->exists();

        if (!$isParticipant) {
            throw new \DomainException('Вы не участник этого чата.');
        }

        return DB::transaction(function () use ($room, $sender, $body, $replyToId) {
            $message = Message::create([
                'chat_room_id'        => $room->id,
                'sender_id'           => $sender->id,
                'body'                => $body,
                'reply_to_message_id' => $replyToId,
                'moderation_status'   => 'approved',
            ]);

            // Обновляем last_message_at для сортировки
            $room->update(['last_message_at' => $message->created_at]);

            return $message;
        });
    }

    /**
     * Отметить сообщения в чате как прочитанные ДО указанного id (включительно).
     * Если messageId = null — отмечаем последнее сообщение.
     */
    public function markRead(ChatRoom $room, User $user, ?int $messageId = null): void
    {
        $participant = $room->participants()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (!$participant) {
            throw new \DomainException('Вы не участник этого чата.');
        }

        if ($messageId === null) {
            $messageId = $room->messages()->approved()->max('id');
        }

        if ($messageId && (!$participant->last_read_message_id || $messageId > $participant->last_read_message_id)) {
            $participant->update(['last_read_message_id' => $messageId]);
        }
    }

    /**
     * Подсчитать непрочитанные сообщения юзера во ВСЕХ чатах.
     * Используется для bell-badge или отдельного chat-icon в header.
     */
    public function unreadCount(User $user): int
    {
        // Все чаты юзера
        $rows = DB::select("
            SELECT COALESCE(SUM(unread), 0) as total
            FROM (
                SELECT count(m.id) as unread
                FROM chat_room_participants p
                JOIN messages m ON m.chat_room_id = p.chat_room_id
                WHERE p.user_id = ?
                  AND p.left_at IS NULL
                  AND m.id > COALESCE(p.last_read_message_id, 0)
                  AND m.sender_id != ?
                  AND m.deleted_at IS NULL
                  AND m.moderation_status = 'approved'
                GROUP BY p.chat_room_id
            ) sub
        ", [$user->id, $user->id]);

        return (int) ($rows[0]->total ?? 0);
    }

    /**
     * Per-чат unread counter для списка чатов в sidebar.
     * @return array  ['chat_room_id' => unread_count, ...]
     */
    public function unreadByChat(User $user): array
    {
        $rows = DB::select("
            SELECT p.chat_room_id, count(m.id) as unread
            FROM chat_room_participants p
            JOIN messages m ON m.chat_room_id = p.chat_room_id
            WHERE p.user_id = ?
              AND p.left_at IS NULL
              AND m.id > COALESCE(p.last_read_message_id, 0)
              AND m.sender_id != ?
              AND m.deleted_at IS NULL
              AND m.moderation_status = 'approved'
            GROUP BY p.chat_room_id
        ", [$user->id, $user->id]);

        $result = [];
        foreach ($rows as $r) {
            $result[(int) $r->chat_room_id] = (int) $r->unread;
        }
        return $result;
    }
}
