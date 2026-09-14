<?php

namespace App\Support;

use App\Models\Store;

final class PostAffiliateContent
{
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

        if (! str_contains($html, $affiliateUrl)) {
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

        return self::swapAffiliateDestination($affiliateUrl, $destination) ?? $affiliateUrl;
    }

    private static function rewriteMerchantAnchors(string $html, Store $store, string $affiliateUrl): string
    {
        $merchantHosts = self::merchantHosts($store);

        if ($merchantHosts === []) {
            return $html;
        }

        $ownHosts = self::ownHosts();

        return preg_replace_callback(
            '/<a\s+([^>]*?)href=(["\'])([^"\']+)\2([^>]*)>/i',
            function (array $matches) use ($store, $affiliateUrl, $merchantHosts, $ownHosts): string {
                $href = html_entity_decode($matches[3], ENT_QUOTES | ENT_HTML5);

                if (! self::shouldRewriteHref($href, $affiliateUrl, $merchantHosts, $ownHosts, $store)) {
                    return $matches[0];
                }

                $tracked = self::trackedHref($store, $href);
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
            if (preg_match('#^/(stores|blog|coupons|categories|authors|search)(/|$)#i', $href)) {
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

        if ($host === null || in_array($host, $ownHosts, true) || self::isAffiliateNetworkHost($host)) {
            return false;
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
