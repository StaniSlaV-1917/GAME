<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Пост — единая сущность для новостей, обзоров, гайдов, обсуждений.
 *
 * Phase 2 / Batch A — Eloquent-модель + связи.
 *
 * Гибкая модель: обязательны author_id + body + минимум 1 тег. Title,
 * cover_url, game_id, regions — опциональны. Поиск по тегам через
 * GIN-индекс на jsonb tags (только Postgres).
 */
class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'author_id',
        'author_role',
        'title',
        'body',
        'cover_url',
        'game_id',
        'tags',
        'regions',
        'visibility',
        'moderation_status',
        'is_featured',
        'is_pinned',
        'is_locked',
        'published_at',
    ];

    protected $casts = [
        'tags'           => 'array',
        'regions'        => 'array',
        'is_featured'    => 'boolean',
        'is_pinned'      => 'boolean',
        'is_locked'      => 'boolean',
        'published_at'   => 'datetime',
        'reaction_count' => 'integer',
        'comment_count'  => 'integer',
        'view_count'     => 'integer',
    ];

    // ── Связи ──────────────────────────────────────────────────

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id'); // только корневые
    }

    public function allComments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Полиморфная связь: реакции на пост (через reactable morph).
     */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    public function allowedReactions()
    {
        return $this->belongsToMany(
            ReactionPalette::class,
            'post_allowed_reactions',
            'post_id',
            'palette_emoji_id'
        );
    }

    // ── Scopes ─────────────────────────────────────────────────

    /**
     * Только опубликованные, прошедшие модерацию, видимые публично.
     */
    public function scopePublished($query)
    {
        return $query->where('moderation_status', 'approved')
                     ->where('visibility', 'public')
                     ->whereNotNull('published_at');
    }

    /**
     * Сортировка по sticky → published_at desc (закреплённые сверху).
     */
    public function scopeFeed($query)
    {
        return $query->orderByDesc('is_pinned')
                     ->orderByDesc('published_at');
    }

    /**
     * Поиск по тегу (Postgres jsonb @> оператор для performance).
     */
    public function scopeWithTag($query, string $tag)
    {
        if (\DB::connection()->getDriverName() === 'pgsql') {
            return $query->whereRaw('tags @> ?::jsonb', [json_encode([$tag])]);
        }
        return $query->whereJsonContains('tags', $tag);
    }
}
