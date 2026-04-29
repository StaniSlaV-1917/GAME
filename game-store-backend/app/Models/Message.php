<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Phase 4 / Batch D — сообщение в чате.
 *
 * Один тип для всех чатов (direct/group/public). attachments — JSON-array
 * для будущих картинок/файлов (Phase 4/E или позже). reply_to_message_id
 * для цитирования в группах.
 */
class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'chat_room_id',
        'sender_id',
        'body',
        'attachments',
        'reply_to_message_id',
        'is_edited',
        'moderation_status',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_edited'   => 'boolean',
    ];

    public function chatRoom()
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('moderation_status', 'approved');
    }
}
