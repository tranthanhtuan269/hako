<?php

namespace App\Support;

use Illuminate\Support\Str;

final class MerchantProductExtractor
{
    /**
     * @return array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>
     */
    public function extract(?string $html, string $baseUrl, int $limit = 5): array
    {
        if (! $html) {
            return [];
        }

        $products = [];

        $products = array_merge($products, $this->fromJsonLd($html, $baseUrl));
        $products = array_merge($products, $this->fromProductLinks($html, $baseUrl));

        return $this->uniqueTake($products, $limit);
    }

    /**
     * @param  array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>  $products
     * @return array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>
     */
    public function uniqueTake(array $products, int $limit = 5): array
    {
        $seenNames = [];
        $seenUrls = [];
        $seenImages = [];
        $unique = [];

        foreach ($products as $product) {
            $name = trim($product['name'] ?? '');

            if ($name === '' || strlen($name) < 3 || $this->isJunkProductName($name)) {
                continue;
            }

            $nameKey = Str::lower($name);
            $urlKey = filled($product['url'] ?? null)
                ? Str::lower(rtrim((string) $product['url'], '/'))
                : '';

            if ($urlKey !== '' && $this->isJunkProductUrl($urlKey)) {
                continue;
            }

            if (isset($seenNames[$nameKey])) {
                continue;
            }

            if ($urlKey !== '' && isset($seenUrls[$urlKey])) {
                continue;
            }

            $image = filled($product['image'] ?? null) ? (string) $product['image'] : null;
            if ($image !== null) {
                $imageKey = $this->normalizeImageKey($image);
                if (isset($seenImages[$imageKey])) {
                    // Keep the product, but drop the duplicated listing thumb so enrich can refill from PDP.
                    $image = null;
                }
            }

            $seenNames[$nameKey] = true;
            if ($urlKey !== '') {
                $seenUrls[$urlKey] = true;
            }
            if ($image !== null) {
                $seenImages[$this->normalizeImageKey($image)] = true;
            }

            $unique[] = [
                'name' => Str::limit(HtmlCleaner::decodeEntities($name), 120),
                'description' => filled($product['description'] ?? null)
                    ? Str::limit(HtmlCleaner::textFromHtml((string) $product['description']), 500)
                    : null,
                'price' => filled($product['price'] ?? null)
                    ? Str::limit(strip_tags((string) $product['price']), 40)
                    : null,
                'image' => $image,
                'url' => $product['url'] ?? null,
                'features' => is_array($product['features'] ?? null) ? $product['features'] : [],
            ];

            if (count($unique) >= $limit) {
                break;
            }
        }

        return $unique;
    }

    /**
     * @param  array{name: string, description: ?string, price: ?string, image: ?string, url: ?string, features?: list<string>}  $product
     * @param  list<string>  $excludeImageUrls  Image URLs already used by other products (avoid duplicates).
     * @return array{name: string, description: ?string, price: ?string, image: ?string, url: ?string, features: list<string>}
     */
    public function enrichFromPage(string $html, array $product, string $baseUrl, array $excludeImageUrls = []): array
    {
        $extracted = $this->extract($html, $baseUrl, 3);
        $detail = null;

        // Prefer JSON-LD/detail that matches this product URL or name when possible.
        $targetUrl = Str::lower(rtrim((string) ($product['url'] ?? ''), '/'));
        $targetName = Str::lower((string) ($product['name'] ?? ''));

        foreach ($extracted as $candidate) {
            $candidateUrl = Str::lower(rtrim((string) ($candidate['url'] ?? ''), '/'));
            $candidateName = Str::lower((string) ($candidate['name'] ?? ''));

            if ($targetUrl !== '' && $candidateUrl !== '' && $candidateUrl === $targetUrl) {
                $detail = $candidate;
                break;
            }

            if ($targetName !== '' && $candidateName !== '' && (
                str_contains($candidateName, $targetName) || str_contains($targetName, $candidateName)
            )) {
                $detail = $candidate;
                break;
            }
        }

        $detail ??= $extracted[0] ?? null;

        if (is_array($detail)) {
            if (empty($product['description']) && filled($detail['description'] ?? null)) {
                $product['description'] = Str::limit(strip_tags((string) $detail['description']), 500);
            }

            if (empty($product['price']) && filled($detail['price'] ?? null)) {
                $product['price'] = $detail['price'];
            }

            if (empty($product['url']) && filled($detail['url'] ?? null)) {
                $product['url'] = $detail['url'];
            }
        }

        // Always prefer a PDP-specific product image so listing/AI thumbs are not reused across SKUs.
        $pdpImage = $this->extractPrimaryProductImage($html, $baseUrl, $excludeImageUrls);

        if ($pdpImage) {
            $product['image'] = $pdpImage;
        } elseif (empty($product['image']) && is_array($detail) && filled($detail['image'] ?? null)) {
            $candidateImage = (string) $detail['image'];
            if (! $this->imageUrlIsExcluded($candidateImage, $excludeImageUrls)) {
                $product['image'] = $candidateImage;
            }
        } elseif (filled($product['image'] ?? null) && $this->imageUrlIsExcluded((string) $product['image'], $excludeImageUrls)) {
            $product['image'] = null;
        }

        $features = $this->extractFeatures($html);

        if ($features === [] && filled($product['description'] ?? null)) {
            $features = $this->featuresFromDescription((string) $product['description']);
        }

        if ($features === [] && is_array($detail) && filled($detail['description'] ?? null)) {
            $features = $this->featuresFromDescription((string) $detail['description']);
        }

        $product['features'] = $features;

        return $product;
    }

    /**
     * Prefer Open Graph / Twitter / product gallery images from a product detail page.
     *
     * @param  list<string>  $excludeUrls
     */
    public function extractPrimaryProductImage(string $html, string $baseUrl, array $excludeUrls = []): ?string
    {
        $candidates = [];

        // Prefer HTTPS Open Graph first (Shopify often emits http og:image + https secure_url).
        foreach ([
            '/<meta[^>]+property=["\']og:image:secure_url["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image:secure_url["\']/i',
            '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i',
            '/<meta[^>]+name=["\']twitter:image(?::src)?["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']twitter:image(?::src)?["\']/i',
        ] as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[1] as $matchUrl) {
                    $candidates[] = $this->absolutizeUrl($baseUrl, html_entity_decode($matchUrl));
                }
            }
        }

        if (preg_match_all('/<img[^>]+>/i', $html, $imgTags)) {
            foreach ($imgTags[0] as $tag) {
                $haystack = strtolower($tag);

                if (! preg_match('/(?:product|gallery|main|featured|primary|zoom|media)/i', $haystack)) {
                    continue;
                }

                if (! preg_match('/(?:src|data-src|data-zoom-image|data-srcset)=["\']([^"\']+)["\']/i', $tag, $srcMatch)) {
                    continue;
                }

                $src = html_entity_decode($srcMatch[1]);

                // data-srcset may list multiple URLs — take the first.
                if (str_contains($src, ' ')) {
                    $src = trim(explode(' ', $src)[0]);
                }

                $candidates[] = $this->absolutizeUrl($baseUrl, $src);
            }
        }

        // Shopify / theme JSON blobs often expose featured_image without og tags in some themes.
        if (preg_match_all('/"featured_image"\s*:\s*"(\\/[^"]+)"/i', $html, $matches)) {
            foreach ($matches[1] as $matchUrl) {
                $candidates[] = $this->absolutizeUrl($baseUrl, stripcslashes($matchUrl));
            }
        }
        if (preg_match_all('/"featured_image"\s*:\s*"(https?:\\/\\/[^"]+)"/i', $html, $matches)) {
            foreach ($matches[1] as $matchUrl) {
                $candidates[] = $this->absolutizeUrl($baseUrl, stripcslashes($matchUrl));
            }
        }
        if (preg_match_all('/"src"\s*:\s*"(https?:\\/\\/[^"]+cdn\\/shop\\/[^"]+\.(?:jpg|jpeg|png|webp)[^"]*)"/i', $html, $matches)) {
            foreach ($matches[1] as $matchUrl) {
                $candidates[] = $this->absolutizeUrl($baseUrl, stripcslashes($matchUrl));
            }
        }

        foreach ($candidates as $url) {
            $url = preg_replace('#^http://#i', 'https://', $url) ?: $url;

            if (! $this->looksLikeProductImageUrl($url)) {
                continue;
            }

            if ($this->imageUrlIsExcluded($url, $excludeUrls)) {
                continue;
            }

            return $url;
        }

        return null;
    }

    /**
     * Normalize image URLs for duplicate detection (ignore size/query noise).
     */
    public function normalizeImageKey(string $url): string
    {
        $url = preg_replace('#^http://#i', 'https://', trim($url)) ?: trim($url);
        $parts = parse_url($url);
        $path = $parts['path'] ?? '';

        // Strip common Shopify size suffixes: image_300x.jpg / image_grande.jpg
        $path = preg_replace('/_(?:pico|icon|thumb|small|compact|medium|large|grande|original|master|\d+x\d*|\d*x\d+)\./i', '.', $path) ?? $path;

        return Str::lower(($parts['host'] ?? '').$path);
    }

    /** @param  list<string>  $excludeUrls */
    public function imageUrlIsExcluded(string $url, array $excludeUrls): bool
    {
        if ($url === '' || $excludeUrls === []) {
            return false;
        }

        $key = $this->normalizeImageKey($url);

        foreach ($excludeUrls as $excluded) {
            if ($excluded !== '' && $this->normalizeImageKey((string) $excluded) === $key) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeProductImageUrl(string $url): bool
    {
        if ($url === '' || ! preg_match('#^https?://#i', $url)) {
            return false;
        }

        if (preg_match('/(logo|icon|favicon|sprite|avatar|badge|payment|pixel|1x1|spacer|blank)/i', $url)) {
            return false;
        }

        if (preg_match('/\.(svg)(\?|$)/i', $url)) {
            return false;
        }

        return true;
    }

    /** @return list<string> */
    private function extractFeatures(string $html): array
    {
        $scopes = [];

        // Prefer product-description / accordion regions so sitewide USP lists are not reused on every SKU.
        if (preg_match_all(
            '/<(?:div|section|aside)[^>]*(?:class|id)=["\'][^"\']*(?:product[-_\s]?(?:description|single|info|details|accordion|tabs|content)|rte|prose|metafield|accordion)[^"\']*["\'][^>]*>(.*?)<\/(?:div|section|aside)>/is',
            $html,
            $scoped
        )) {
            $scopes = array_slice($scoped[1], 0, 4);
        }

        $candidates = $this->featuresFromListItems($scopes !== [] ? implode("\n", $scopes) : $html);

        // If scoped scrape found nothing useful, try the full page with the same junk filters.
        if ($candidates === [] && $scopes !== []) {
            $candidates = $this->featuresFromListItems($html);
        }

        return $candidates;
    }

    /**
     * Turn a long product description into short bullets when the PDP has no useful <li> list.
     *
     * @return list<string>
     */
    public function featuresFromDescription(?string $description): array
    {
        if (! filled($description)) {
            return [];
        }

        $text = HtmlCleaner::textFromHtml((string) $description);
        $chunks = preg_split('/[\r\n•●▪]+|(?<=[.!?])\s+/u', $text) ?: [];
        $features = [];

        foreach ($chunks as $chunk) {
            $chunk = trim(preg_replace('/\s+/u', ' ', $chunk) ?? '');

            if ($chunk === '' || $this->isJunkFeature($chunk)) {
                continue;
            }

            if (strlen($chunk) < 24 || strlen($chunk) > 160) {
                continue;
            }

            $features[] = $chunk;

            if (count($features) >= 4) {
                break;
            }
        }

        return array_values(array_unique($features));
    }

    /** @return list<string> */
    private function featuresFromListItems(string $html): array
    {
        $features = [];

        if (! preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $html, $matches)) {
            return [];
        }

        foreach ($matches[1] as $item) {
            $text = HtmlCleaner::textFromHtml($item);

            if ($text === '' || $this->isJunkFeature($text)) {
                continue;
            }

            $features[] = $text;

            if (count($features) >= 6) {
                break;
            }
        }

        return array_values(array_unique($features));
    }

    private function isJunkFeature(string $text): bool
    {
        $text = trim($text);

        if ($text === '' || strlen($text) < 12 || strlen($text) > 160) {
            return true;
        }

        if (preg_match(
            '/^(home|shop|cart|login|sign in|subscribe|menu|search|affiliates?|instructions?|account|faq|contact|about|blog|privacy|terms|individual products?|full ingredient list|fast delivery|free shipping|shipping|returns?|size guide|reviews?)$/i',
            $text
        )) {
            return true;
        }

        // Sitewide Shopify promo / trust-bar junk that is identical on every PDP.
        if (preg_match('/\b(use code|promo code|% off|ends friday|free shipping \$|free shipping on over|newsletter|subscribe|add to cart|quick view|clinically studied peptide formulas)\b/i', $text)) {
            return true;
        }

        return false;
    }

    private function isJunkProductName(string $name): bool
    {
        $name = trim($name);

        if (preg_match(
            '/^(shop|products?|buy now|learn more|view all|add to cart|sale|new arrivals?|best sellers?|collections?|choose option|select option|select|options?|quick view|sold out|default title)$/i',
            $name
        )) {
            return true;
        }

        // CTA labels masquerading as products ("View Ritual", "Shop Collection").
        if (preg_match('/^(view|shop|explore|discover|see|buy|open)\s+.+$/i', $name) && str_word_count($name) <= 3) {
            return true;
        }

        // Review-widget / social-proof CTAs (common on SaaS landing pages).
        if (preg_match('/\breviews?\s+on\s+(g2|trustpilot|capterra|sitejabber|getapp|softwareadvice)\b/i', $name)) {
            return true;
        }

        if (preg_match('/^(read|see|check|view|browse)\s+.+\s+reviews?\b/i', $name)) {
            return true;
        }

        // Generic placeholder alts / SEO keyword blobs that are not real SKUs.
        if (preg_match('/^(skin care product|beauty product|product image|item|untitled)$/i', $name)) {
            return true;
        }

        if (preg_match('/\bskin care product\b/i', $name)) {
            return true;
        }

        if (preg_match('/^best\s+.+\s+for\s+.+$/i', $name)) {
            return true;
        }

        // Ingredient-only keyword titles without a product form factor.
        if (preg_match('/^(hyaluronic acid|retinol|collagen|peptides?|vitamin [a-cde])(\s+and\s+.+)?$/i', $name)) {
            return true;
        }

        return false;
    }

    private function isJunkProductUrl(string $url): bool
    {
        $host = Str::lower((string) (parse_url($url, PHP_URL_HOST) ?? ''));

        if ($host === '') {
            return false;
        }

        $host = preg_replace('/^www\./', '', $host) ?? $host;

        return (bool) preg_match(
            '/(^|\.)(g2\.com|trustpilot\.com|capterra\.com|sitejabber\.com|getapp\.com|softwareadvice\.com|producthunt\.com)$/i',
            $host
        );
    }

    public function nameFromProductUrl(string $url): ?string
    {
        if (! preg_match('#/(?:products?|p)/([^/?]+)#i', $url, $match)) {
            return null;
        }

        $slug = urldecode(str_replace(['-', '_'], ' ', $match[1]));
        $name = trim(preg_replace('/\s+/', ' ', $slug) ?? '');

        if ($name === '' || strlen($name) < 3 || $this->isJunkProductName($name)) {
            return null;
        }

        return Str::title($name);
    }

    /**
     * @return array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>
     */
    private function fromJsonLd(string $html, string $baseUrl): array
    {
        $products = [];

        if (! preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $blocks)) {
            return [];
        }

        foreach ($blocks[1] as $json) {
            $decoded = json_decode(html_entity_decode(trim($json)), true);

            if (is_array($decoded)) {
                $products = array_merge($products, $this->productsFromSchema($decoded, $baseUrl));
            }
        }

        return $products;
    }

    /**
     * @return array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>
     */
    private function productsFromSchema(mixed $data, string $baseUrl): array
    {
        if (! is_array($data)) {
            return [];
        }

        if (isset($data['@graph']) && is_array($data['@graph'])) {
            $merged = [];

            foreach ($data['@graph'] as $node) {
                $merged = array_merge($merged, $this->productsFromSchema($node, $baseUrl));
            }

            return $merged;
        }

        if (isset($data['@type']) && is_array($data)) {
            $types = is_array($data['@type']) ? $data['@type'] : [$data['@type']];

            if (in_array('ItemList', $types, true) && ! empty($data['itemListElement']) && is_array($data['itemListElement'])) {
                $merged = [];

                foreach ($data['itemListElement'] as $item) {
                    if (is_array($item) && isset($item['item'])) {
                        $merged = array_merge($merged, $this->productsFromSchema($item['item'], $baseUrl));
                    } else {
                        $merged = array_merge($merged, $this->productsFromSchema($item, $baseUrl));
                    }
                }

                return $merged;
            }

            if (in_array('Product', $types, true) || in_array('ProductGroup', $types, true)) {
                $product = $this->normalizeProductNode($data, $baseUrl);

                return $product ? [$product] : [];
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $nested = $this->productsFromSchema($value, $baseUrl);

                if ($nested !== []) {
                    return $nested;
                }
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}|null
     */
    private function normalizeProductNode(array $node, string $baseUrl): ?array
    {
        $name = $node['name'] ?? $node['alternateName'] ?? null;

        if (! is_string($name) || trim($name) === '') {
            return null;
        }

        $description = is_string($node['description'] ?? null) ? $node['description'] : null;
        $url = is_string($node['url'] ?? null) ? $this->absolutizeUrl($baseUrl, $node['url']) : null;

        $image = null;
        if (is_string($node['image'] ?? null)) {
            $image = $this->absolutizeUrl($baseUrl, $node['image']);
        } elseif (is_array($node['image'] ?? null)) {
            $firstImage = $node['image'][0] ?? null;
            if (is_string($firstImage)) {
                $image = $this->absolutizeUrl($baseUrl, $firstImage);
            } elseif (is_array($firstImage) && is_string($firstImage['url'] ?? null)) {
                $image = $this->absolutizeUrl($baseUrl, $firstImage['url']);
            }
        }

        $price = null;
        $offers = $node['offers'] ?? null;

        if (is_array($offers)) {
            if (isset($offers['price'])) {
                $currency = is_string($offers['priceCurrency'] ?? null) ? $offers['priceCurrency'] : 'USD';
                $price = $currency . ' ' . $offers['price'];
            } elseif (isset($offers[0]) && is_array($offers[0]) && isset($offers[0]['price'])) {
                $currency = is_string($offers[0]['priceCurrency'] ?? null) ? $offers[0]['priceCurrency'] : 'USD';
                $price = $currency . ' ' . $offers[0]['price'];
            }
        }

        return [
            'name' => HtmlCleaner::decodeEntities(trim($name)),
            'description' => is_string($description) ? HtmlCleaner::decodeEntities($description) : null,
            'price' => $price,
            'image' => $image,
            'url' => $url,
        ];
    }

    /**
     * @return array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>
     */
    private function fromProductLinks(string $html, string $baseUrl): array
    {
        $products = [];

        if (! preg_match_all('/<a[^>]+href=["\']([^"\']*(?:\/products\/|\/product\/|\/p\/)[^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($matches as $match) {
            $href = html_entity_decode($match[1]);
            $inner = $match[2];
            $text = HtmlCleaner::textFromHtml($inner);
            $image = null;
            $alt = null;
            $url = $this->absolutizeUrl($baseUrl, $href);

            if (preg_match('/<img[^>]+>/i', $inner, $imgTag)) {
                if (preg_match('/(?:src|data-src)=["\']([^"\']+)["\']/i', $imgTag[0], $srcMatch)) {
                    $src = html_entity_decode($srcMatch[1]);
                    if (str_contains($src, ' ')) {
                        $src = trim(explode(',', explode(' ', $src)[0])[0]);
                    }
                    $candidate = $this->absolutizeUrl($baseUrl, $src);
                    if ($this->looksLikeProductImageUrl($candidate)) {
                        $image = preg_replace('#^http://#i', 'https://', $candidate) ?: $candidate;
                    }
                }

                if (preg_match('/alt=["\']([^"\']*)["\']/i', $imgTag[0], $altMatch)) {
                    $alt = HtmlCleaner::decodeEntities(trim($altMatch[1]));
                }
            }

            if ($text === '' && filled($alt) && strlen($alt) >= 3) {
                $text = $alt;
            }

            if ($text === '' || strlen($text) < 3 || strlen($text) > 120 || $this->isJunkProductName($text)) {
                $fromUrl = $this->nameFromProductUrl($url);
                if ($fromUrl === null) {
                    continue;
                }
                $text = $fromUrl;
            }

            $products[] = [
                'name' => $text,
                'description' => null,
                'price' => null,
                'image' => $image,
                'url' => $url,
            ];
        }

        return $products;
    }

    private function absolutizeUrl(string $baseUrl, string $path): string
    {
        $path = trim($path);

        if ($path === '' || preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $parts = parse_url($baseUrl);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';

        if (str_starts_with($path, '//')) {
            return $scheme . ':' . $path;
        }

        if (str_starts_with($path, '/')) {
            return "{$scheme}://{$host}{$path}";
        }

        $dir = rtrim(dirname($parts['path'] ?? '/'), '/');

        return "{$scheme}://{$host}{$dir}/{$path}";
    }
}
