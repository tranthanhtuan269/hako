<?php

namespace App\Support;

use App\Models\Store;

class DynamicCouponContent
{
    public const PLACEHOLDER = '[store_coupons]';

    public static function placeholderMarkup(string $storeName = ''): string
    {
        $heading = filled($storeName)
            ? 'Current '.e($storeName).' Coupon Codes &amp; Deals'
            : 'Current Coupon Codes &amp; Deals';

        return '<h2>'.$heading.'</h2>'."\n"
            .'<p>Live offers update automatically when coupons change on '.e((string) config('site.name')).'.</p>'."\n"
            .self::PLACEHOLDER;
    }

    public static function ensurePlaceholder(string $html, string $storeName = ''): string
    {
        $html = trim($html);

        if ($html === '') {
            return self::placeholderMarkup($storeName);
        }

        if (str_contains($html, self::PLACEHOLDER)) {
            return $html;
        }

        $stripped = self::stripLegacyOffersSection($html);

        return rtrim($stripped)."\n\n".self::placeholderMarkup($storeName);
    }

    public static function expand(string $html, ?Store $store, ?int $limit = null, bool $showMoreLink = true): string
    {
        if ($store === null || ! $store->exists) {
            return str_replace(self::PLACEHOLDER, '', $html);
        }

        $limit = max(1, $limit ?? min(8, $store->storeCouponLimit()));
        $coupons = $store->publicStoreCouponsQuery()
            ->with(['store.category'])
            ->limit($limit)
            ->get();

        $block = view('partials.embedded-coupon-list', [
            'store' => $store,
            'coupons' => $coupons,
            'showMoreLink' => $showMoreLink,
        ])->render();

        if (str_contains($html, self::PLACEHOLDER)) {
            return str_replace(self::PLACEHOLDER, $block, $html);
        }

        $replaced = self::replaceLegacyOffersSection($html, $block);
        if ($replaced !== null) {
            return $replaced;
        }

        return rtrim($html)."\n\n".$block;
    }

    public static function stripLegacyOffersSection(string $html): string
    {
        $replaced = self::replaceLegacyOffersSection($html, '');

        return $replaced ?? $html;
    }

    /**
     * Replace a baked "Current … Coupon Codes & Deals" section through the next H2 (or end).
     */
    private static function replaceLegacyOffersSection(string $html, string $replacement): ?string
    {
        $pattern = '/<h2[^>]*>\s*Current\s+.*?Coupon Codes?\s*(?:&amp;|&)\s*Deals.*?<\/h2>.*?(?=(?:<h2\b)|$)/is';

        if (! preg_match($pattern, $html)) {
            return null;
        }

        $out = preg_replace($pattern, $replacement, $html, 1);

        return is_string($out) ? $out : null;
    }
}
