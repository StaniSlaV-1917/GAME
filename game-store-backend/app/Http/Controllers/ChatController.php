<?php

namespace App\Http\Controllers;

use App\Events\NewChatMessage;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Phase 4 / Batch D — REST для чатов.
 *
 * Все endpoint'ы под auth:sanctum.
 *
 * Семантика:
 *   GET    /api/chats                   — список чатов юзера (с last message + unread)
 *   GET    /api/chats/unread-count      — общий счётчик для bell-style badge
 *   POST   /api/chats/dm                — создать/найти DM с user_id
 *   GET    /api/chats/{id}              — детали чата + 50 последних сообщений
 *   GET    /api/chats/{id}/messages     — пагинация сообщений (older first)
 *   POST   /api/chats/{id}/messages     — отправить сообщение (триггерит broadcast)
 *   POST   /api/chats/{id}/read         — пометить прочитанным до message_id
 */
class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chats
    ) {}

    /**
     * GET /api/chats/_probe — диагностика для отладки sidebar empty.
     * Без auth — чтобы можно было curl'нуть с токеном или без.
     * Возвращает: существуют ли таблицы, сколько чатов у юзера, и т.п.
     */
    public function probe(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::guard('sanctum')->user();
        $info = [
            'has_chat_rooms_table'       => \Schema::hasTable('chat_rooms'),
            'has_messages_table'         => \Schema::hasTable('messages'),
            'has_participants_table'     => \Schema::hasTable('chat_room_participants'),
            'total_rooms'                => \Schema::hasTable('chat_rooms') ? \DB::table('chat_rooms')->count() : null,
            'total_participants'         => \Schema::hasTable('chat_room_participants') ? \DB::table('chat_room_participants')->count() : null,
            'authenticated'              => (bool) $user,
            'auth_user_id'               => $user?->id,
            'my_participations'          => $user
                ? \DB::table('chat_room_participants')->where('user_id', $user->id)->whereNull('left_at')->count()
                : null,
            'my_room_ids'                => $user
                ? \DB::table('chat_room_participants')->where('user_id', $user->id)->whereNull('left_at')->pluck('chat_room_id')->all()
                : null,
        ];
        return response()->json($info);
    }

    /**
     * GET /api/chats — мои чаты (sidebar list).
     * Сортировка по last_message_at desc.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Phase 4/D.1 — упрощённый eager-load (без column subsets), чтобы
        // избежать silent fails из-за отсутствующих pivot-keys в belongsToMany.
        // Перформанс не страдает — это всего ~100 строк max.
        $rooms = ChatRoom::whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id)->whereNull('left_at');
            })
            ->with(['latestMessage', 'users'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        Log::info('[Chat] index', [
            'user_id'    => $user->id,
            'rooms_count'=> $rooms->count(),
            'room_ids'   => $rooms->pluck('id')->all(),
        ]);

        $unreadByChat = $this->chats->unreadByChat($user);

        $data = $rooms->map(function ($room) use ($user, $unreadByChat) {
            // Для direct: "контрагент" = другой участник
            $counterpart = null;
            if ($room->type === 'direct') {
                $counterpart = $room->users->first(fn ($u) => $u->id !== $user->id);
            }

            return [
                'id'              => $room->id,
                'type'            => $room->type,
                'name'            => $room->name,
                'avatar_url'      => $room->avatar_url,
                'counterpart'     => $counterpart ? [
                    'id'       => $counterpart->id,
                    'fullname' => $counterpart->fullname,
                    'username' => $counterpart->username,
                    'avatar'   => $counterpart->avatar,
                    'role'     => $counterpart->role,
                ] : null,
                'last_message'    => $room->latestMessage ? [
                    'id'         => $room->latestMessage->id,
                    'sender_id'  => $room->latestMessage->sender_id,
                    'body'       => mb_substr($room->latestMessage->body, 0, 80),
                    'created_at' => $room->latestMessage->created_at?->toIso8601String(),
                ] : null,
                'last_message_at' => $room->last_message_at?->toIso8601String(),
                'unread_count'    => $unreadByChat[$room->id] ?? 0,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function unreadCount(Request $request)
    {
        return response()->json([
            'unread_count' => $this->chats->unreadCount($request->user()),
        ]);
    }

    /**
     * POST /api/chats/dm
     * Body: { user_id: int }  ИЛИ  { username: string }
     * Создаёт или находит DM с указанным юзером, возвращает chat_room.id.
     */
    public function createDm(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'user_id'  => 'sometimes|integer|exists:users,id',
            'username' => 'sometimes|string',
        ]);

        if (empty($data['user_id']) && empty($data['username'])) {
            throw ValidationException::withMessages([
                'user_id' => ['Укажите user_id или username собеседника.'],
            ]);
        }

        if (!empty($data['username'])) {
            $target = User::whereRaw('LOWER(username) = ?', [mb_strtolower(trim($data['username']))])
                ->first();
            if (!$target) {
                return response()->json(['message' => 'Пользователь не найден'], 404);
            }
        } else {
            $target = User::find($data['user_id']);
            if (!$target) {
                return response()->json(['message' => 'Пользователь не найден'], 404);
            }
        }

        if ($target->id === $user->id) {
            throw ValidationException::withMessages([
                'user_id' => ['Нельзя написать самому себе.'],
            ]);
        }

        if (method_exists($target, 'isBanned') && $target->isBanned()) {
            return response()->json(['message' => 'Пользователь недоступен'], 403);
        }

        try {
            $room = $this->chats->createOrFindDm($user, $target);
        } catch (\Throwable $e) {
            Log::error('[Chat] createDm failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json([
            'id'   => $room->id,
            'type' => $room->type,
        ], 201);
    }

    /**
     * GET /api/chats/{id} — детали чата + последние 50 сообщений.
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $room = $this->getRoomForUser($user, $id);

        // Берём 50 НОВЕЙШИХ через ORDER BY id DESC LIMIT, потом
        // sortBy('id') разворачивает в ASC (oldest-first) для top-down
        // рендера. ->values() ресетит ключи в 0..N чтобы JSON был
        // массивом (а не объектом).
        $messages = $room->messages()->approved()
            ->with('sender:id,fullname,username,avatar,role')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->sortBy('id')
            ->values();

        $counterpart = null;
        if ($room->type === 'direct') {
            $counterpart = $room->users()->where('users.id', '!=', $user->id)->first();
        }

        return response()->json([
            'id'           => $room->id,
            'type'         => $room->type,
            'name'         => $room->name,
            'avatar_url'   => $room->avatar_url,
            'counterpart'  => $counterpart ? [
                'id'       => $counterpart->id,
                'fullname' => $counterpart->fullname,
                'username' => $counterpart->username,
                'avatar'   => $counterpart->avatar,
                'role'     => $counterpart->role,
            ] : null,
            'messages'     => $messages,
        ]);
    }

    /**
     * GET /api/chats/{id}/messages?before_id=...
     * Пагинация для подгрузки старых сообщений (scroll up).
     */
    public function messages(Request $request, int $id)
    {
        $user = $request->user();
        $room = $this->getRoomForUser($user, $id);

        $beforeId = (int) $request->query('before_id');
        $perPage  = min(50, max(1, (int) $request->query('per_page', 30)));

        $q = $room->messages()->approved()
            ->with('sender:id,fullname,username,avatar,role')
            ->orderByDesc('id')
            ->limit($perPage);

        if ($beforeId > 0) {
            $q->where('id', '<', $beforeId);
        }

        // Та же логика: фетчим DESC по id, sortBy ASC для рендера, ->values() ресетит keys
        $items = $q->get()->sortBy('id')->values();

        return response()->json(['data' => $items]);
    }

    /**
     * POST /api/chats/{id}/messages
     * Body: { body: string, reply_to_message_id?: int }
     */
    public function send(Request $request, int $id)
    {
        $user = $request->user();
        if ($user->isFrozen()) {
            return response()->json(['message' => 'Аккаунт заморожен, сообщения недоступны.'], 403);
        }

        $data = $request->validate([
            'body'                => 'required|string|min:1|max:4000',
            'reply_to_message_id' => 'sometimes|nullable|integer|exists:messages,id',
        ]);

        $room = $this->getRoomForUser($user, $id);

        try {
            $message = $this->chats->sendMessage(
                $room,
                $user,
                $data['body'],
                $data['reply_to_message_id'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        $message->load('sender:id,fullname,username,avatar,role');

        // Phase 4/D — broadcast event на private channel chat-room.{id}.
        // Все Echo-subscribers (включая отправителя в других вкладках) получат
        // payload через .NewChatMessage event.
        try {
            broadcast(new NewChatMessage($message))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('[Chat] broadcast failed', [
                'message_id' => $message->id,
                'error'      => $e->getMessage(),
            ]);
        }

        return response()->json($message, 201);
    }

    /**
     * POST /api/chats/{id}/read
     * Body: { message_id?: int }  — null = до самого свежего
     */
    public function markRead(Request $request, int $id)
    {
        $user = $request->user();
        $room = $this->getRoomForUser($user, $id);

        $data = $request->validate([
            'message_id' => 'sometimes|nullable|integer',
        ]);

        try {
            $this->chats->markRead($room, $user, $data['message_id'] ?? null);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'unread_count' => $this->chats->unreadCount($user),
        ]);
    }

    /**
     * Найти чат по id, гарантировав что юзер — его участник.
     * Возвращает 404 если чата нет или юзер не participant.
     */
    private function getRoomForUser(User $user, int $id): ChatRoom
    {
        $room = ChatRoom::find($id);
        if (!$room) {
            abort(404, 'Чат не найден');
        }

        $isParticipant = $room->participants()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->exists();

        if (!$isParticipant) {
            abort(403, 'Вы не участник этого чата');
        }

        return $room;
    }
}
