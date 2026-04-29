<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pay/A — крипто-платежи (USDT TRC-20 / BEP-20 / TRX).
 *
 * Один pending_payment = одно «окно оплаты». Юзер видит уникальную
 * дробную сумму и адрес. Бекенд раз в 30 сек скан транзакций через
 * TronGrid, матч по сумме → status='confirmed'.
 *
 * MVP реализует только USDT_TRC20. Остальные crypto_currency
 * зарезервированы в schema под будущие итерации.
 */
class Payment extends Model
{
    use HasFactory;

    protected $table = 'pending_payments';

    protected $fillable = [
        'user_id',
        'order_id',
        'crypto_currency',
        'amount_rub',
        'amount_crypto',
        'exchange_rate',
        'recipient_address',
        'status',
        'transaction_hash',
        'confirmations',
        'expires_at',
        'confirmed_at',
        'metadata',
    ];

    protected $casts = [
        'amount_rub'    => 'decimal:2',
        'amount_crypto' => 'decimal:8',
        'exchange_rate' => 'decimal:8',
        'confirmations' => 'integer',
        'expires_at'    => 'datetime',
        'confirmed_at'  => 'datetime',
        'metadata'      => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Активные (не истёкшие, не подтверждённые) платежи.
     * Используется в worker'е для матчинга.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                     ->where('expires_at', '>', now());
    }

    /**
     * Истёкшие но всё ещё в pending — кандидаты для перевода в expired.
     */
    public function scopeStaleAndExpired($query)
    {
        return $query->where('status', 'pending')
                     ->where('expires_at', '<=', now());
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->status === 'pending' && $this->expires_at->isPast());
    }

    public function secondsRemaining(): int
    {
        if (!$this->isPending()) return 0;
        return max(0, $this->expires_at->diffInSeconds(now(), false) * -1);
    }
}
