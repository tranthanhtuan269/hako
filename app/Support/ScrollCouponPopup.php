<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\Store;
use Illuminate\Support\Collection;

class ScrollCouponPopup
{
    public static function forStore(?Store $store, int $limit = 5, bool $openAffiliateOnCopy = false): ?array
    {
        if (! $store || ! $store->is_active) {
            return null;
        }

        $coupons = $store->publicStoreCouponsQuery()
            ->take($limit)
            ->get();

        if ($coupons->isEmpty() && ! $store->shopUrl()) {
            return null;
        }

        return self::buildPayload($store, $coupons, $openAffiliateOnCopy);
    }

    /**
     * @param  Collection<int, Coupon>  $coupons
     */
    public static function buildPayload(Store $store, Collection $coupons, bool $openAffiliateOnCopy = false): array
    {
        $affiliateUrl = (string) ($store->affiliate_url ?: $store->shopUrl());

        return [
            'storeName' => $store->name,
            'storeSlug' => $store->slug,
            'storeLogo' => $store->logoUrl(),
            'affiliateUrl' => $affiliateUrl,
            'openAffiliateOnCopy' => $openAffiliateOnCopy,
            'coupons' => $coupons->map(fn (Coupon $coupon) => [
                'title' => $coupon->title,
                'discount' => $coupon->discountLabel(),
                'hasCode' => filled($coupon->code),
                'codeMasked' => filled($coupon->code) ? $coupon->maskedCodeParts() : null,
                'expires' => $coupon->expiresLabel(),
                'goUrl' => route('coupons.go', $coupon->slug),
                'affiliateUrl' => $coupon->affiliateClickUrl(),
                'revealUrl' => route('coupons.reveal', $coupon->slug),
            ])->values()->all(),
        ];
    }
}
