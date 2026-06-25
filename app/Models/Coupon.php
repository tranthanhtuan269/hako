<?php

namespace App\Models;

use App\Support\Seo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Coupon extends Model
{
    protected $fillable = [
        'user_id',
        'store_id',
        'title',
        'slug',
        'description',
        'code',
        'type',
        'discount_type',
        'discount_value',
        'affiliate_url',
        'starts_at',
        'expires_at',
        'is_featured',
        'is_active',
        'show_on_store',
        'store_sort_order',
        'show_on_coupons',
        'coupons_sort_order',
        'click_count',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'show_on_store' => 'boolean',
        'store_sort_order' => 'integer',
        'show_on_coupons' => 'boolean',
        'coupons_sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Coupon $coupon) {
            if (empty($coupon->slug)) {
                $coupon->slug = Str::slug($coupon->title) . '-' . Str::random(4);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_active'), true);
    }

    public function scopeValid(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->active()
            ->where(function (Builder $q) use ($now, $query) {
                $q->whereNull($query->qualifyColumn('starts_at'))
                    ->orWhere($query->qualifyColumn('starts_at'), '<=', $now);
            })
            ->where(function (Builder $q) use ($now, $query) {
                $q->whereNull($query->qualifyColumn('expires_at'))
                    ->orWhere($query->qualifyColumn('expires_at'), '>=', $now);
            });
    }

    public function scopeVisibleOnCouponsPage(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('show_on_coupons'), true);
    }

    public static function publicCatalogQuery(): Builder
    {
        return static::query()
            ->valid()
            ->visibleOnCouponsPage()
            ->orderByDesc('coupons_sort_order')
            ->orderByDesc('is_featured')
            ->latest();
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_featured'), true);
    }

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        if ($type && in_array($type, ['coupon', 'discount'], true)) {
            return $query->where('type', $type);
        }

        return $query;
    }

    public function expiresLabel(): ?string
    {
        if (! $this->expires_at) {
            return null;
        }

        if ($this->expires_at->isPast()) {
            return 'Expired';
        }

        $diff = now()->diff($this->expires_at);
        $parts = [];
        if ($diff->d > 0) {
            $parts[] = $diff->d.'d';
        }
        if ($diff->h > 0) {
            $parts[] = $diff->h.'h';
        }
        if ($parts === [] && $diff->i > 0) {
            $parts[] = $diff->i.'m';
        }

        return 'Expires: '.implode(' ', $parts ?: ['soon']);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * @return array{visible: string, hidden: string}
     */
    public function maskedCodeParts(int $visibleChars = 2): array
    {
        $code = (string) ($this->code ?? '');
        $length = mb_strlen($code);

        if ($length === 0) {
            return ['visible' => '', 'hidden' => ''];
        }

        $visible = min(max(1, $visibleChars), max(1, $length - 1));

        return [
            'visible' => mb_substr($code, 0, $visible),
            'hidden' => str_repeat('•', $length - $visible),
        ];
    }

    public function discountLabel(): string
    {
        return match ($this->discount_type) {
            'percent' => (int) $this->discount_value . '% OFF',
            'fixed' => '$' . number_format((float) $this->discount_value, 0, '.', ',') . ' OFF',
            'free_shipping' => 'Free Shipping',
            default => $this->type === 'coupon' ? 'Promo Code' : 'Deal',
        };
    }

    public function typeLabel(): string
    {
        return $this->type === 'coupon' ? 'Coupon Code' : 'Discount Deal';
    }

    public function incrementClicks(): void
    {
        $this->increment('click_count');
    }

    public function seoTitle(): string
    {
        return "{$this->title} — {$this->store->name} {$this->typeLabel()}";
    }

    public function seoDescription(): string
    {
        $base = $this->description
            ?: "Get {$this->discountLabel()} at {$this->store->name}. {$this->typeLabel()} listed on " . config('site.name') . '.';

        return Seo::description($base);
    }

    public function ogImageUrl(): ?string
    {
        return $this->store?->logoUrl();
    }

    public function affiliateClickUrl(): ?string
    {
        if (filled($this->affiliate_url)) {
            return $this->affiliate_url;
        }

        return $this->store?->shopUrl();
    }
}
