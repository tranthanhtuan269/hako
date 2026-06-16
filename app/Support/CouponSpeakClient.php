<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        $apiUrl = $this->apiBaseUrl();

        if ($apiUrl === '') {
            return [];
        }

        try {
            $response = $this->jsonClient()
                ->timeout(12)
                ->get($apiUrl, [
                    'site' => $this->siteSlug(),
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

    /**
     * @param  list<Coupon>  $coupons
     * @return array<string, mixed>|null
     */
    public function syncImportedStore(Store $store, array $coupons, string $affiliateUrl): ?array
    {
        $syncUrl = $this->syncUrl();

        if ($syncUrl === '' || $coupons === []) {
            return null;
        }

        $domain = $this->storeDomain($store, $affiliateUrl);

        if ($domain === null || $domain === '') {
            return null;
        }

        $payload = [
            'store' => [
                'domain' => $domain,
                'slug' => $store->slug,
                'name' => $store->name,
                'affiliate_url' => $affiliateUrl,
            ],
            'sync_mode' => 'replace',
            'coupons' => collect($coupons)
                ->map(fn (Coupon $coupon) => $this->mapCouponForSync($coupon, $affiliateUrl))
                ->values()
                ->all(),
        ];

        try {
            $response = $this->jsonClient()
                ->timeout(20)
                ->withOptions(['allow_redirects' => false])
                ->post($syncUrl, $payload);

            if (! $response->successful()) {
                Log::warning('CouponSpeak sync failed', [
                    'status' => $response->status(),
                    'url' => $syncUrl,
                    'body' => Str::limit($response->body(), 500),
                    'store' => $store->slug,
                ]);

                return null;
            }

            $body = $response->json();

            if (! is_array($body) || ($body['success'] ?? false) !== true) {
                Log::warning('CouponSpeak sync rejected', [
                    'url' => $syncUrl,
                    'body' => Str::limit($response->body(), 500),
                    'store' => $store->slug,
                ]);

                return null;
            }

            if (! isset($body['stats'], $body['request_id']) || isset($body['sample'])) {
                Log::warning('CouponSpeak sync unexpected response (wrong endpoint or HTTP redirect?)', [
                    'url' => $syncUrl,
                    'body' => Str::limit($response->body(), 500),
                    'store' => $store->slug,
                ]);

                return null;
            }

            return $body;
        } catch (\Throwable $exception) {
            Log::warning('CouponSpeak sync exception', [
                'message' => $exception->getMessage(),
                'store' => $store->slug,
            ]);

            return null;
        }
    }

    private function siteSlug(): string
    {
        $site = trim((string) config('services.couponspeak.site', ''));

        if ($site !== '') {
            return $site;
        }

        $domain = (string) config('site.domain', '');

        return strtolower(explode('.', $domain)[0] ?? $domain);
    }

    private function syncUrl(): string
    {
        $syncUrl = trim((string) config('services.couponspeak.sync_url', ''));

        if ($syncUrl !== '') {
            return $this->appendSiteQuery($this->forceHttps($syncUrl));
        }

        $apiUrl = $this->apiBaseUrl();

        if ($apiUrl === '') {
            return '';
        }

        return $this->appendSiteQuery($apiUrl . '/import');
    }

    private function apiBaseUrl(): string
    {
        return rtrim($this->forceHttps((string) config('services.couponspeak.url', '')), '/');
    }

    private function forceHttps(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        return (string) preg_replace('#^http://#i', 'https://', $url);
    }

    private function jsonClient(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::acceptJson()->asJson();
    }

    private function appendSiteQuery(string $url): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . 'site=' . urlencode($this->siteSlug());
    }

    private function storeDomain(Store $store, string $affiliateUrl): ?string
    {
        if (filled($store->website)) {
            return $this->hostFromUrl($store->website);
        }

        return $this->hostFromUrl($affiliateUrl);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCouponForSync(Coupon $coupon, string $affiliateUrl): array
    {
        $description = HtmlCleaner::plainText($coupon->description);
        $hasCode = filled($coupon->code);

        $payload = [
            'offer_id' => 'ext-' . $coupon->id,
            'discount_label' => Str::limit($coupon->title, 60, ''),
            'title' => $description !== '' ? $description : $coupon->title,
            'coupon_type' => $hasCode ? 'code' : 'deal',
            'affiliate_url' => $affiliateUrl,
            'button_text' => $hasCode ? 'Get Code' : 'Get Deal',
        ];

        if ($hasCode) {
            $payload['coupon_code'] = $coupon->code;
            $payload['is_verified'] = true;
        }

        return $payload;
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
