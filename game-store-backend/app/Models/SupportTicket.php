<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_email',
        'user_name',
        'problem_path',
        'body',
        'status',
        'admin_note',
    ];

    // Метки статусов (для удобства)
    public static array $statuses = [
        'new'         => 'Новое',
        'in_progress' => 'В работе',
        'resolved'    => 'Решено',
        'rejected'    => 'Отклонено',
    ];
}
