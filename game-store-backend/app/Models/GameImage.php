<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameImage extends Model
{
    use HasFactory;

    protected $table = 'game_images';

    public $timestamps = true;

    protected $fillable = [
        'game_id',
        'path',
        'sort_order',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
