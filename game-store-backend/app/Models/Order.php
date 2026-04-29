<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'order_date',
        'status',
        'total',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Pay/A — связанные крипто-платежи (1:N).
     * Один заказ → один payment в обычном кейсе, но если юзер
     * пробовал оплатить, окно истекло, потом создал новый —
     * могут быть multiple. Для admin view берём latest.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class)->orderByDesc('created_at');
    }

    /** Последний платёж (или null если их нет) */
    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }
}
