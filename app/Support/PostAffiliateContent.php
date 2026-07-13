<?php

namespace App\Support;

use App\Models\Store;

final class PostAffiliateContent
{
    public static function embed(?string $html, ?Store $store): string
    {
        $html = (string) $html;

        if ($html === '' || $store === null || ! filled($store->affiliate_url)) {
            return $html;
        }

        $affiliateUrl = (string) $store->affiliate_url;
        $storePaths = self::storeLinkTargets($store);

        foreach ($storePaths as $target) {
            $html = self::replaceAnchorHref($html, $target, $affiliateUrl);
        }

        if (! str_contains($html, $affiliateUrl)) {
            $html = self::injectAffiliateParagraph($html, $store->name, $affiliateUrl);
        }

        return $html;
    }

    /** @return list<string> */
    private static function storeLinkTargets(Store $store): array
    {
        $slug = $store->slug;
        $paths = [
            '/stores/'.$slug,
            route('stores.show', $store->slug, false),
            route('stores.show', $store->slug),
            url('/stores/'.$slug),
        ];

        return array_values(array_unique(array_filter($paths)));
    }

    private static function replaceAnchorHref(string $html, string $target, string $affiliateUrl): string
    {
        $pattern = '/<a([^>]*)\shref=(["\'])'.preg_quote($target, '/').'\2([^>]*)>/i';

        return preg_replace_callback($pattern, function (array $matches) use ($affiliateUrl) {
            $before = $matches[1];
            $after = $matches[3];
            $attrs = trim($before.' '.$after);

            if (! preg_match('/\srel=/i', $attrs)) {
                $attrs .= ' rel="nofollow sponsored"';
            }

            if (preg_match('/\starget=/i', $attrs)) {
                $attrs = preg_replace('/\starget=(["\'])[^"\']*\1/i', ' target="_blank"', $attrs) ?? $attrs;
            } else {
                $attrs .= ' target="_blank"';
            }

            return '<a'.self::normalizeAttrSpacing($attrs).' href="'.e($affiliateUrl).'">';
        }, $html) ?? $html;
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
