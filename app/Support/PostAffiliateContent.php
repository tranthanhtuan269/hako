<?php

namespace App\Support;

use App\Models\Store;

final class PostAffiliateContent
{
    /**
     * Query keys copied from affiliate_url onto every outbound article link.
     *
     * @var list<string>
     */
    private const MERCHANT_TRACKING_KEYS = ['ref', 'aff', 'affiliate', 'affiliate_id'];

    /**
     * @var list<string>
     */
    private const SOCIAL_SHARE_HOSTS = [
        'facebook.com',
        'twitter.com',
        'x.com',
        'linkedin.com',
        'pinterest.com',
        'instagram.com',
        'wa.me',
        'whatsapp.com',
        't.me',
        'telegram.me',
        'youtube.com',
        'youtu.be',
    ];

    /**
     * @var list<string>
     */
    private const AFFILIATE_NETWORK_HOSTS = [
        'awin1.com',
        'linksynergy.com',
        'rakuten.com',
        'viglink.com',
        'skimresources.com',
        'shareasale.com',
        'impact.com',
        'impactradius.com',
        'pjtra.com',
        'clickbank.net',
        'anrdoezrs.net',
        'dpbolvw.net',
        'jdoqocy.com',
        'tkqlhce.com',
        'kqzyfj.com',
        'bit.ly',
        't.co',
        'amzn.to',
        'amazon.to',
    ];

    public static function embed(?string $html, ?Store $store): string
    {
        $html = (string) $html;

        if ($html === '' || $store === null || ! filled($store->affiliate_url)) {
            return $html;
        }

        $affiliateUrl = (string) $store->affiliate_url;
        $html = self::rewriteMerchantAnchors($html, $store, $affiliateUrl);

        if (! str_contains($html, $affiliateUrl) && ! self::htmlHasMerchantTracking($html, $affiliateUrl)) {
            $html = self::injectAffiliateParagraph($html, $store->name, $affiliateUrl);
        }

        return $html;
    }

    /**
     * Affiliate href for a shopping CTA. Deep-links stay wrapped when the network has a destination param.
     */
    public static function trackedHref(Store $store, ?string $destination = null): string
    {
        $affiliateUrl = trim((string) $store->affiliate_url);

        if ($affiliateUrl === '') {
            return filled($destination) ? (string) $destination : '';
        }

        $destination = filled($destination) ? trim((string) $destination) : '';

        if ($destination === '' || self::urlsMatch($destination, $affiliateUrl)) {
            return $affiliateUrl;
        }

        $wrapped = self::swapAffiliateDestination($affiliateUrl, $destination);

        if ($wrapped !== null) {
            return $wrapped;
        }

        $tracking = self::merchantTrackingParams($affiliateUrl);

        if ($tracking !== []) {
            return self::mergeQueryParams($destination, $tracking) ?? $affiliateUrl;
        }

        return $affiliateUrl;
    }

    private static function rewriteMerchantAnchors(string $html, Store $store, string $affiliateUrl): string
    {
        $merchantHosts = self::merchantHosts($store);
        $ownHosts = self::ownHosts();
        $tracking = self::merchantTrackingParams($affiliateUrl);

        if ($merchantHosts === [] && $tracking === [] && self::swapAffiliateDestination($affiliateUrl, 'https://example.com') === null) {
            return $html;
        }

        return preg_replace_callback(
            '/<a\s+([^>]*?)href=(["\'])([^"\']+)\2([^>]*)>/i',
            function (array $matches) use ($store, $affiliateUrl, $merchantHosts, $ownHosts): string {
                $href = html_entity_decode($matches[3], ENT_QUOTES | ENT_HTML5);

                if (! self::shouldRewriteHref($href, $affiliateUrl, $merchantHosts, $ownHosts, $store)) {
                    return $matches[0];
                }

                $destination = $href;

                if (str_starts_with($href, '/') && ! str_starts_with($href, '//')) {
                    $destination = self::absoluteMerchantUrl($href, $store) ?? $href;
                } elseif (str_starts_with($href, '//')) {
                    $destination = 'https:'.$href;
                }

                $tracked = self::trackedHref($store, $destination);
                $before = $matches[1];
                $after = $matches[4];
                $attrs = trim($before.' '.$after);

                if (! preg_match('/\srel=/i', $attrs)) {
                    $attrs .= ' rel="nofollow sponsored"';
                } elseif (! preg_match('/\b(sponsored|nofollow)\b/i', $attrs)) {
                    $attrs = preg_replace('/\srel=(["\'])([^"\']*)\1/i', ' rel="$2 nofollow sponsored"', $attrs) ?? $attrs;
                }

                if (preg_match('/\starget=/i', $attrs)) {
                    $attrs = preg_replace('/\starget=(["\'])[^"\']*\1/i', ' target="_blank"', $attrs) ?? $attrs;
                } else {
                    $attrs .= ' target="_blank"';
                }

                return '<a'.self::normalizeAttrSpacing($attrs).' href="'.e($tracked).'">';
            },
            $html
        ) ?? $html;
    }

    /**
     * @param  list<string>  $merchantHosts
     * @param  list<string>  $ownHosts
     */
    private static function shouldRewriteHref(
        string $href,
        string $affiliateUrl,
        array $merchantHosts,
        array $ownHosts,
        Store $store,
    ): bool {
        $href = trim($href);

        if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) {
            return false;
        }

        if (self::urlsMatch($href, $affiliateUrl)) {
            return false;
        }

        if (str_starts_with($href, '/') && ! str_starts_with($href, '//')) {
            if (preg_match('#^/(stores|blog|coupons|categories|authors|search|pages|login|register|about)(/|$)#i', $href)) {
                return false;
            }

            $href = self::absoluteMerchantUrl($href, $store) ?? '';

            if ($href === '') {
                return false;
            }
        }

        if (! preg_match('#^https?://#i', $href) && ! str_starts_with($href, '//')) {
            return false;
        }

        if (str_starts_with($href, '//')) {
            $href = 'https:'.$href;
        }

        $host = Store::normalizeMerchantHost($href);

        if ($host === null || in_array($host, $ownHosts, true) || self::isAffiliateNetworkHost($host) || self::isSocialShareHost($host)) {
            return false;
        }

        $tracking = self::merchantTrackingParams($affiliateUrl);

        if ($tracking !== []) {
            $merged = self::mergeQueryParams($href, $tracking);

            return $merged !== null && ! self::urlsMatch($href, $merged);
        }

        foreach ($merchantHosts as $merchantHost) {
            if ($host === $merchantHost || str_ends_with($host, '.'.$merchantHost)) {
                return true;
            }
        }

        return false;
    }

    private static function absoluteMerchantUrl(string $path, Store $store): ?string
    {
        $base = trim((string) ($store->website ?: ''));

        if ($base === '' || ! preg_match('#^https?://#i', $base)) {
            return null;
        }

        return rtrim($base, '/').'/'.ltrim($path, '/');
    }

    /** @return list<string> */
    private static function merchantHosts(Store $store): array
    {
        $hosts = array_filter([
            $store->domain(),
            Store::normalizeMerchantHost($store->website),
            self::destinationMerchantHost((string) $store->affiliate_url),
        ]);

        return array_values(array_unique($hosts));
    }

    private static function destinationMerchantHost(string $affiliateUrl): ?string
    {
        $destination = self::unwrapDestination($affiliateUrl);
        $host = Store::normalizeMerchantHost($destination);

        if ($host === null || self::isAffiliateNetworkHost($host)) {
            return null;
        }

        return $host;
    }

    /** @return list<string> */
    private static function ownHosts(): array
    {
        try {
            if (! function_exists('app') || ! app()->bound('config')) {
                return [];
            }

            $hosts = array_filter([
                Store::normalizeMerchantHost((string) config('app.url')),
                Store::normalizeMerchantHost((string) config('site.url')),
            ]);
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_unique($hosts));
    }

    private static function unwrapDestination(string $url): string
    {
        $parsed = parse_url($url);

        if (empty($parsed['query'])) {
            return $url;
        }

        parse_str($parsed['query'], $params);

        foreach (['url', 'u', 'dest', 'destination', 'redirect', 'murl', 'ued', 'target', 'link', 'r'] as $key) {
            if (empty($params[$key]) || ! is_string($params[$key])) {
                continue;
            }

            $candidate = urldecode($params[$key]);

            if (filter_var($candidate, FILTER_VALIDATE_URL)) {
                return $candidate;
            }
        }

        return $url;
    }

    private static function swapAffiliateDestination(string $affiliateUrl, string $destination): ?string
    {
        $parsed = parse_url($affiliateUrl);

        if (empty($parsed['host']) || empty($parsed['query'])) {
            return null;
        }

        parse_str($parsed['query'], $params);
        $replaced = false;

        foreach (['url', 'u', 'dest', 'destination', 'redirect', 'murl', 'ued', 'target', 'link', 'r'] as $key) {
            if (empty($params[$key]) || ! is_string($params[$key])) {
                continue;
            }

            $candidate = urldecode($params[$key]);

            if (! filter_var($candidate, FILTER_VALIDATE_URL)) {
                continue;
            }

            $params[$key] = $destination;
            $replaced = true;
            break;
        }

        if (! $replaced) {
            return null;
        }

        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'];
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
        $path = $parsed['path'] ?? '';
        $fragment = isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

        return $scheme.'://'.$host.$port.$path.'?'.http_build_query($params).$fragment;
    }

    /**
     * @return array<string, string>
     */
    private static function merchantTrackingParams(string $affiliateUrl): array
    {
        $parsed = parse_url($affiliateUrl);

        if (empty($parsed['query'])) {
            return [];
        }

        parse_str($parsed['query'], $params);
        $tracking = [];

        foreach (self::MERCHANT_TRACKING_KEYS as $key) {
            if (! isset($params[$key]) || ! is_string($params[$key]) || trim($params[$key]) === '') {
                continue;
            }

            $tracking[$key] = trim($params[$key]);
        }

        return $tracking;
    }

    private static function htmlHasMerchantTracking(string $html, string $affiliateUrl): bool
    {
        $tracking = self::merchantTrackingParams($affiliateUrl);

        if (isset($tracking['ref']) && str_contains($html, 'ref='.rawurlencode($tracking['ref']))) {
            return true;
        }

        if (isset($tracking['ref']) && str_contains($html, 'ref='.$tracking['ref'])) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, string>  $params
     */
    private static function mergeQueryParams(string $url, array $params): ?string
    {
        if ($params === []) {
            return $url;
        }

        $parsed = parse_url($url);

        if (empty($parsed['host'])) {
            return null;
        }

        $existing = [];

        if (! empty($parsed['query'])) {
            parse_str($parsed['query'], $existing);
        }

        $query = array_merge($existing, $params);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'];
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
        $path = $parsed['path'] ?? '';
        $fragment = isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

        return $scheme.'://'.$host.$port.$path.'?'.http_build_query($query).$fragment;
    }

    private static function isSocialShareHost(string $host): bool
    {
        foreach (self::SOCIAL_SHARE_HOSTS as $needle) {
            if ($host === $needle || str_ends_with($host, '.'.$needle)) {
                return true;
            }
        }

        return false;
    }

    private static function isAffiliateNetworkHost(string $host): bool
    {
        foreach (self::AFFILIATE_NETWORK_HOSTS as $needle) {
            if ($host === $needle || str_ends_with($host, '.'.$needle)) {
                return true;
            }
        }

        return false;
    }

    private static function urlsMatch(string $left, string $right): bool
    {
        return rtrim(strtolower($left), '/') === rtrim(strtolower($right), '/');
    }

    private static function normalizeAttrSpacing(string $attrs): string
    {
        $attrs = trim($attrs);

        return $attrs === '' ? '' : ' '.$attrs;
    }

    private static function injectAffiliateParagraph(string $html, string $storeName, string $affiliateUrl): string
    {
        $paragraph = '<p>Shop at <strong>'.e($storeName).'</strong> through our '
            .'<a href="'.e($affiliateUrl).'" rel="nofollow sponsored" target="_blank">affiliate link</a>.</p>';

        if (preg_match('/<\/p>/i', $html, $match, PREG_OFFSET_CAPTURE)) {
            $position = $match[0][1] + strlen($match[0][0]);

            return substr($html, 0, $position).$paragraph.substr($html, $position);
        }

        return $paragraph.$html;
    }
}
