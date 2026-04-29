<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 4 / Batch D — членство юзера в чате.
 *
 * last_read_message_id — указатель «прочитано до этого id».
 * Unread-counter = count(messages) WHERE id > last_read_message_id
 * AND sender_id != participant.user_id (свои не считаются).
 */
class ChatParticipant extends Model
{
    use HasFactory;

    protected $table = 'chat_room_participants';

    protected $fillable = [
        'chat_room_id',
        'user_id',
        'role',
        'last_read_message_id',
        'joined_at',
        'left_at',
        'is_pinned',
        'is_muted',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_muted'  => 'boolean',
        'joined_at' => 'datetime',
        'left_at'   => 'datetime',
    ];

    public function chatRoom()
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
