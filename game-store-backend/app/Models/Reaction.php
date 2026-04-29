<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Реакция юзера на пост / коммент / сообщение.
 *
 * Полиморфная связь reactable: post / comment / message (Phase 4).
 * Уникальный ключ (reactable, user, palette_emoji_id) — один юзер
 * не может ставить одну и ту же реакцию дважды на один объект.
 */
class Reaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reactable_type',
        'reactable_id',
        'user_id',
        'palette_emoji_id',
    ];

    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function emoji()
    {
        return $this->belongsTo(ReactionPalette::class, 'palette_emoji_id');
    }
}
