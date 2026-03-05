<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 

class Review extends Model
{
    use HasFactory;
    use SoftDeletes; 

    protected $table = 'reviews';

    public $timestamps = true;

    protected $fillable = [
        'game_id',
        'user_id',
        'rating',
        'title',
        'body',
        'status',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
