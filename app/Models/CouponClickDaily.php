<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class CouponClickDaily extends Model
{
    protected $fillable = [
        'coupon_id',
        'store_id',
        'stat_date',
        'clicks',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'clicks' => 'integer',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public static function recordClick(Coupon $coupon): void
    {
        if (! $coupon->id || ! $coupon->store_id) {
            return;
        }

        $date = now()->toDateString();
        $now = now()->toDateTimeString();

        DB::statement(
            'INSERT INTO coupon_click_dailies (coupon_id, store_id, stat_date, clicks, created_at, updated_at)
             VALUES (?, ?, ?, 1, ?, ?)
             ON DUPLICATE KEY UPDATE
                clicks = clicks + 1,
                store_id = VALUES(store_id),
                updated_at = VALUES(updated_at)',
            [$coupon->id, $coupon->store_id, $date, $now, $now]
        );
    }
}
