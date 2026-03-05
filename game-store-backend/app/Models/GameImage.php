<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameImage extends Model
{
    use HasFactory;

    /**
     * Таблица, связанная с моделью.
     *
     * @var string
     */
    protected $table = 'game_images';

    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'game_id',
        'path',
        'sort_order',
    ];

    /**
     * Получить игру, которой принадлежит это изображение.
     */
    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
