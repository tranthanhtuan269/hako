<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\Store;
use Illuminate\Support\Collection;

class ScrollCouponPopup
{
    public static function forStore(?Store $store, int $limit = 5): ?array
    {
        if (! $store || ! $store->is_active) {
            return null;
        }

        $coupons = $store->coupons()
            ->valid()
            ->orderByDesc('is_featured')
            ->latest()
            ->take($limit)
            ->get();

        if ($coupons->isEmpty() && ! $store->shopUrl()) {
            return null;
        }

        return self::buildPayload($store, $coupons);
    }

    /**
     * @param  Collection<int, Coupon>  $coupons
     */
    public static function buildPayload(Store $store, Collection $coupons): array
    {
        $affiliateUrl = $coupons->first()
            ? route('coupons.go', $coupons->first()->slug)
            : (string) $store->shopUrl();

        return [
            'storeName' => $store->name,
            'storeSlug' => $store->slug,
            'storeLogo' => $store->logoUrl(),
            'affiliateUrl' => $affiliateUrl,
            'coupons' => $coupons->map(fn (Coupon $coupon) => [
                'title' => $coupon->title,
                'discount' => $coupon->discountLabel(),
                'code' => $coupon->code,
                'expires' => $coupon->expiresLabel(),
                'goUrl' => route('coupons.go', $coupon->slug),
                'revealUrl' => route('coupons.reveal', $coupon->slug),
            ])->values()->all(),
        ];
    }
}
