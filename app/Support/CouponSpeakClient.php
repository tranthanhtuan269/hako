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
     * Affiliate signup registry from Scan API.
     *
     * @return array{
     *     projects: list<array{id:int,project:?string,domain_link:?string,signup_link:string,category:?string,created_at:string}>,
     *     pagination: array{page:int,per_page:int,total:int,total_pages:int},
     *     error: ?string
     * }
     */
    public function fetchAffiliateSignups(int $page = 1, int $limit = 25, ?string $search = null): array
    {
        $empty = [
            'projects' => [],
            'pagination' => [
                'page' => max(1, $page),
                'per_page' => max(1, min(100, $limit)),
                'total' => 0,
                'total_pages' => 0,
            ],
            'error' => null,
        ];

        $apiUrl = $this->normalizeApiUrl(SiteIntegrations::scanAffiliateSignupsApiUrl());

        if ($apiUrl === '') {
            $empty['error'] = 'Affiliate signups API URL is not configured. Set it in Admin → Integrations.';

            return $empty;
        }

        $page = max(1, $page);
        $limit = max(1, min(100, $limit));
        $site = $this->siteSlug();

        if ($site === '') {
            $empty['error'] = 'Could not determine Scan site slug from domain. Set it in Admin → Integrations.';

            return $empty;
        }

        $query = [
            'site' => $site,
            'page' => $page,
            'limit' => $limit,
        ];

        if (filled($search)) {
            $query['q'] = trim($search);
        }

        try {
            $response = $this->jsonClientForUrl($apiUrl)
                ->timeout(12)
                ->get($apiUrl, $query);

            if (! $response->successful()) {
                $empty['error'] = $this->formatScanApiFailure($response, $site);

                return $empty;
            }

            $body = $response->json();

            if (! is_array($body) || ($body['success'] ?? false) !== true) {
                $empty['error'] = is_array($body) && filled($body['error'] ?? null)
                    ? (string) $body['error']
                    : 'Scan API returned an unexpected response.';

                return $empty;
            }

            $projects = is_array($body['projects'] ?? null) ? $body['projects'] : [];

            return [
                'projects' => collect($projects)
                    ->filter(fn ($row) => is_array($row) && filled($row['signup_link'] ?? null))
                    ->map(fn (array $row) => [
                        'id' => (int) ($row['id'] ?? 0),
                        'project' => filled($row['project'] ?? null) ? trim((string) $row['project']) : null,
                        'domain_link' => filled($row['domain_link'] ?? null) ? trim((string) $row['domain_link']) : null,
                        'signup_link' => trim((string) $row['signup_link']),
                        'category' => filled($row['category'] ?? null) ? trim((string) $row['category']) : null,
                        'created_at' => filled($row['created_at'] ?? null) ? trim((string) $row['created_at']) : null,
                    ])
                    ->values()
                    ->all(),
                'pagination' => [
                    'page' => (int) ($body['page'] ?? $page),
                    'per_page' => (int) ($body['limit'] ?? $limit),
                    'total' => (int) ($body['total'] ?? 0),
                    'total_pages' => (int) ($body['total_pages'] ?? 0),
                ],
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            $empty['error'] = 'Could not reach Scan API: ' . $exception->getMessage();

            return $empty;
        }
    }

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
                    'limit' => SiteIntegrations::scanApiLimit(),
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
            $profile['name'] = HtmlCleaner::normalizePlainText((string) $body['store_name']);
        }

        if (filled($body['store_slug'] ?? null)) {
            $profile['slug'] = trim((string) $body['store_slug']);
        }

        if (filled($body['category_name'] ?? null)) {
            $profile['category_name'] = HtmlCleaner::normalizePlainText((string) $body['category_name']);
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
            'name' => HtmlCleaner::normalizePlainText((string) ($profile['name'] ?? '')),
            'logo' => $profile['logo'] ?? null,
            'page_title' => $profile['page_title'] ?? $profile['meta_title'] ?? null,
            'meta_description' => $profile['meta_description'] ?? null,
            'category_name' => filled($profile['category_name'] ?? null)
                ? HtmlCleaner::normalizePlainText((string) $profile['category_name'])
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
        return SiteIntegrations::scanSite();
    }

    private function apiBaseUrl(): string
    {
        return $this->normalizeApiUrl(SiteIntegrations::scanApiUrl());
    }

    private function syncUrl(): string
    {
        return $this->appendSiteQuery($this->normalizeApiUrl(SiteIntegrations::scanSyncUrl()));
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
        return $this->jsonClientForUrl($this->apiBaseUrl());
    }

    private function jsonClientForUrl(string $url): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::acceptJson()->asJson();

        if ($this->shouldDisableTlsVerify($url)) {
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

    private function appendSiteQuery(string $url, ?string $site = null): string
    {
        $site = strtolower(trim((string) ($site ?? $this->siteSlug())));

        if ($site === '') {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . 'site=' . urlencode($site);
    }

    private function formatScanApiFailure(\Illuminate\Http\Client\Response $response, string $site = ''): string
    {
        $status = $response->status();
        $body = $response->json();

        if (is_array($body) && filled($body['error'] ?? null)) {
            $message = (string) $body['error'];

            if ($status === 403 && str_contains(strtolower($message), 'not registered') && $site !== '') {
                $message .= ' Register site "' . $site . '" in Scan sitename table, or change Scan site slug in Integrations.';
            }

            if ($status === 400 && str_contains(strtolower($message), 'missing store')) {
                $message .= ' Check Affiliate signups API URL — it must be /api/affiliate-signups, not /api/coupons.';
            }

            return $message . ' (HTTP ' . $status . ')';
        }

        if ($status === 404) {
            return 'Scan API endpoint /api/affiliate-signups not found. Deploy latest Scan code to the server. (HTTP 404)';
        }

        $snippet = trim(Str::limit(strip_tags($response->body()), 160));

        if ($snippet !== '') {
            return 'Scan API error (HTTP ' . $status . '): ' . $snippet;
        }

        return 'Scan API returned HTTP ' . $status . '.';
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
            'button_text' => $hasCode ? 'Copy Code' : 'Get Deal',
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

            $discount = HtmlCleaner::normalizePlainText((string) ($coupon['discount_label'] ?? ''));
            $title = HtmlCleaner::normalizePlainText((string) ($coupon['title'] ?? ''));
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
