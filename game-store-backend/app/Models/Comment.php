<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Комментарий к посту.
 * Иерархия через parent_id self-reference, бесконечная глубина.
 * YouTube-style сворачивание глубоких веток делается на фронте по
 * полю depth (Phase 2 / Batch D).
 */
class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'post_id',
        'parent_id',
        'author_id',
        'body',
        'depth',
        'moderation_status',
    ];

    protected $casts = [
        'depth'          => 'integer',
        'reaction_count' => 'integer',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    public function scopeApproved($query)
    {
        return $query->where('moderation_status', 'approved');
    }
}
