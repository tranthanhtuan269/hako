<?php

namespace App\Models;

use App\Support\Seo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function coupons(): HasManyThrough
    {
        return $this->hasManyThrough(Coupon::class, Store::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function seoTitle(): string
    {
        return "{$this->name} Coupons & Deals";
    }

    public function seoDescription(): string
    {
        $base = $this->description
            ?: "Shop {$this->name} coupon codes and online deals for U.S. stores. Curated offers on " . config('site.name') . '.';

        return Seo::description($base);
    }
}
