<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

final class CouponSpeakClient
{
    /**
     * @return list<array{code: ?string, title: string, description: ?string, coupon_type: ?string, discount_label: ?string}>
     */
    public function fetchOffersForAffiliateUrl(string $affiliateUrl): array
    {
        $storeQuery = $this->hostFromUrl($affiliateUrl);

        if ($storeQuery === null || $storeQuery === '') {
            return [];
        }

        return $this->fetchOffersByStore($storeQuery);
    }

    /**
     * @return list<array{code: ?string, title: string, description: ?string, coupon_type: ?string, discount_label: ?string}>
     */
    public function fetchOffersByStore(string $storeQuery): array
    {
        $apiUrl = rtrim((string) config('services.couponspeak.url'), '/');

        if ($apiUrl === '') {
            return [];
        }

        try {
            $response = Http::timeout(12)
                ->acceptJson()
                ->get($apiUrl, [
                    'site' => (string) config('services.couponspeak.site', ''),
                    'store' => $storeQuery,
                    'limit' => (int) config('services.couponspeak.limit', 20),
                ]);

            if (! $response->successful()) {
                return [];
            }

            $coupons = $response->json('coupons');

            if (! is_array($coupons)) {
                return [];
            }

            return $this->mapCouponsToOffers($coupons);
        } catch (\Throwable) {
            return [];
        }
    }

    public function hostFromUrl(string $url): ?string
    {
        $host = parse_url(trim($url), PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return preg_replace('/^www\./', '', strtolower($host));
    }

    /**
     * @param  list<array<string, mixed>>  $coupons
     * @return list<array{code: ?string, title: string, description: ?string, coupon_type: ?string, discount_label: ?string}>
     */
    private function mapCouponsToOffers(array $coupons): array
    {
        $offers = [];

        foreach ($coupons as $coupon) {
            if (! is_array($coupon)) {
                continue;
            }

            $discount = trim((string) ($coupon['discount_label'] ?? ''));
            $title = trim((string) ($coupon['title'] ?? ''));
            $code = filled($coupon['coupon_code'] ?? null) ? trim((string) $coupon['coupon_code']) : null;
            $type = filled($coupon['coupon_type'] ?? null) ? trim((string) $coupon['coupon_type']) : null;

            if ($title === '' && $discount === '') {
                continue;
            }

            $offers[] = [
                'code' => $code,
                'title' => $discount !== '' ? $discount : $title,
                'description' => $title !== '' ? $title : null,
                'coupon_type' => $type,
                'discount_label' => $discount !== '' ? $discount : null,
            ];
        }

        return $offers;
    }
}
