<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Палитра доступных реакций (эмодзи).
 *
 * Phase 2 / Batch A — стартовый seeder в database/seeders/
 * ReactionsPaletteSeeder.php с 8 эмодзи в Ashenforge-стиле.
 *
 * is_default — попадают в новые посты автоматически (если автор не
 * ограничивает палитру через post_allowed_reactions).
 */
class ReactionPalette extends Model
{
    use HasFactory;

    protected $table = 'reactions_palette';

    protected $fillable = [
        'emoji_char',
        'emoji_url',
        'name',
        'description',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function reactions()
    {
        return $this->hasMany(Reaction::class, 'palette_emoji_id');
    }

    /**
     * Активные эмодзи отсортированные по sort_order.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
