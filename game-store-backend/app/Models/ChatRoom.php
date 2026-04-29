<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 4 / Batch D — комната чата (DM, группа или публичный канал).
 *
 * type='direct'  — DM 1-на-1, name=null, ровно 2 participants
 * type='group'   — групповой чат (Phase 4/E)
 * type='public'  — открытый тематический канал (Phase 4/F)
 *
 * last_message_at обновляется при каждом sendMessage — для сортировки
 * списка чатов «свежие сверху».
 */
class ChatRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'description',
        'avatar_url',
        'created_by',
        'is_archived',
        'last_message_at',
    ];

    protected $casts = [
        'is_archived'     => 'boolean',
        'last_message_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function participants()
    {
        return $this->hasMany(ChatParticipant::class);
    }

    public function activeParticipants()
    {
        return $this->participants()->whereNull('left_at');
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'chat_room_participants',
            'chat_room_id',
            'user_id'
        )->withPivot(['role', 'last_read_message_id', 'is_pinned', 'is_muted', 'left_at']);
    }

    public function isDirect(): bool
    {
        return $this->type === 'direct';
    }
}
