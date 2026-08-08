<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateExcelImportItem extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'affiliate_excel_import_id',
        'user_id',
        'sheet_name',
        'source_row',
        'stt',
        'category_name',
        'store_name',
        'website',
        'logo',
        'affiliate_url',
        'offers',
        'status',
        'error_message',
        'store_id',
        'coupons_added',
        'was_existing_store',
        'processed_at',
    ];

    protected $casts = [
        'offers' => 'array',
        'source_row' => 'integer',
        'coupons_added' => 'integer',
        'was_existing_store' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(AffiliateExcelImport::class, 'affiliate_excel_import_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function markProcessing(): void
    {
        $this->forceFill([
            'status' => self::STATUS_PROCESSING,
            'error_message' => null,
        ])->save();
    }

    public function markDone(Store $store, int $couponsAdded, bool $wasExisting): void
    {
        $this->forceFill([
            'status' => self::STATUS_DONE,
            'store_id' => $store->id,
            'coupons_added' => $couponsAdded,
            'was_existing_store' => $wasExisting,
            'error_message' => null,
            'processed_at' => now(),
        ])->save();
    }

    public function markFailed(string $message): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'error_message' => $message,
            'processed_at' => now(),
        ])->save();
    }
}
