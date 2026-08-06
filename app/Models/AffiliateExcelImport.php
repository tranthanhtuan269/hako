<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliateExcelImport extends Model
{
    protected $fillable = [
        'user_id',
        'original_filename',
        'stored_path',
        'status',
        'total_items',
        'processed_items',
        'failed_items',
        'error_message',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'processed_items' => 'integer',
        'failed_items' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(AffiliateExcelImportItem::class);
    }

    public function refreshCounters(): void
    {
        $this->forceFill([
            'total_items' => $this->items()->count(),
            'processed_items' => $this->items()->where('status', 'done')->count(),
            'failed_items' => $this->items()->where('status', 'failed')->count(),
            'status' => $this->items()->where('status', 'pending')->exists()
                ? ($this->items()->whereIn('status', ['done', 'failed'])->exists() ? 'processing' : 'parsed')
                : 'completed',
        ])->save();
    }
}
