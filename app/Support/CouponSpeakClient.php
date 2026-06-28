<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CouponSpeakClient
{
    /**
     * @return list<array{code: ?string, title: string, description: ?string, coupon_type: ?string, discount_label: ?string, expires_at: ?string}>
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
     * @return list<array{code: ?string, title: string, description: ?string, coupon_type: ?string, discount_label: ?string, expires_at: ?string}>
     */
    public function fetchOffersByStore(string $storeQuery): array
    {
        return $this->fetchStoreBundle($storeQuery)['offers'];
    }

    /**
     * Coupons + optional cached store profile from scan API (?profile=1).
     *
     * @return array{
     *     offers: list<array{code: ?string, title: string, description: ?string, coupon_type: ?string, discount_label: ?string, expires_at: ?string}>,
     *     store_profile: ?array<string, mixed>,
     *     scan_logo: ?string,
     *     profile_cached: bool
     * }
     */
    public function fetchStoreBundle(string $storeQuery): array
    {
        $empty = [
            'offers' => [],
            'store_profile' => null,
            'scan_logo' => null,
            'profile_cached' => false,
        ];

        $storeQuery = trim($storeQuery);

        if ($storeQuery === '' || $this->apiBaseUrl() === '') {
            return $empty;
        }

        try {
            $response = $this->jsonClient()
                ->timeout(12)
                ->get($this->apiBaseUrl(), [
                    'site' => $this->siteSlug(),
                    'store' => $storeQuery,
                    'limit' => (int) config('services.couponspeak.limit', 20),
                    'profile' => 1,
                ]);

            if (! $response->successful()) {
                return $empty;
            }

            $body = $response->json();
            $coupons = is_array($body['coupons'] ?? null) ? $body['coupons'] : [];
            $profile = is_array($body['store_profile'] ?? null) ? $body['store_profile'] : [];
            $profile = $this->mergeApiStoreMetaIntoProfile($profile, $body);
            $scanLogo = $this->resolveScanLogo($body, $profile);

            return [
                'offers' => $this->mapCouponsToOffers($coupons),
                'store_profile' => $profile !== [] ? $profile : null,
                'scan_logo' => $scanLogo,
                'profile_cached' => (bool) ($body['profile_cached'] ?? ! empty($profile['detected_at'] ?? null)),
            ];
        } catch (\Throwable) {
            return $empty;
        }
    }

    public function profileIsUsable(?array $profile): bool
    {
        return is_array($profile) && filled($profile['name'] ?? null);
    }

    /**
     * Logo from scan /coupons response. Null means caller should use the local detect flow.
     */
    public function resolveScanLogo(array $body, ?array $profile = null): ?string
    {
        if (array_key_exists('logo', $body)) {
            if (! filled($body['logo'])) {
                return null;
            }

            return $this->resolveScanAssetUrl((string) $body['logo']);
        }

        $profileLogo = is_array($profile) ? ($profile['logo'] ?? null) : null;

        if (! filled($profileLogo)) {
            return null;
        }

        return $this->resolveScanAssetUrl((string) $profileLogo);
    }

    public function resolveScanAssetUrl(string $url): string
    {
        $url = trim($url);

        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        if (str_starts_with($url, '/')) {
            return rtrim($this->scanWebBaseUrl(), '/') . $url;
        }

        return $url;
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function mergeApiStoreMetaIntoProfile(array $profile, array $body): array
    {
        if (filled($body['store_name'] ?? null)) {
            $profile['name'] = trim((string) $body['store_name']);
        }

        if (filled($body['store_slug'] ?? null)) {
            $profile['slug'] = trim((string) $body['store_slug']);
        }

        if (filled($body['category_name'] ?? null)) {
            $profile['category_name'] = trim((string) $body['category_name']);
        }

        if (array_key_exists('logo', $body)) {
            $profile['logo'] = filled($body['logo'])
                ? $this->resolveScanAssetUrl((string) $body['logo'])
                : null;
        }

        return $profile;
    }

    private function scanWebBaseUrl(): string
    {
        $api = $this->apiBaseUrl();

        return (string) preg_replace('#/api(?:/coupons)?/?$#', '', $api);
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    public function merchantFromProfile(array $profile, string $affiliateUrl): array
    {
        return [
            'affiliate_url' => $affiliateUrl,
            'final_url' => $profile['final_url'] ?? $profile['website'] ?? $affiliateUrl,
            'domain' => $profile['domain'] ?? $this->hostFromUrl($affiliateUrl),
            'name' => trim((string) ($profile['name'] ?? '')),
            'logo' => $profile['logo'] ?? null,
            'page_title' => $profile['page_title'] ?? $profile['meta_title'] ?? null,
            'meta_description' => $profile['meta_description'] ?? null,
            'category_name' => filled($profile['category_name'] ?? null)
                ? trim((string) $profile['category_name'])
                : null,
            'faqs' => is_array($profile['faqs'] ?? null) ? $profile['faqs'] : [],
            'products' => is_array($profile['products'] ?? null) ? $profile['products'] : [],
        ];
    }

    /**
     * @param  list<Coupon>  $coupons
     * @return array<string, mixed>|null
     */
    /**
     * @param  array<string, mixed>  $detectContext
     */
    public function syncImportedStore(
        Store $store,
        array $coupons,
        string $affiliateUrl,
        array $detectContext = []
    ): ?array {
        $syncUrl = $this->syncUrl();

        if ($syncUrl === '' || $coupons === []) {
            return null;
        }

        $domain = $this->storeDomain($store, $affiliateUrl);

        if ($domain === null || $domain === '') {
            return null;
        }

        $detect = array_filter([
            'page_title' => $detectContext['page_title'] ?? null,
            'final_url' => $detectContext['final_url'] ?? null,
            'faqs' => $detectContext['faqs'] ?? null,
            'products' => $detectContext['products'] ?? null,
            'generated_blog' => $detectContext['generated_blog'] ?? null,
        ], fn ($value) => $value !== null && $value !== []);

        $payload = [
            'store' => array_filter([
                'domain' => $domain,
                'slug' => $store->slug,
                'name' => $store->name,
                'affiliate_url' => $affiliateUrl,
                'website' => $detectContext['website'] ?? $store->website,
                'logo_url' => $detectContext['logo'] ?? null,
                'meta_description' => $detectContext['meta_description'] ?? null,
                'category_name' => $detectContext['category_name'] ?? null,
            ], fn ($value) => filled($value)),
            'detect' => $detect !== [] ? $detect : null,
            'sync_mode' => 'replace',
            'coupons' => collect($coupons)
                ->map(fn (Coupon $coupon) => $this->mapCouponForSync($coupon, $affiliateUrl))
                ->values()
                ->all(),
        ];

        if ($payload['detect'] === null) {
            unset($payload['detect']);
        }

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

    private function apiBaseUrl(): string
    {
        return $this->normalizeApiUrl(trim((string) config('services.couponspeak.url', '')));
    }

    private function syncUrl(): string
    {
        $syncUrl = trim((string) config('services.couponspeak.sync_url', ''));

        if ($syncUrl !== '') {
            return $this->appendSiteQuery($this->normalizeApiUrl($syncUrl));
        }

        $apiUrl = $this->apiBaseUrl();

        if ($apiUrl === '') {
            return '';
        }

        return $this->appendSiteQuery($apiUrl . '/import');
    }

    private function normalizeApiUrl(string $url): string
    {
        $url = rtrim($url, '/');

        if ($url === '') {
            return '';
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return $url;
        }

        $host = strtolower($host);

        if ($host === 'localhost' || str_ends_with($host, '.test') || str_ends_with($host, '.local')) {
            return (string) preg_replace('#^https://#i', 'http://', $url);
        }

        return (string) preg_replace('#^http://#i', 'https://', $url);
    }

    private function jsonClient(): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::acceptJson()->asJson();

        if ($this->shouldDisableTlsVerify($this->apiBaseUrl())) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    private function shouldDisableTlsVerify(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);

        return $host === 'localhost'
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local');
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

        if ($coupon->expires_at) {
            $payload['expires_at'] = $coupon->expires_at->format('Y-m-d H:i:s');
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
     * @return list<array{code: ?string, title: string, description: ?string, coupon_type: ?string, discount_label: ?string, expires_at: ?string}>
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
                'expires_at' => $this->formatExpiresForImportForm($coupon['expires_at'] ?? null),
            ];
        }

        return $offers;
    }

    private function formatExpiresForImportForm(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d\TH:i');
        } catch (\Throwable) {
            return null;
        }
    }
}
