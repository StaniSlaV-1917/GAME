<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'fullname',
        'email',
        'phone',
        'password',
        'role',
        'reg_date',
        'avatar',
        'email_hash',
        'phone_hash',
        'notify_login',
        'notify_order_created',
        'notify_order_status',
    ];

    protected $casts = [
        'notify_login'         => 'boolean',
        'notify_order_created' => 'boolean',
        'notify_order_status'  => 'boolean',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_hash',
        'phone_hash',
    ];

    public $timestamps = false;

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function reviews()
    {
        return $this->hasMany(\App\Models\Review::class);
    }

    public function cartItems()
    {
        return $this->hasMany(\App\Models\CartItem::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }
}
