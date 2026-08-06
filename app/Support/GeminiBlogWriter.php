<?php

namespace App\Support;

use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class GeminiBlogWriter
{
    public function isEnabled(): bool
    {
        return (bool) config('ai.gemini.enabled')
            && filled(SiteIntegrations::geminiApiKey());
    }

    /**
     * @param  array{
     *     store_name: string,
     *     category_name: ?string,
     *     store_slug: string,
     *     affiliate_url: ?string,
     *     offers: array<int, array{code: ?string, title: string, description: ?string, type: string}>,
     *     merchant: array<string, mixed>
     * }  $context
     * @return array{title: string, excerpt: string, meta_title: string, meta_description: string, content: string, source: string}|null
     */
    public function generate(array $context): ?array
    {
        $parsed = $this->requestJson($this->buildPrompt($context), 'Gemini blog generation');

        if ($parsed === null) {
            return null;
        }

        $blog = $this->normalizeOutput($parsed);

        if ($blog === null) {
            return null;
        }

        $blog['source'] = 'gemini';

        return $blog;
    }

    /**
     * @param  array{
     *     store_name: string,
     *     category_name: ?string,
     *     store_slug: string,
     *     affiliate_url: ?string,
     *     offers: array<int, array{code: ?string, title: string, description: ?string, type: string}>,
     *     merchant: array<string, mixed>
     * }  $context
     */
    public function generateStoreDescription(array $context): ?string
    {
        $parsed = $this->requestJson($this->buildStoreDescriptionPrompt($context), 'Gemini store description generation');

        if ($parsed === null) {
            return null;
        }

        $content = trim((string) ($parsed['content'] ?? ''));

        return $content !== '' ? $content : null;
    }

    /**
     * Ask Gemini to pick the best products + product photos for a review/deals article.
     *
     * @param  array{
     *     store_name: string,
     *     domain: ?string,
     *     category_name: ?string,
     *     meta_description: ?string,
     *     page_title: ?string,
     *     base_url: string,
     *     candidates: array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>,
     *     html_excerpt: ?string
     * }  $context
     * @return array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string, features: list<string>}>|null
     */
    public function pickBestProducts(array $context): ?array
    {
        $parsed = $this->requestJson($this->buildProductPickerPrompt($context), 'Gemini product picker');

        if ($parsed === null) {
            return null;
        }

        $items = $parsed['products'] ?? null;

        if (! is_array($items) || $items === []) {
            return null;
        }

        $domain = strtolower(preg_replace('/^www\./', '', (string) ($context['domain'] ?? '')));
        $baseUrl = (string) ($context['base_url'] ?? '');
        $candidatesByUrl = [];

        foreach ($context['candidates'] ?? [] as $candidate) {
            if (! is_array($candidate) || empty($candidate['url'])) {
                continue;
            }

            $candidatesByUrl[strtolower(rtrim((string) $candidate['url'], '/'))] = $candidate;
        }

        $products = [];
        $usedImages = [];
        $usedUrls = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            $image = trim((string) ($item['image'] ?? ''));

            if ($name === '' || strlen($name) < 3 || $url === '' || ! preg_match('#^https?://#i', $url)) {
                continue;
            }

            if (preg_match('/^(choose option|select option|select|options?|quick view|default title|product image|view ritual|shop now)$/i', $name)) {
                continue;
            }

            if (preg_match('/\bskin care product\b|^best\s+.+\s+for\s+/i', $name)) {
                continue;
            }

            $urlHost = strtolower(preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST)));

            if ($domain !== '' && $urlHost !== '' && $urlHost !== $domain && ! str_ends_with($urlHost, '.'.$domain)) {
                continue;
            }

            $urlKey = strtolower(rtrim($url, '/'));
            if (isset($usedUrls[$urlKey])) {
                continue;
            }

            $matchedCandidate = $candidatesByUrl[$urlKey] ?? null;

            if ($candidatesByUrl !== [] && $matchedCandidate === null) {
                // Allow same-domain PDP URLs even if the model slightly rewrote the link.
                if (! preg_match('#/(products?|product|p)/#i', $url)) {
                    continue;
                }
            }

            if ($image === '' && is_array($matchedCandidate) && filled($matchedCandidate['image'] ?? null)) {
                $image = (string) $matchedCandidate['image'];
            }

            if ($image !== '' && ! preg_match('#^https?://#i', $image)) {
                if (str_starts_with($image, '//')) {
                    $image = 'https:'.$image;
                } elseif (str_starts_with($image, '/') && $baseUrl !== '') {
                    $parts = parse_url($baseUrl);
                    $image = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').$image;
                } else {
                    $image = '';
                }
            }

            if ($image !== '' && preg_match('/(logo|icon|favicon|sprite|avatar|badge|pixel|1x1|spacer|blank)/i', $image)) {
                $image = '';
            }

            // Never reuse the same product photo across SKUs.
            if ($image !== '') {
                $imageKey = $this->normalizePickerImageKey($image);
                if (isset($usedImages[$imageKey])) {
                    $image = '';
                }
            }

            if ($name === '' && is_array($matchedCandidate)) {
                $name = (string) ($matchedCandidate['name'] ?? '');
            }

            $products[] = [
                'name' => Str::limit(HtmlCleaner::decodeEntities($name), 120),
                'description' => filled($item['description'] ?? null)
                    ? Str::limit(HtmlCleaner::textFromHtml((string) $item['description']), 500)
                    : (is_array($matchedCandidate) ? ($matchedCandidate['description'] ?? null) : null),
                'price' => filled($item['price'] ?? null)
                    ? Str::limit(strip_tags((string) $item['price']), 40)
                    : (is_array($matchedCandidate) ? ($matchedCandidate['price'] ?? null) : null),
                'image' => $image !== '' ? $image : null,
                'url' => $url,
                'features' => [],
            ];

            $usedUrls[$urlKey] = true;
            if ($image !== '') {
                $usedImages[$this->normalizePickerImageKey($image)] = true;
            }

            if (count($products) >= 5) {
                break;
            }
        }

        return $products !== [] ? $products : null;
    }

    private function normalizePickerImageKey(string $url): string
    {
        $url = preg_replace('#^http://#i', 'https://', trim($url)) ?: trim($url);
        $parts = parse_url($url);
        $path = $parts['path'] ?? '';
        $path = preg_replace('/_(?:pico|icon|thumb|small|compact|medium|large|grande|original|master|\d+x\d*|\d*x\d+)\./i', '.', $path) ?? $path;

        return Str::lower(($parts['host'] ?? '').$path);
    }

    /**
     * @param  array{
     *     store_name: string,
     *     domain: ?string,
     *     category_name: ?string,
     *     meta_description: ?string,
     *     page_title: ?string,
     *     base_url: string,
     *     candidates: array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>,
     *     html_excerpt: ?string
     * }  $context
     */
    private function buildProductPickerPrompt(array $context): string
    {
        $storeName = (string) ($context['store_name'] ?? 'Store');
        $domain = (string) ($context['domain'] ?? '');
        $candidates = array_slice($context['candidates'] ?? [], 0, 24);
        $htmlExcerpt = Str::limit(preg_replace('/\s+/', ' ', (string) ($context['html_excerpt'] ?? '')) ?? '', 12000, '');

        $payload = [
            'store_name' => $storeName,
            'domain' => $domain,
            'category' => $context['category_name'] ?? null,
            'meta_description' => $context['meta_description'] ?? null,
            'page_title' => $context['page_title'] ?? null,
            'base_url' => $context['base_url'] ?? null,
            'candidate_products' => collect($candidates)->map(fn (array $p) => [
                'name' => $p['name'] ?? null,
                'url' => $p['url'] ?? null,
                'image' => $p['image'] ?? null,
                'price' => $p['price'] ?? null,
                'description' => filled($p['description'] ?? null) ? Str::limit((string) $p['description'], 180) : null,
            ])->values()->all(),
            'html_excerpt' => $htmlExcerpt !== '' ? $htmlExcerpt : null,
        ];

        $factsJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return <<<PROMPT
You are a senior affiliate merchandiser picking products for a U.S. coupon/review article about {$storeName}.

From the JSON below, select the 3–5 BEST products to feature (bestsellers, flagship items, or clearly distinct hero SKUs). Also pick the BEST product photo URL for each — a real product image (wallet, bag, backpack, device, serum bottle, etc.), never a logo, icon, badge, or banner.

JSON:
{$factsJson}

Rules:
- Prefer items from candidate_products. You may use html_excerpt only to refine names/images/prices already implied by candidates.
- Every product MUST include a product page url on domain "{$domain}" (or from candidate_products).
- Every product SHOULD include an image URL of the actual product. Prefer high-quality CDN/product gallery images from candidates or html_excerpt. Do not invent image URLs that are not present in the JSON.
- CRITICAL: each product must have a UNIQUE image URL. Never reuse the same image for two products. If you cannot find a distinct photo, set image to null.
- Skip junk labels like "Choose Option", "Select Option", "Quick View", or generic "product" alts.
- Do not invent product names or URLs that are not supported by candidates/html_excerpt.
- Prefer variety across the catalog when candidates allow it instead of near-duplicates.
- Rank by usefulness for shoppers researching deals: popular, clearly named, with price/image when available.

Return valid JSON only:
{
  "products": [
    {
      "name": "string",
      "url": "https://...",
      "image": "https://... or null",
      "price": "string or null",
      "description": "short string or null",
      "why_selected": "one short reason"
    }
  ]
}
PROMPT;
    }

    /** @return array<string, mixed>|null */
    private function requestJson(string $prompt, string $logContext): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $apiKey = SiteIntegrations::geminiApiKey();
        $model = (string) config('ai.gemini.model', 'gemini-2.0-flash');
        $timeout = (int) config('ai.gemini.timeout', 90);

        try {
            $response = Http::timeout($timeout)
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'responseMimeType' => 'application/json',
                            'temperature' => 0.55,
                            'maxOutputTokens' => 8192,
                        ],
                    ]
                );

            if (! $response->successful()) {
                Log::warning("{$logContext} failed", [
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 500),
                ]);

                return null;
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

            if (! is_string($text) || trim($text) === '') {
                return null;
            }

            $parsed = json_decode($text, true);

            return is_array($parsed) ? $parsed : null;
        } catch (\Throwable $exception) {
            Log::warning("{$logContext} exception", [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array{
     *     store_name: string,
     *     category_name: ?string,
     *     store_slug: string,
     *     affiliate_url: ?string,
     *     offers: array<int, array{code: ?string, title: string, description: ?string, type: string}>,
     *     merchant: array<string, mixed>
     * }  $context
     */
    private function buildPrompt(array $context): string
    {
        $siteName = (string) config('site.name');
        $storeName = $context['store_name'];
        $category = $context['category_name'] ?? 'online retail';
        $storeUrl = url('/stores/' . $context['store_slug']);
        $affiliateUrl = filled($context['affiliate_url'] ?? null) ? (string) $context['affiliate_url'] : null;
        $merchant = $context['merchant'];
        $products = is_array($merchant['products'] ?? null) ? $merchant['products'] : [];
        $faqs = is_array($merchant['faqs'] ?? null) ? $merchant['faqs'] : [];
        $offers = $context['offers'];

        $payload = [
            'site_name' => $siteName,
            'store_name' => $storeName,
            'store_url' => $storeUrl,
            'affiliate_url' => $affiliateUrl,
            'category' => $category,
            'domain' => $merchant['domain'] ?? null,
            'meta_description' => $merchant['meta_description'] ?? null,
            'page_title' => $merchant['page_title'] ?? null,
            'product_focus' => (bool) ($merchant['product_focus'] ?? false),
            'banner' => $merchant['banner'] ?? null,
            'logo' => $merchant['logo'] ?? null,
            'products' => $products,
            'faqs' => $faqs,
            'offers' => collect($offers)->map(fn (array $offer) => [
                'title' => $offer['title'],
                'code' => $offer['code'] ?? null,
                'type' => $offer['type'] ?? (filled($offer['code'] ?? null) ? 'coupon' : 'discount'),
                'description' => $offer['description'] ?? null,
            ])->values()->all(),
        ];

        $factsJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $productFocusRules = ! empty($merchant['product_focus'])
            ? <<<FOCUS
- CRITICAL: product_focus is true. Write ONLY about the single product in the JSON products array (the linked product page).
- Do NOT invent, compare, or list other catalog products from the same domain (e.g. other Amazon ASINs).
- Structure as a deep product review/spotlight of that one product: features, who it is for, pros/cons, pricing if available, how to buy via affiliate_url, FAQs, verdict.
- Title and excerpt must center on that product name, not a brand-wide comparison.
FOCUS
            : <<<FOCUS
- If 2+ products: comparison article with a store at-a-glance <table class="comparison-table"> plus a product comparison table (Feature | each product).
- If 1 product: product spotlight/review style with an at-a-glance Feature | brand table and a product specs table when features/price exist.
- If 0 products: store/brand review grounded in offers, meta_description, FAQs, and domain — still include an at-a-glance Feature | brand table.
FOCUS;
        $antiAiRules = $this->antiGenericAiWritingRules($storeName);

        return <<<PROMPT
You are a senior deal editor at {$siteName} writing for U.S. shoppers. Write like a human researcher summarizing THIS merchant's public facts — not like a marketing brochure.

Write ONE English blog article using ONLY the facts in the JSON below. Do not invent product specs, prices, reviews, awards, founding stories, or coupon codes that are not in the data.

JSON facts:
{$factsJson}

{$antiAiRules}

Article rules:
- Audience: U.S. online shoppers comparing products and coupons before checkout.
- CRITICAL: Do NOT use specific calendar dates, months, or years in the title, excerpt, meta fields, headings, or body (e.g. ban "July 2026", "August 2025", "offers listed for July 2026", "this July guide"). Prefer evergreen phrasing like "current deals", "right now", or "featured offers".
- Length: 1,000–1,600 words preferred. Prefer a shorter, specific article over padded filler. Never invent content just to hit a word count.
{$productFocusRules}
- Include sections: intro, Why Trust Us (EEAT), at-a-glance comparison table, product comparison or highlights, pros/cons, how to save with coupons, FAQ, final verdict.
- REQUIRED: include at least one HTML <table class="comparison-table">. First table should be "{$storeName} at a glance" with columns Feature | {$storeName}. Example rows grounded in JSON only: Category, Wallet/Bags/Belt/Leather (Yes only if product names/descriptions support it), Featured products, Sample listed price, Offers tracked, Shipping, Returns. If shipping/returns are unknown, write "Confirm on merchant site" — never invent policies.
- If 2+ products exist, also include a second product comparison table (Feature | Product A | Product B …) with listed price and best-for columns using only JSON facts.
- Place a <h2>Why Trust Us</h2> section near the top (right after the intro). Example tone: "We researched {$storeName} products, customer reviews, pricing and verified coupon availability before publishing this guide." Explain product research, coupon verification, and transparent affiliate disclosure for {$siteName}. Do not invent lab tests, awards, or fake reviewer credentials.
- If banner URL is provided, place it FIRST: <figure class="article-media article-media--banner"><img src="BANNER_URL" alt="{$storeName} official store banner" loading="lazy"><figcaption>{$storeName} store banner</figcaption></figure>
- If logo URL is provided, place it right after the banner: <figure class="article-media article-media--logo"><img src="LOGO_URL" alt="{$storeName} brand logo" width="160" height="160" loading="lazy"><figcaption>{$storeName} logo</figcaption></figure>
- For each product that has an image URL, place a product photo under that product heading: <figure class="article-media article-media--product"><img src="IMAGE_URL" alt="PRODUCT_NAME product photo from {$storeName}" loading="lazy"><figcaption>PRODUCT_NAME</figcaption></figure>. Use only image URLs from the JSON — never invent image URLs. Prefer real product photos (wallet, bag, backpack, device, etc.) over decorative graphics.
- Quote or paraphrase product descriptions, features, prices, offer titles/codes, and merchant FAQs from the JSON. Name products and codes explicitly in product sections — do not repeat the full product-name list in intro, Why Trust Us, and what-sells.
- CRITICAL: each product heading must use that product's OWN description/features only. Never copy-paste the same bullet list across products. If features are missing or identical sitewide USPs, paraphrase the product description instead or omit bullets.
- Mention {$siteName} naturally and link to our store deals page (store_url) when sending readers to browse coupons on our site.
- When affiliate_url is provided in the JSON, include at least 2 in-article links to affiliate_url with rel="nofollow sponsored" and target="_blank" when directing readers to shop at the merchant. Do not use store_url for outbound shopping CTAs when affiliate_url exists.
- Use merchant FAQs when provided. Also include high-intent search FAQs shoppers type into Google, using ONLY facts from the JSON (or an honest research note when unknown). Required question patterns:
  - "Is {$storeName} genuine leather?" (only if leather appears in product/meta data; otherwise skip)
  - "Where is {$storeName} made?" — mine products[].description, products[].features, meta_description, and faqs for Made in / manufactured / formulated / country-of-origin clues before saying it is not stated. Prefer SKU-level notes over a vague "check packaging" default.
  - "Does {$storeName} ship internationally?"
  - "Is {$storeName} worth buying?"
  - "Does {$storeName} offer student discounts?"
  - "How often does {$storeName} release coupon codes?"
  Plus practical coupon FAQs (promo code required?, code not working?). Do not invent materials, origin, shipping policy, or student discounts.
- Do not paste a full list of current coupon codes in the article body. Mention offer themes/titles if useful, then tell readers live codes appear in the dynamic coupons block (we inject [store_coupons] automatically). Never invent codes.
- HTML only in content: <h2>, <h3>, <p>, <ul>, <li>, <ol>, <table>, <strong>, <em>, <a>, <code>, <img>, <figure>, <figcaption>. No <h1>, no markdown.
- Do not claim star ratings or verified customer reviews unless explicitly in the JSON.

Return valid JSON with exactly these keys:
{
  "title": "string, ONE line only, 45-72 characters, short SEO headline that fits in 2-3 lines on a blog card. No line breaks. No long product lists.",
  "excerpt": "string, 140-220 chars",
  "meta_title": "string, max 70 chars",
  "meta_description": "string, max 160 chars",
  "content": "string, full HTML article body"
}
PROMPT;
    }

    /**
     * @param  array{
     *     store_name: string,
     *     category_name: ?string,
     *     store_slug: string,
     *     affiliate_url: ?string,
     *     offers: array<int, array{code: ?string, title: string, description: ?string, type: string}>,
     *     merchant: array<string, mixed>
     * }  $context
     */
    private function buildStoreDescriptionPrompt(array $context): string
    {
        $siteName = (string) config('site.name');
        $storeName = $context['store_name'];
        $category = $context['category_name'] ?? 'online retail';
        $storeUrl = url('/stores/'.$context['store_slug']);
        $affiliateUrl = filled($context['affiliate_url'] ?? null) ? (string) $context['affiliate_url'] : null;
        $merchant = $context['merchant'];
        $products = is_array($merchant['products'] ?? null) ? $merchant['products'] : [];
        $faqs = is_array($merchant['faqs'] ?? null) ? $merchant['faqs'] : [];
        $offers = $context['offers'];

        $payload = [
            'site_name' => $siteName,
            'store_name' => $storeName,
            'store_url' => $storeUrl,
            'affiliate_url' => $affiliateUrl,
            'category' => $category,
            'domain' => $merchant['domain'] ?? null,
            'meta_description' => $merchant['meta_description'] ?? null,
            'page_title' => $merchant['page_title'] ?? null,
            'product_focus' => (bool) ($merchant['product_focus'] ?? false),
            'banner' => $merchant['banner'] ?? null,
            'logo' => $merchant['logo'] ?? null,
            'products' => ! empty($merchant['product_focus']) ? array_slice($products, 0, 1) : $products,
            'faqs' => $faqs,
            'offers' => collect($offers)->map(fn (array $offer) => [
                'title' => $offer['title'],
                'code' => $offer['code'] ?? null,
                'type' => $offer['type'] ?? (filled($offer['code'] ?? null) ? 'coupon' : 'discount'),
                'description' => $offer['description'] ?? null,
            ])->values()->all(),
        ];

        $factsJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $focusNote = ! empty($merchant['product_focus'])
            ? "- product_focus is true: center the description on the single linked product (not the whole marketplace catalog).\n"
            : '';
        $antiAiRules = $this->antiGenericAiWritingRules($storeName);

        return <<<PROMPT
You are a senior deal editor at {$siteName} writing a retailer profile for U.S. shoppers. Write like a human researcher — specific, useful, and grounded in the JSON facts.

Write ONE English store description using ONLY the facts in the JSON below. Do not invent product specs, prices, reviews, awards, founding stories, or coupon codes that are not in the data.

JSON facts:
{$factsJson}

{$antiAiRules}

Description rules:
- Audience: U.S. online shoppers researching this merchant before they buy.
- CRITICAL: Do NOT use specific calendar dates, months, or years in headings or body (e.g. ban "July 2026", "offers listed for July 2026", "this July guide"). Prefer evergreen phrasing like "current deals", "right now", or "featured offers".
- Length: 700–1,000 words preferred. Prefer specific, useful copy over padded filler. Never invent content just to hit a word count.
{$focusNote}- Include sections: Why Trust Us (EEAT), at-a-glance comparison table, what this store sells (with product headings when products exist), current savings, shopping tips tied to the listed offers, FAQ, short summary.
- REQUIRED: include at least one HTML <table class="comparison-table"> titled like "{$storeName} at a glance" with columns Feature | {$storeName}. Rows must be grounded in JSON (Category, product types such as Wallet/Bags/Belt only if supported by product names, Featured products, Sample listed price, Offers tracked, Shipping, Returns). Unknown shipping/returns => "Confirm on merchant site".
- If 2+ products exist, also include a product comparison table (Feature | each product).
- Place a <h2>Why Trust Us</h2> section near the top (right after the intro). Example tone: "We researched {$storeName} products, customer reviews, pricing and verified coupon availability before publishing this guide." Cover product research, coupon verification, and transparent affiliate disclosure for {$siteName}. Do not invent lab tests, awards, or fake reviewer credentials.
- If banner URL is provided, place it FIRST: <figure class="article-media article-media--banner"><img src="BANNER_URL" alt="{$storeName} official store banner" loading="lazy"><figcaption>{$storeName} store banner</figcaption></figure>
- If logo URL is provided, place it right after the banner: <figure class="article-media article-media--logo"><img src="LOGO_URL" alt="{$storeName} brand logo" width="160" height="160" loading="lazy"><figcaption>{$storeName} logo</figcaption></figure>
- When products are provided, create an <h2> for featured products and an <h3> for EACH product name. Immediately under each product <h3>, include <figure class="article-media article-media--product"><img src="IMAGE_URL" alt="PRODUCT_NAME product photo from {$storeName}" loading="lazy"><figcaption>PRODUCT_NAME</figcaption></figure> when that product has an image URL. Use only image URLs from the JSON — never invent image URLs.
- CRITICAL: under each product <h3>, use that product's OWN description/features/price only. Never reuse the same feature bullets across products. If features look like shared theme USPs (shipping bars, "Individual Products", ingredient-list links), paraphrase the product description instead or omit the list.
- Do NOT keyword-stuff. Mention the full product-name list at most once (preferably only as <h3> headings). Intro, Why Trust Us, and at-a-glance may use product counts instead of repeating every name.
- Include a strong FAQ section with high-intent search questions shoppers type into Google. Required patterns when relevant to the brand/category:
  - "Is {$storeName} genuine leather?" (include only if leather appears in JSON; otherwise skip)
  - "Where is {$storeName} made?" — first mine products[].description, products[].features, meta_description, and faqs for Made in / manufactured / formulated / origin clues. Only if none exist, say a brand-wide origin is not stated publicly and shoppers should check the SKU label — do not invent a country.
  - "Does {$storeName} ship internationally?"
  - "Is {$storeName} worth buying?"
  - "Does {$storeName} offer student discounts?"
  - "How often does {$storeName} release coupon codes?"
  Answer from JSON facts or give a precise research note — never invent origin, shipping coverage, or student discounts.
- Ground major sections in prices, distinct product facts, offer titles/codes, meta_description, or FAQs from the JSON — not by repeating the same product-name string in every paragraph.
- Mention {$siteName} naturally and link to store_url when pointing readers to browse coupons on our site.
- When affiliate_url is provided, include at least 2 natural in-text links to affiliate_url with rel="nofollow sponsored" and target="_blank" when directing readers to shop at the merchant.
- Do not paste a full list of current coupon codes in the description body. You may mention offer themes/titles; live codes are injected via [store_coupons]. Never invent codes.
- HTML only: <h2>, <h3>, <p>, <ul>, <li>, <ol>, <table>, <thead>, <tbody>, <tr>, <th>, <td>, <strong>, <em>, <a>, <code>, <img>, <figure>, <figcaption>. No <h1>, no markdown.
- Do not claim star ratings or verified customer reviews unless explicitly in the JSON.

Return valid JSON with exactly this key:
{
  "content": "string, full HTML store description body"
}
PROMPT;
    }

    private function antiGenericAiWritingRules(string $storeName): string
    {
        return <<<RULES
Anti-generic writing rules (critical for Google EEAT):
- Do NOT write interchangeable brand fluff. Ban patterns like: "{$storeName} offers quality…", "{$storeName} focuses on craftsmanship…", "{$storeName} is known for…", "stands out for", "perfect for every occasion", "elevate your", "seamlessly", "in today's fast-paced world", "whether you're a beginner or a pro".
- Do NOT start multiple consecutive sentences or paragraphs with "{$storeName}".
- Do NOT keyword-stuff by repeating the same product-name list across intro, Why Trust Us, what-sells, comparison, and FAQ. Name products mainly under their own headings; elsewhere prefer counts, category, offers, or one lead example.
- Prefer concrete shopping advice: which listed product fits which need, how a listed coupon/discount works, what to verify at checkout.
- Each product section must use distinct facts for that SKU. Never paste identical feature bullets under every product.
- Vary sentence openings and keep prose concise. Sound like a deal site editor, not a product brochure.
RULES;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array{title: string, excerpt: string, meta_title: string, meta_description: string, content: string}|null
     */
    private function normalizeOutput(array $parsed): ?array
    {
        $title = trim((string) ($parsed['title'] ?? ''));
        $excerpt = trim((string) ($parsed['excerpt'] ?? ''));
        $metaTitle = trim((string) ($parsed['meta_title'] ?? ''));
        $metaDescription = trim((string) ($parsed['meta_description'] ?? ''));
        $content = trim((string) ($parsed['content'] ?? ''));

        if ($title === '' || $excerpt === '' || $content === '') {
            return null;
        }

        if ($metaTitle === '') {
            $metaTitle = Str::limit($title, 70, '');
        }

        if ($metaDescription === '') {
            $metaDescription = Str::limit($excerpt, 160, '');
        }

        return [
            'title' => Post::normalizeTitle($title),
            'excerpt' => Str::limit($excerpt, 500, ''),
            'meta_title' => Str::limit($metaTitle, 70, ''),
            'meta_description' => Str::limit($metaDescription, 320, ''),
            'content' => $content,
        ];
    }
}
