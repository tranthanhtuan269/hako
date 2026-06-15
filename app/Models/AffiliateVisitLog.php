<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateVisitLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'referrer_user_id',
        'ip_address',
        'user_agent',
        'landing_url',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }
}
