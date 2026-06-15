<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'author_slug',
        'author_title',
        'author_bio',
        'author_avatar',
        'referral_code',
        'referred_by_user_id',
        'affiliate_balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'affiliate_balance' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (! filled($user->referral_code)) {
                do {
                    $code = strtoupper(\Illuminate\Support\Str::random(8));
                } while (static::query()->where('referral_code', $code)->exists());

                $user->referral_code = $code;
            }
        });
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function referrer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }

    public function affiliateOrders(): HasMany
    {
        return $this->hasMany(AffiliateOrder::class, 'referrer_user_id');
    }

    public function affiliatePayoutRequests(): HasMany
    {
        return $this->hasMany(AffiliatePayoutRequest::class);
    }

    public function ensureAuthorSlug(): void
    {
        if (filled($this->author_slug)) {
            return;
        }

        $base = Str::slug($this->name) ?: 'author';
        $slug = $base;
        $i = 1;

        while (static::query()
            ->where('author_slug', $slug)
            ->whereKeyNot($this->id)
            ->exists()) {
            $slug = $base . '-' . $i++;
        }

        $this->forceFill(['author_slug' => $slug])->saveQuietly();
    }

    public function authorAvatarUrl(): ?string
    {
        if (! filled($this->author_avatar)) {
            return null;
        }

        if (str_starts_with($this->author_avatar, 'http://') || str_starts_with($this->author_avatar, 'https://')) {
            return $this->author_avatar;
        }

        return asset('storage/' . ltrim($this->author_avatar, '/'));
    }
}
