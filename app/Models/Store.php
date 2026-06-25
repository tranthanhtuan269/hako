<?php

namespace App\Models;

use App\Support\HtmlCleaner;
use App\Support\PublicImage;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Store extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'logo',
        'website',
        'affiliate_url',
        'description',
        'category_id',
        'sort_order',
        'show_on_stores',
        'stores_list_sort_order',
        'store_coupon_limit',
        'is_active',
        'view_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_on_stores' => 'boolean',
        'store_coupon_limit' => 'integer',
        'stores_list_sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Store $store) {
            if (empty($store->slug)) {
                $store->slug = Str::slug($store->name);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function keywordSet(): HasOne
    {
        return $this->hasOne(StoreKeywordSet::class);
    }

    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVisibleOnStoresPage(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('show_on_stores'), true);
    }

    public static function publicCatalogQuery(): Builder
    {
        return static::query()
            ->active()
            ->visibleOnStoresPage()
            ->orderByDesc('stores_list_sort_order')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function listingDescription(): ?string
    {
        $text = HtmlCleaner::plainText($this->description);

        return $text !== '' ? $text : null;
    }

    public function activeCouponsCount(): int
    {
        return $this->coupons()->valid()->count();
    }

    public function storeCouponLimit(): int
    {
        return max(1, (int) ($this->store_coupon_limit ?? 16));
    }

    public function publicStoreCouponsQuery(): Builder
    {
        return Coupon::query()
            ->where('store_id', $this->getKey())
            ->valid()
            ->where('show_on_store', true)
            ->orderByDesc('store_sort_order')
            ->orderByDesc('is_featured')
            ->latest();
    }

    public function visibleStoreCouponsCount(): int
    {
        return $this->publicStoreCouponsQuery()->count();
    }

    public function domain(): ?string
    {
        if (! $this->website) {
            return null;
        }

        $host = parse_url($this->website, PHP_URL_HOST);

        return $host ? preg_replace('/^www\./', '', $host) : null;
    }

    /** Affiliate tracking link — used when shoppers click through to the merchant. */
    public function shopUrl(): ?string
    {
        return $this->affiliate_url ?: $this->website;
    }

    /** href for store name / logo / chips: affiliate when set, else internal store page. */
    public function clickHref(): string
    {
        return $this->shopUrl() ?? route('stores.show', $this->slug);
    }

    public function clickOpensExternal(): bool
    {
        return filled($this->shopUrl());
    }

    /** Public store URL you enter in the website field. */
    public function publicWebsiteUrl(): ?string
    {
        return filled($this->website) ? $this->website : null;
    }

    public function publicWebsiteLabel(): ?string
    {
        if (! filled($this->website)) {
            return null;
        }

        $host = parse_url($this->website, PHP_URL_HOST);

        return $host ? preg_replace('/^www\./', '', $host) : $this->website;
    }

    public function hasStoredLogo(): bool
    {
        return PublicImage::isStored($this->logo);
    }

    public function logoUrl(): ?string
    {
        if ($this->hasStoredLogo() && PublicImage::isValidImage($this->logo)) {
            return PublicImage::url($this->logo);
        }

        return null;
    }

    /**
     * Re-download logo to local storage (merchant URL, Clearbit, Google favicon, …).
     */
    public function ensureLogoStored(?string $primaryUrl = null): bool
    {
        if ($this->hasStoredLogo() && PublicImage::isValidImage($this->logo)) {
            return true;
        }

        $previous = $this->logo;

        if ($previous && PublicImage::isStored($previous)) {
            PublicImage::delete($previous);
        }

        $stored = PublicImage::ingestStoreLogo(
            $primaryUrl ?? (PublicImage::isRemote($previous) ? $previous : null),
            $this->domain(),
            $this->user_id ?? 0
        );

        if (! $stored) {
            $this->forceFill(['logo' => null])->saveQuietly();

            return false;
        }

        $this->forceFill(['logo' => $stored])->saveQuietly();

        return true;
    }

    /** @deprecated Only used internally; logos must be stored locally. */
    public function faviconUrl(): ?string
    {
        return null;
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->name)) ?: [];

        return strtoupper(collect($words)->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode(''));
    }

    public function seoTitle(): string
    {
        return "{$this->name} Coupons & Promo Codes";
    }

    public function seoDescription(): string
    {
        $count = $this->visibleStoreCouponsCount();
        $base = HtmlCleaner::plainText($this->description)
            ?: "Browse {$count} {$this->name} coupon codes and discount deals. Updated daily on " . config('site.name') . '.';

        return Seo::description($base);
    }

    public function ogImageUrl(): ?string
    {
        return $this->logoUrl();
    }
}
