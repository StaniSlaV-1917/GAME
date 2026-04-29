<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Подписка одного юзера на другого.
 * Phase 3 / Batch A.
 */
class Follow extends Model
{
    use HasFactory;

    // Phase 1 миграция создала follows только с created_at,
    // без updated_at. Отключаем updated_at:
    public const UPDATED_AT = null;

    protected $fillable = [
        'follower_id',
        'following_id',
    ];

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function followed()
    {
        return $this->belongsTo(User::class, 'following_id');
    }
}
