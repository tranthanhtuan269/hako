<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliatePayoutRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    public const STATUS_REJECTED = 'rejected';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_PAID,
        self::STATUS_REJECTED,
    ];

    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'payment_method',
        'payment_details',
        'member_note',
        'admin_note',
        'processed_at',
        'processed_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PAID => 'Paid',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Pending',
        };
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
