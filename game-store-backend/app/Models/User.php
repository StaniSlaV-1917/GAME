<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles;

    protected $table = 'users';

    protected $fillable = [
        'fullname',
        'username',
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
        'banned_at',
        'ban_reason',
        'frozen_at',
        'freeze_reason',
        'followers_count',
        'following_count',
    ];

    protected $casts = [
        'notify_login'         => 'boolean',
        'notify_order_created' => 'boolean',
        'notify_order_status'  => 'boolean',
        'banned_at'            => 'datetime',
        'frozen_at'            => 'datetime',
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

    /**
     * На кого этот юзер подписан (его подписки).
     * Через pivot follows: follower_id = $this->id, followed_id = target.
     */
    public function following()
    {
        return $this->belongsToMany(
            User::class,
            'follows',
            'follower_id',
            'followed_id'
        )->withTimestamps();
    }

    /**
     * Кто подписан на этого юзера (его подписчики).
     */
    public function followers()
    {
        return $this->belongsToMany(
            User::class,
            'follows',
            'followed_id',
            'follower_id'
        )->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Юзер забанен — не может логиниться вообще.
     * Возвращает причину в ответе при попытке /api/auth/login.
     */
    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    /**
     * Юзер заморожен — может логиниться и читать, но не может
     * создавать контент (посты/комменты/реакции).
     */
    public function isFrozen(): bool
    {
        return $this->frozen_at !== null;
    }

    /**
     * Активный юзер: не забанен и не заморожен.
     */
    public function isActive(): bool
    {
        return !$this->isBanned() && !$this->isFrozen();
    }
}
