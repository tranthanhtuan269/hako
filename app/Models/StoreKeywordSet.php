<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreKeywordSet extends Model
{
    protected $fillable = [
        'store_id',
        'user_id',
        'products',
        'result',
    ];

    protected $casts = [
        'products' => 'array',
        'result' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
