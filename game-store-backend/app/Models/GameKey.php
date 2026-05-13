<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameKey extends Model
{
    use HasFactory;

    protected $table = 'game_keys';

    protected $fillable = [
        'game_id',
        'key_code',
        'is_issued',
        'issued_to',
        'order_id',
        'issued_at',
    ];

    protected $casts = [
        'is_issued'  => 'boolean',
        'issued_at'  => 'datetime',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function issuedToUser()
    {
        return $this->belongsTo(User::class, 'issued_to');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_issued', false);
    }
}
