<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mod extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'title',
        'description',
        'external_url',
        'source_site',
        'author',
        'version',
        'download_count',
        'popularity_score',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'download_count' => 'integer',
        'popularity_score' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}