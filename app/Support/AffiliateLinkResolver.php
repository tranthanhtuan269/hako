<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class AffiliateLinkResolver
{
    public function __construct(
        private readonly MerchantProductExtractor $productExtractor = new MerchantProductExtractor(),
        private readonly MerchantProductEnricher $productEnricher = new MerchantProductEnricher(),
    ) {}

    public function finalUrl(string $affiliateUrl): string
    {
        $affiliateUrl = trim($affiliateUrl);

        if ($affiliateUrl === '') {
            return '';
        }

        $redirected = $this->followRedirects($affiliateUrl);
        $seedUrl = $this->unwrapAffiliateUrl($affiliateUrl);

        if ($seedUrl === $affiliateUrl) {
            return $redirected;
        }

        $unwrappedFinal = $this->followRedirects($seedUrl);
        $host = parse_url($unwrappedFinal, PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            $host = preg_replace('/^www\./', '', strtolower($host));

            if (! $this->isAffiliateHost($host)) {
                return $unwrappedFinal;
            }
        }

        return $redirected;
    }

    public function resolve(string $affiliateUrl): array
    {
        $affiliateUrl = trim($affiliateUrl);
        $seedUrl = $this->unwrapAffiliateUrl($affiliateUrl);
        $finalUrl = $this->finalUrl($affiliateUrl);
        $host = $this->merchantDomain($finalUrl, $seedUrl, $affiliateUrl);

        $html = $this->fetchHtml($finalUrl) ?? $this->fetchHtml($seedUrl) ?? $this->fetchHtml($affiliateUrl);
        $pageTitle = $this->extractTitle($html);
        $metaDescription = $this->extractMetaDescription($html);
        $name = $this->guessStoreName($host, $pageTitle);
        $category = $this->guessCategory($host, $pageTitle, $metaDescription, $finalUrl);
        $logo = $this->resolveLogo($host, $finalUrl, $html);
        $faqs = $this->extractFaqs($html);
        $products = $this->discoverProducts($finalUrl, $html);

        return [
            'affiliate_url' => $affiliateUrl,
            'final_url' => $finalUrl,
            'domain' => $host,
            'name' => $name,
            'logo' => $logo,
            'page_title' => $pageTitle,
            'meta_description' => $metaDescription,
            'category_id' => $category?->id,
            'category_name' => $category?->name,
            'faqs' => $faqs,
            'products' => $products,
        ];
    }

    /**
     * Pull FAQ and extra copy from the public store website when provided.
     *
     * @param  array<string, mixed>  $merchant
     * @return array<string, mixed>
     */
    public function enrichFromWebsite(array $merchant, string $websiteUrl): array
    {
        $html = $this->fetchHtml($websiteUrl);

        if ($html) {
            $faqs = $this->extractFaqs($html);

            if ($faqs !== []) {
                $merchant['faqs'] = $faqs;
            }

            if (empty($merchant['meta_description'])) {
                $merchant['meta_description'] = $this->extractMetaDescription($html);
            }

            $host = parse_url($websiteUrl, PHP_URL_HOST) ?: ($merchant['domain'] ?? '');
            $logo = $this->resolveLogo($host, $websiteUrl, $html);

            if ($logo) {
                $merchant['logo'] = $logo;
            }

            $websiteProducts = $this->discoverProducts($websiteUrl, $html);

            if (count($websiteProducts) >= count($merchant['products'] ?? [])) {
                $merchant['products'] = $websiteProducts;
            }
        }

        return $merchant;
    }

    /**
     * @return array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>
     */
    private function discoverProducts(string $baseUrl, ?string $html): array
    {
        $products = $this->productExtractor->extract($html, $baseUrl);

        if (count($products) >= 2) {
            return $products;
        }

        $paths = ['/collections/all', '/shop', '/products', '/catalog', '/store'];

        foreach ($paths as $path) {
            $shopUrl = rtrim($baseUrl, '/') . $path;
            $shopHtml = $this->fetchHtml($shopUrl);

            if (! $shopHtml) {
                continue;
            }

            $products = $this->productExtractor->uniqueTake(
                array_merge($products, $this->productExtractor->extract($shopHtml, $shopUrl)),
                3
            );

            if (count($products) >= 2) {
                break;
            }
        }

        return $this->productEnricher->enrich(
            $products,
            fn (string $url) => $this->fetchHtml($url)
        );
    }

    private function unwrapAffiliateUrl(string $url): string
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

    private function followRedirects(string $url): string
    {
        if ($url === '') {
            return '';
        }

        if (function_exists('curl_init')) {
            $curlFinal = $this->followRedirectsWithCurl($url);

            if ($curlFinal !== null) {
                return $curlFinal;
            }
        }

        try {
            $client = Http::withOptions([
                'allow_redirects' => ['max' => 10, 'track_redirects' => true],
            ])
                ->timeout(12)
                ->withoutVerifying()
                ->withHeaders([
                    'User-Agent' => config('site.bot_user_agent'),
                ]);

            $response = $client->head($url);

            if (! $response->successful() && in_array($response->status(), [405, 501], true)) {
                $response = $client->get($url);
            }

            if ($response->successful()) {
                return (string) ($response->effectiveUri() ?? $url);
            }
        } catch (\Throwable) {
            // Fall back to the original URL when the merchant blocks automated requests.
        }

        return $url;
    }

    private function followRedirectsWithCurl(string $url): ?string
    {
        $ch = curl_init($url);

        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_NOBODY => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_USERAGENT => (string) config('site.bot_user_agent', 'Mozilla/5.0'),
        ]);

        curl_exec($ch);

        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $error = curl_errno($ch);

        curl_close($ch);

        if ($error !== 0 || ! is_string($finalUrl) || $finalUrl === '') {
            return null;
        }

        return $finalUrl;
    }

    private function merchantDomain(string $finalUrl, string $seedUrl, string $affiliateUrl): string
    {
        foreach ([$finalUrl, $seedUrl, $affiliateUrl] as $url) {
            $host = parse_url($url, PHP_URL_HOST);

            if (! $host) {
                continue;
            }

            $host = preg_replace('/^www\./', '', $host);

            if ($this->isAffiliateHost($host)) {
                continue;
            }

            return $host;
        }

        $host = parse_url($finalUrl, PHP_URL_HOST) ?: parse_url($affiliateUrl, PHP_URL_HOST);

        return preg_replace('/^www\./', '', (string) $host);
    }

    private function isAffiliateHost(string $host): bool
    {
        $needles = [
            'awin1.com',
            'linksynergy.com',
            'rakuten.com',
            'viglink.com',
            'skimresources.com',
            'shareasale.com',
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
        ];

        foreach ($needles as $needle) {
            if ($host === $needle || str_ends_with($host, '.'.$needle)) {
                return true;
            }
        }

        return false;
    }

    private function fetchHtml(string $url): ?string
    {
        try {
            $response = Http::timeout(12)
                ->withHeaders([
                    'User-Agent' => config('site.bot_user_agent'),
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($url);

            if ($response->successful() && str_contains(strtolower($response->header('Content-Type', '')), 'html')) {
                return $response->body();
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function resolveLogo(string $domain, string $finalUrl, ?string $html): ?string
    {
        if ($domain === '') {
            return null;
        }

        $candidates = [];

        if ($html) {
            $candidates = array_merge($candidates, $this->extractLogoCandidatesFromHtml($html, $finalUrl));
        }

        // build logo từ favicon, thảo nào có nhiều logo nhỏ thế. 
        $scheme = parse_url($finalUrl, PHP_URL_SCHEME) ?: 'https';
        $candidates[] = "{$scheme}://{$domain}/apple-touch-icon.png";
        $candidates[] = "{$scheme}://{$domain}/favicon.ico";
        $candidates[] = "https://www.google.com/s2/favicons?domain={$domain}&sz=128";
        $candidates[] = "https://icons.duckduckgo.com/ip3/{$domain}.ico";

        $candidates = array_values(array_unique(array_filter($candidates)));

        $valid = [];

        foreach ($candidates as $candidate) {
            if ($this->isValidLogoUrl($candidate)) {
                $valid[] = $candidate;
            }
        }

        return $this->pickPreferredLogo($valid, $domain);
    }

    /**
     * @param  list<string>  $valid
     */
    private function pickPreferredLogo(array $valid, string $domain): ?string
    {
        foreach ($valid as $url) {
            if (! $this->isSvgUrl($url)) {
                return $url;
            }
        }

        if ($valid !== []) {
            return $valid[0];
        }

        $fallback = "https://www.google.com/s2/favicons?domain={$domain}&sz=128";

        return $this->isValidLogoUrl($fallback) ? $fallback : null;
    }

    private function isSvgUrl(string $url): bool
    {
        return (bool) preg_match('/\.svg(\?|$)/i', $url);
    }

    /**
     * @return list<string>
     */
    private function extractLogoCandidatesFromHtml(string $html, string $baseUrl): array
    {
        $candidates = [];

        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $match)
            || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i', $html, $match)) {
            $candidates[] = $this->absolutizeUrl($baseUrl, html_entity_decode($match[1]));
        }

        if (preg_match_all('/"@type"\s*:\s*"Organization"[^}]*"logo"\s*:\s*"([^"]+)"/is', $html, $matches)) {
            foreach ($matches[1] as $logo) {
                $candidates[] = $this->absolutizeUrl($baseUrl, html_entity_decode($logo));
            }
        }

        if (preg_match_all('/<link[^>]+rel=["\'](?:apple-touch-icon(?:-precomposed)?|icon|shortcut icon)["\'][^>]*>/i', $html, $linkTags)) {
            $icons = [];

            foreach ($linkTags[0] as $tag) {
                if (! preg_match('/href=["\']([^"\']+)["\']/i', $tag, $hrefMatch)) {
                    continue;
                }

                $size = 0;
                if (preg_match('/sizes=["\'](\d+)x\d+["\']/i', $tag, $sizeMatch)) {
                    $size = (int) $sizeMatch[1];
                }

                $icons[] = [
                    'size' => $size,
                    'url' => $this->absolutizeUrl($baseUrl, html_entity_decode($hrefMatch[1])),
                ];
            }

            usort($icons, fn (array $a, array $b) => $b['size'] <=> $a['size']);

            foreach ($icons as $icon) {
                $candidates[] = $icon['url'];
            }
        }

        if (preg_match('/<meta[^>]+property=["\']og:logo["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $match)
            || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:logo["\']/i', $html, $match)) {
            $candidates[] = $this->absolutizeUrl($baseUrl, html_entity_decode($match[1]));
        }

        return $candidates;
    }

    private function absolutizeUrl(string $baseUrl, string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return $baseUrl;
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $parts = parse_url($baseUrl);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';

        if (str_starts_with($path, '//')) {
            return $scheme.':'.$path;
        }

        if (str_starts_with($path, '/')) {
            return "{$scheme}://{$host}{$path}";
        }

        $dir = rtrim(dirname($parts['path'] ?? '/'), '/');

        return "{$scheme}://{$host}{$dir}/{$path}";
    }

    private function isValidLogoUrl(string $url): bool
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => config('site.bot_user_agent'),
                    'Accept' => 'image/*,*/*',
                ])
                ->get($url);

            if (! $response->successful()) {
                return false;
            }

            $type = strtolower((string) $response->header('Content-Type', ''));

            if ($type !== '' && ! str_starts_with($type, 'image/') && ! str_contains($type, 'icon')) {
                return false;
            }

            return strlen($response->body()) > 120;
        } catch (\Throwable) {
            return false;
        }
    }

    private function extractTitle(?string $html): ?string
    {
        if (! $html || ! preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return null;
        }

        $title = html_entity_decode(trim(strip_tags($matches[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $title !== '' ? Str::limit($title, 180) : null;
    }

    private function extractMetaDescription(?string $html): ?string
    {
        if (! $html) {
            return null;
        }

        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)
            || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\']/i', $html, $matches)) {
            $description = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return $description !== '' ? Str::limit($description, 500) : null;
        }

        return null;
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    private function extractFaqs(?string $html): array
    {
        if (! $html) {
            return [];
        }

        $faqs = [];

        if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $blocks)) {
            foreach ($blocks[1] as $json) {
                $decoded = json_decode(html_entity_decode(trim($json)), true);

                if (is_array($decoded)) {
                    $faqs = array_merge($faqs, $this->faqsFromSchema($decoded));
                }
            }
        }

        return array_values(array_slice($faqs, 0, 10));
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    private function faqsFromSchema(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        if (isset($data['@graph']) && is_array($data['@graph'])) {
            $merged = [];

            foreach ($data['@graph'] as $node) {
                $merged = array_merge($merged, $this->faqsFromSchema($node));
            }

            return $merged;
        }

        $type = $data['@type'] ?? null;
        $types = is_array($type) ? $type : [$type];

        if (in_array('FAQPage', $types, true) && ! empty($data['mainEntity']) && is_array($data['mainEntity'])) {
            $faqs = [];

            foreach ($data['mainEntity'] as $entity) {
                if (! is_array($entity)) {
                    continue;
                }

                $question = $entity['name'] ?? null;
                $answer = is_array($entity['acceptedAnswer'] ?? null)
                    ? ($entity['acceptedAnswer']['text'] ?? null)
                    : null;

                if ($question && $answer) {
                    $faqs[] = [
                        'question' => Str::limit(strip_tags((string) $question), 220),
                        'answer' => Str::limit(strip_tags((string) $answer), 600),
                    ];
                }
            }

            return $faqs;
        }

        if (in_array('Question', $types, true)) {
            $question = $data['name'] ?? null;
            $answer = is_array($data['acceptedAnswer'] ?? null)
                ? ($data['acceptedAnswer']['text'] ?? null)
                : null;

            if ($question && $answer) {
                return [[
                    'question' => Str::limit(strip_tags((string) $question), 220),
                    'answer' => Str::limit(strip_tags((string) $answer), 600),
                ]];
            }
        }

        return [];
    }

    private function guessStoreName(string $domain, ?string $pageTitle): string
    {
        if ($domain !== '') {
            $parts = explode('.', $domain);
            $label = $parts[count($parts) - 2] ?? $parts[0] ?? $domain;

            if (! in_array(strtolower($label), ['shop', 'store', 'www', 'm', 'app'], true)) {
                return Str::title(str_replace(['-', '_'], ' ', $label));
            }
        }

        if ($pageTitle) {
            $clean = preg_replace('/\s*[|\-–—:].*$/u', '', $pageTitle);

            return Str::limit(trim((string) $clean), 80) ?: 'New Store';
        }

        return $domain !== '' ? Str::title(str_replace(['-', '_'], ' ', explode('.', $domain)[0])) : 'New Store';
    }

    private function guessCategory(string $domain, ?string $pageTitle, ?string $metaDescription, string $finalUrl): ?Category
    {
        $haystack = Str::lower(implode(' ', array_filter([$domain, $pageTitle, $metaDescription, $finalUrl])));

        $keywords = [
            'Fashion' => ['fashion', 'clothing', 'apparel', 'shoes', 'dress', 'wear', 'nike', 'adidas', 'zara'],
            'Electronics' => ['electronics', 'tech', 'computer', 'laptop', 'phone', 'gadget', 'bestbuy', 'apple'],
            'Beauty' => ['beauty', 'cosmetic', 'skincare', 'makeup', 'fragrance', 'sephora', 'ulta'],
            'Food & Dining' => ['food', 'grocery', 'restaurant', 'dining', 'meal', 'pizza', 'coffee', 'doordash', 'ubereats'],
            'Travel' => ['travel', 'hotel', 'flight', 'airline', 'booking', 'vacation', 'expedia', 'airbnb'],
            'Health' => ['health', 'pharmacy', 'vitamin', 'supplement', 'wellness', 'medical', 'cvs', 'walgreens'],
        ];

        $categories = Category::active()->orderBy('name')->get()->keyBy('name');

        foreach ($keywords as $categoryName => $terms) {
            foreach ($terms as $term) {
                if (str_contains($haystack, $term) && $categories->has($categoryName)) {
                    return $categories->get($categoryName);
                }
            }
        }

        return null;
    }

    public function categories(): Collection
    {
        return Category::active()->orderBy('name')->get();
    }
}
