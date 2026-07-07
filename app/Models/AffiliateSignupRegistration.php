<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AffiliateSignupRegistration extends Model
{
    protected $fillable = [
        'scan_project_id',
        'project',
        'signup_link',
        'registered_by',
        'registered_at',
    ];

    protected $casts = [
        'scan_project_id' => 'integer',
        'registered_at' => 'datetime',
    ];

    /** @return array<int, string> scan_project_id => registered_at (Y-m-d H:i) */
    public static function registeredMap(): array
    {
        return static::query()
            ->orderByDesc('registered_at')
            ->pluck('registered_at', 'scan_project_id')
            ->map(fn ($value) => $value instanceof Carbon ? $value->format('Y-m-d H:i') : (string) $value)
            ->all();
    }

    public static function isRegistered(int $scanProjectId): bool
    {
        return static::query()->where('scan_project_id', $scanProjectId)->exists();
    }

    public static function markRegistered(
        int $scanProjectId,
        string $signupLink,
        ?string $project,
        ?int $userId
    ): self {
        return static::query()->updateOrCreate(
            ['scan_project_id' => $scanProjectId],
            [
                'project' => $project,
                'signup_link' => $signupLink,
                'registered_by' => $userId,
                'registered_at' => now(),
            ]
        );
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
