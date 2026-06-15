<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateOrder extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PAID,
        self::STATUS_CANCELLED,
        self::STATUS_REFUNDED,
    ];

    protected $fillable = [
        'referrer_user_id',
        'referred_user_id',
        'order_number',
        'customer_name',
        'customer_email',
        'description',
        'amount',
        'commission_rate',
        'commission_amount',
        'status',
        'commission_credited',
        'paid_at',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_credited' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'Paid',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REFUNDED => 'Refunded',
            default => 'Pending',
        };
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
