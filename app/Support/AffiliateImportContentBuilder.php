<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Post;
use App\Models\Store;
use App\Support\PostAffiliateContent;
use Illuminate\Support\Str;

final class AffiliateImportContentBuilder
{
    private const STORE_DESCRIPTION_MIN_WORDS = 650;

    private const STORE_DESCRIPTION_MIN_AFFILIATE_LINKS = 2;

    public function __construct(
        private readonly GeminiBlogWriter $geminiWriter = new GeminiBlogWriter(),
        private readonly MerchantProductExtractor $productExtractor = new MerchantProductExtractor(),
    ) {}

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     * @param  array<string, mixed>  $merchant
     */
    public function storeDescription(
        string $storeName,
        string $storeSlug,
        ?string $affiliateUrl,
        ?string $categoryName,
        array $offers,
        array $merchant = [],
    ): string {
        $store = $this->storeContextModel($storeName, $storeSlug, $affiliateUrl, $categoryName);
        $merchant['products'] = $this->usableProducts($merchant);
        $context = $this->storeDescriptionContext($store, $offers, $merchant);

        try {
            $aiContent = $this->geminiWriter->generateStoreDescription($context);

            $content = filled($aiContent)
                ? $aiContent
                : $this->buildStoreDescriptionWithoutAi($store, $offers, $merchant);
        } catch (\Throwable $e) {
            report($e);
            $merchant['products'] = [];
            $content = $this->buildStoreDescriptionWithoutAi($store, $offers, $merchant);
        }

        return $this->finalizeStoreDescription($content, $store, $merchant, $offers);
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     * @param  array<string, mixed>  $merchant
     * @return array{
     *     store_name: string,
     *     category_name: ?string,
     *     store_slug: string,
     *     affiliate_url: ?string,
     *     offers: array<int, array{code: ?string, title: string, description: ?string, type: string}>,
     *     merchant: array<string, mixed>
     * }
     */
    private function storeDescriptionContext(Store $store, array $offers, array $merchant): array
    {
        return [
            'store_name' => $store->name,
            'category_name' => $store->category?->name,
            'store_slug' => $store->slug,
            'affiliate_url' => $store->affiliate_url,
            'offers' => $offers,
            'merchant' => $merchant,
        ];
    }

    private function storeContextModel(
        string $storeName,
        string $storeSlug,
        ?string $affiliateUrl,
        ?string $categoryName,
    ): Store {
        $store = new Store([
            'name' => $storeName,
            'slug' => $storeSlug,
            'affiliate_url' => $affiliateUrl,
        ]);

        if (filled($categoryName)) {
            $store->setRelation('category', new Category(['name' => $categoryName]));
        }

        return $store;
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     * @param  array<string, mixed>  $merchant
     */
    private function buildStoreDescriptionWithoutAi(Store $store, array $offers, array $merchant): string
    {
        $category = $store->category?->name ?? ($merchant['category_name'] ?? 'online retail');
        $faqs = is_array($merchant['faqs'] ?? null) ? $merchant['faqs'] : [];

        $content = $this->buildLongFormContent(
            $store,
            $offers,
            $category,
            $merchant['meta_description'] ?? null,
            $faqs,
            $merchant
        );

        if ($this->wordCount($content) < self::STORE_DESCRIPTION_MIN_WORDS) {
            $content .= "\n\n".$this->sectionCheckoutChecklist($store);
        }

        return $content;
    }

    private function finalizeStoreDescription(string $content, Store $store, array $merchant = [], array $offers = []): string
    {
        $content = $this->ensureArticleImages($content, $merchant, $store->name);
        $content = $this->ensureWhyTrustUs($content, $store->name, $merchant, $offers);
        $content = $this->ensureComparisonTables(
            $content,
            $store->name,
            $merchant,
            $offers,
            $store->category?->name ?? ($merchant['category_name'] ?? null)
        );
        $content = $this->ensureSearchIntentFaqs($content, $store->name, $merchant, $offers, $store);
        $content = PostAffiliateContent::embed($content, $store);
        $content = $this->ensureAffiliateLinks($content, $store, self::STORE_DESCRIPTION_MIN_AFFILIATE_LINKS);

        // Do not pad with generic brand fluff — short and specific beats long and interchangeable.
        if ($this->wordCount($content) < self::STORE_DESCRIPTION_MIN_WORDS
            && ! preg_match('/<h2[^>]*>\s*Checkout checklist/i', $content)) {
            $content .= "\n\n".$this->sectionCheckoutChecklist($store);
        }

        return DynamicCouponContent::ensurePlaceholder($content, $store->name);
    }

    private function ensureAffiliateLinks(string $html, Store $store, int $minimum): string
    {
        if (! filled($store->affiliate_url)) {
            return $html;
        }

        $affiliateUrl = (string) $store->affiliate_url;
        $count = substr_count($html, $affiliateUrl);
        $name = $store->name;

        if ($count < 1) {
            $html .= '<p>Ready to place an order? '
                .'<a href="'.e($affiliateUrl).'" rel="nofollow sponsored" target="_blank">Shop at '.e($name).' through our affiliate link</a> '
                .'to browse the official catalog with tracking support from '.e(config('site.name')).'.</p>';
            $count++;
        }

        if ($count < $minimum) {
            $html .= '<p>For repeat purchases, bookmark our '
                .'<a href="'.e($affiliateUrl).'" rel="nofollow sponsored" target="_blank">tracked '.e($name).' shopping link</a> '
                .'so you can return directly to the merchant while supporting '.e(config('site.name')).'.</p>';
        }

        return $html;
    }

    private function sectionCheckoutChecklist(Store $store): string
    {
        $name = $store->name;
        $site = (string) config('site.name');
        $parts = [];

        $parts[] = '<h2>Checkout checklist for '.e($name).'</h2>';
        $parts[] = '<p>Before you pay, run through this quick list so the final cart total matches what you expected on '.e($site).':</p>';
        $parts[] = '<ul>';
        $parts[] = '<li>Confirm the exact product name, size, color, or kit version on the merchant product page.</li>';
        $parts[] = '<li>Check whether a featured coupon code still applies — exclusions and minimum spend rules change.</li>';
        $parts[] = '<li>Compare the cart total with and without the promotion, including shipping.</li>';
        $parts[] = '<li>Note return/warranty terms on the merchant site if the item is a gift or higher-ticket purchase.</li>';
        $parts[] = '</ul>';

        if (filled($store->affiliate_url)) {
            $parts[] = '<p>When you are ready, use our '
                .'<a href="'.e((string) $store->affiliate_url).'" rel="nofollow sponsored" target="_blank">tracked link to shop '.e($name).'</a>.</p>';
        }

        return implode("\n", $parts);
    }

    private function wordCount(string $html): int
    {
        return str_word_count(strip_tags($html));
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     * @param  array<string, mixed>  $merchant
     * @return array{title: string, excerpt: string, meta_title: string, meta_description: string, content: string}
     */
    public function blogPost(Store $store, array $offers, array $merchant = [], ?array $preGenerated = null): array
    {
        $merchant['products'] = $this->usableProducts($merchant);

        if ($preGenerated !== null) {
            return $this->sanitizeBlogOutput($preGenerated, $store, $merchant, $offers);
        }

        try {
            $aiBlog = $this->geminiWriter->generate($this->blogContext($store, $offers, $merchant));

            if ($aiBlog !== null) {
                return $this->sanitizeBlogOutput($aiBlog, $store, $merchant, $offers);
            }

            return $this->sanitizeBlogOutput($this->blogPostWithoutAi($store, $offers, $merchant), $store, $merchant, $offers);
        } catch (\Throwable $e) {
            report($e);

            $merchant['products'] = [];

            return $this->sanitizeBlogOutput($this->classicBlogPost($store, $offers, $merchant), $store, $merchant, $offers);
        }
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     * @param  array<string, mixed>  $merchant
     * @return array{
     *     store_name: string,
     *     category_name: ?string,
     *     store_slug: string,
     *     offers: array<int, array{code: ?string, title: string, description: ?string, type: string}>,
     *     merchant: array<string, mixed>
     * }
     */
    public function blogContext(Store $store, array $offers, array $merchant = []): array
    {
        return [
            'store_name' => $store->name,
            'category_name' => $store->category?->name ?? ($merchant['category_name'] ?? null),
            'store_slug' => $store->slug,
            'affiliate_url' => $store->affiliate_url,
            'offers' => $offers,
            'merchant' => $merchant,
        ];
    }

    /**
     * @param  array<string, mixed>  $blog
     * @return array{title: string, excerpt: string, meta_title: string, meta_description: string, content: string}
     */
    public function sanitizeBlogOutput(array $blog, ?Store $store = null, array $merchant = [], array $offers = []): array
    {
        $storeName = $store?->name ?? (string) ($merchant['name'] ?? 'Store');
        $content = trim((string) ($blog['content'] ?? ''));
        $content = $this->ensureArticleImages($content, $merchant, $storeName);
        $content = $this->ensureWhyTrustUs($content, $storeName, $merchant, $offers);
        $content = $this->ensureComparisonTables($content, $storeName, $merchant, $offers, $store?->category?->name);
        $content = $this->ensureSearchIntentFaqs($content, $storeName, $merchant, $offers, $store);

        if ($store !== null) {
            $content = PostAffiliateContent::embed($content, $store);
            $content = DynamicCouponContent::ensurePlaceholder($content, $store->name);
        }

        return [
            'title' => Post::normalizeTitle(trim((string) ($blog['title'] ?? ''))),
            'excerpt' => Str::limit(trim((string) ($blog['excerpt'] ?? '')), 500, ''),
            'meta_title' => Str::limit(trim((string) ($blog['meta_title'] ?? $blog['title'] ?? '')), 70, ''),
            'meta_description' => Str::limit(trim((string) ($blog['meta_description'] ?? $blog['excerpt'] ?? '')), 320, ''),
            'content' => $content,
        ];
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     * @param  array<string, mixed>  $merchant
     * @return array{title: string, excerpt: string, meta_title: string, meta_description: string, content: string, source?: string}|null
     */
    public function generateBlogPreview(Store $store, array $offers, array $merchant = []): ?array
    {
        $merchant['products'] = $this->usableProducts($merchant);

        try {
            $aiBlog = $this->geminiWriter->generate($this->blogContext($store, $offers, $merchant));

            if ($aiBlog !== null) {
                return $this->sanitizeBlogOutput($aiBlog, $store, $merchant, $offers) + [
                    'source' => $aiBlog['source'] ?? (new AiChatClient())->activeProvider(),
                ];
            }

            $fallback = $this->blogPostWithoutAi($store, $offers, $merchant);

            return $this->sanitizeBlogOutput($fallback, $store, $merchant, $offers) + ['source' => 'template'];
        } catch (\Throwable $e) {
            report($e);

            // SaaS / no-catalog merchants must still get a usable brand+offers article.
            $merchant['products'] = [];
            $fallback = $this->classicBlogPost($store, $offers, $merchant);

            return $this->sanitizeBlogOutput($fallback, $store, $merchant, $offers) + ['source' => 'template'];
        }
    }

    /**
     * @param  array<string, mixed>  $merchant
     * @return array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string, features?: list<string>}>
     */
    private function usableProducts(array $merchant): array
    {
        $products = is_array($merchant['products'] ?? null) ? $merchant['products'] : [];
        $products = array_values(array_filter($products, fn ($product) => is_array($product)));
        $products = $this->productExtractor->uniqueTake($products, max(count($products), 1));

        if (! empty($merchant['product_focus'])) {
            $products = array_slice($products, 0, 1);
        }

        return $products;
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     * @param  array<string, mixed>  $merchant
     * @return array{title: string, excerpt: string, meta_title: string, meta_description: string, content: string}
     */
    private function blogPostWithoutAi(Store $store, array $offers, array $merchant = []): array
    {
        $products = $this->usableProducts($merchant);
        $merchant['products'] = $products;

        if (count($products) >= 2) {
            return $this->comparisonBlogPost($store, $offers, $merchant, $products);
        }

        if (count($products) === 1) {
            return $this->spotlightBlogPost($store, $offers, $merchant, $products[0]);
        }

        return $this->classicBlogPost($store, $offers, $merchant);
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     * @param  array<string, mixed>  $merchant
     * @param  array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>  $products
     * @return array{title: string, excerpt: string, meta_title: string, meta_description: string, content: string}
     */
    private function comparisonBlogPost(Store $store, array $offers, array $merchant, array $products): array
    {
        $category = $store->category?->name ?? ($merchant['category_name'] ?? 'online retail');
        $metaDescription = $merchant['meta_description'] ?? null;
        $faqs = is_array($merchant['faqs'] ?? null) ? $merchant['faqs'] : [];
        $offerCount = count($offers);
        $name = $store->name;
        $storeUrl = route('stores.show', $store->slug);
        $productNames = collect($products)->pluck('name')->map(fn (string $name) => Str::limit($name, 35, '…'))->take(2)->all();
        $comparisonTitle = implode(' vs ', $productNames);
        if (count($products) > 2) {
            $comparisonTitle .= ' & more';
        }

        $title = Post::normalizeTitle("{$comparisonTitle}: Which Is Best?");
        $excerpt = "Side-by-side comparison of top {$name} products for U.S. shoppers — features, pricing, pros and cons, plus {$offerCount} current coupon codes.";
        $metaTitle = Str::limit("{$name} Product Comparison | " . config('site.name'), 70, '');
        $metaDescriptionSeo = Str::limit(
            "Compare {$comparisonTitle} from {$name}. See prices, strengths, drawbacks, and the best current deals.",
            320,
            ''
        );

        $parts = [];
        $parts[] = $this->storeBannerHtml($merchant, $name);
        $parts[] = $this->storeLogoHtml($merchant, $name);
        $parts[] = '<p>This side-by-side looks at <strong>'.e($comparisonTitle).'</strong> using details from the public '
            .e($name).' product pages, then maps them to the current offers on '.e(config('site.name')).'.</p>';

        $parts[] = $this->sectionWhyTrustUs($name, $products, $offers);

        if ($metaDescription) {
            $parts[] = '<p><strong>Merchant positioning:</strong> '.e($metaDescription).'</p>';
        }

        $parts[] = $this->sectionStoreAtAGlanceTable($name, $category, $products, $offers, $faqs, $metaDescription, $merchant);
        $parts[] = $this->sectionProductComparisonTable($name, $products, $category);
        $parts[] = $this->sectionProductDeepDives($name, $products, $storeUrl);
        $parts[] = $this->sectionWhichProductToChoose($name, $products, $category);
        $parts[] = $this->sectionCurrentOffers($name, $offers, $storeUrl, $store->affiliate_url);
        $parts[] = $this->sectionHowToSave($name, $storeUrl, $store->affiliate_url);
        $parts[] = $this->sectionFaq($name, $faqs, $storeUrl, $products, $offers, $merchant);
        $parts[] = $this->sectionCheckoutChecklist($store);

        $parts[] = '<h2>Final verdict</h2>';
        $parts[] = '<p>Pick the listing whose price and description match your use case, then apply a verified deal from our '
            . '<a href="' . e($storeUrl) . '">' . e($name) . ' coupon page</a> and re-check the cart total before paying.</p>';

        return [
            'title' => $title,
            'excerpt' => $excerpt,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescriptionSeo,
            'content' => implode("\n\n", array_filter($parts)),
        ];
    }

    /**
     * @param  array{code: ?string, title: string, description: ?string, type: string}  $offer
     * @param  array<string, mixed>  $merchant
     * @param  array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}  $product
     * @return array{title: string, excerpt: string, meta_title: string, meta_description: string, content: string}
     */
    private function spotlightBlogPost(Store $store, array $offers, array $merchant, array $product): array
    {
        $name = $store->name;
        $storeUrl = route('stores.show', $store->slug);
        $faqs = is_array($merchant['faqs'] ?? null) ? $merchant['faqs'] : [];
        $productName = $product['name'];

        $title = Post::normalizeTitle("{$productName} Review: Is It Worth It?");
        $excerpt = "Hands-on style breakdown of {$productName} from {$name} — key features, pros, cons, and the best current coupons.";
        $metaTitle = Str::limit("{$productName} Review | " . config('site.name'), 70, '');
        $metaDescriptionSeo = Str::limit(
            "Read our {$productName} review with pricing, pros, cons, and verified {$name} coupon codes.",
            320,
            ''
        );

        $parts = [];
        $parts[] = $this->storeBannerHtml($merchant, $name);
        $parts[] = $this->storeLogoHtml($merchant, $name);
        $parts[] = '<p>This overview of <strong>'.e($productName).'</strong> uses the merchant\'s public product listing'
            .(filled($product['price'] ?? null) ? ' (listed around '.e((string) $product['price']).')' : '')
            .' and the current '.e($name).' offers tracked on '.e(config('site.name')).'.</p>';

        $parts[] = $this->sectionWhyTrustUs($name, [$product], $offers);

        $parts[] = $this->productImageHtml($product, $name);

        $category = $store->category?->name ?? ($merchant['category_name'] ?? 'online retail');
        $parts[] = $this->sectionStoreAtAGlanceTable(
            $name,
            $category,
            [$product],
            $offers,
            $faqs,
            $merchant['meta_description'] ?? null,
            $merchant
        );
        $parts[] = $this->sectionProductSpecTable($product, $name);

        if (filled($product['description'])) {
            $parts[] = '<p>' . e($product['description']) . '</p>';
        }

        if (filled($product['price'])) {
            $parts[] = '<p><strong>Listed price:</strong> ' . e($product['price']) . ' (confirm on the merchant site before checkout).</p>';
        }

        $parts[] = $this->sectionSingleProductProsCons($productName, $product);
        $parts[] = $this->sectionCurrentOffers($name, $offers, $storeUrl, $store->affiliate_url);
        $parts[] = $this->sectionHowToSave($name, $storeUrl, $store->affiliate_url);
        $parts[] = $this->sectionFaq($name, $faqs, $storeUrl, [$product], $offers, $merchant);
        $parts[] = $this->sectionCheckoutChecklist($store);

        return [
            'title' => $title,
            'excerpt' => $excerpt,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescriptionSeo,
            'content' => implode("\n\n", array_filter($parts)),
        ];
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     * @param  array<string, mixed>  $merchant
     * @return array{title: string, excerpt: string, meta_title: string, meta_description: string, content: string}
     */
    private function classicBlogPost(Store $store, array $offers, array $merchant = []): array
    {
        $category = $store->category?->name ?? ($merchant['category_name'] ?? 'online retail');
        $metaDescription = $merchant['meta_description'] ?? null;
        $faqs = $merchant['faqs'] ?? [];
        $offerCount = count($offers);

        $title = Post::normalizeTitle("{$store->name} Review: Products, Pros, Coupons & FAQ");

        $excerpt = "In-depth {$store->name} guide for U.S. shoppers: brand overview, five key advantages, "
            . "best current deals, category comparisons, shopper insights, and {$offerCount} featured offers on "
            . config('site.name') . '.';

        $metaTitle = Str::limit("{$store->name} Review & Coupons | " . config('site.name'), 70, '');
        $metaDescriptionSeo = Str::limit(
            "Read our {$store->name} review with pros, product highlights, comparisons, FAQs, and {$offerCount} verified coupon codes.",
            320,
            ''
        );

        $content = $this->buildLongFormContent(
            $store,
            $offers,
            $category,
            $metaDescription,
            is_array($faqs) ? $faqs : [],
            $merchant
        );

        return [
            'title' => $title,
            'excerpt' => $excerpt,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescriptionSeo,
            'content' => $content,
        ];
    }

    /**
     * Feature | Brand at-a-glance table (Google-friendly structured comparison).
     *
     * @param  array<int, array{name?: string, description?: ?string, price?: ?string, image?: ?string, url?: ?string, features?: list<string>}>  $products
     * @param  array<int, array{code?: ?string, title?: string, description?: ?string, type?: string}>  $offers
     * @param  array<int, array{question?: string, answer?: string}>  $faqs
     * @param  array<string, mixed>  $merchant
     */
    private function sectionStoreAtAGlanceTable(
        string $storeName,
        string $category,
        array $products,
        array $offers,
        array $faqs = [],
        ?string $metaDescription = null,
        array $merchant = [],
    ): string {
        $rows = $this->buildStoreAtAGlanceRows($storeName, $category, $products, $offers, $faqs, $metaDescription, $merchant);

        if ($rows === []) {
            return '';
        }

        $parts = [];
        $parts[] = '<h2>'.e($storeName).' at a glance</h2>';
        $parts[] = '<p>Quick facts from the merchant listings and offers tracked on '.e((string) config('site.name')).':</p>';
        $parts[] = '<table class="comparison-table"><thead><tr><th>Feature</th><th>'.e($storeName).'</th></tr></thead><tbody>';

        foreach ($rows as $row) {
            $parts[] = '<tr><td><strong>'.e($row[0]).'</strong></td><td>'.e($row[1]).'</td></tr>';
        }

        $parts[] = '</tbody></table>';

        return implode("\n", $parts);
    }

    /**
     * @param  array<int, array{name?: string, description?: ?string, price?: ?string, image?: ?string, url?: ?string, features?: list<string>}>  $products
     * @param  array<int, array{code?: ?string, title?: string, description?: ?string, type?: string}>  $offers
     * @param  array<int, array{question?: string, answer?: string}>  $faqs
     * @param  array<string, mixed>  $merchant
     * @return list<array{0: string, 1: string}>
     */
    private function buildStoreAtAGlanceRows(
        string $storeName,
        string $category,
        array $products,
        array $offers,
        array $faqs,
        ?string $metaDescription,
        array $merchant,
    ): array {
        $haystack = Str::lower(collect($products)->map(function (array $product) {
            $bits = [
                (string) ($product['name'] ?? ''),
                (string) ($product['description'] ?? ''),
                implode(' ', is_array($product['features'] ?? null) ? $product['features'] : []),
            ];

            return implode(' ', $bits);
        })->implode(' ').' '.Str::lower((string) $metaDescription).' '.Str::lower($category));

        $typeChecks = [
            'Leather' => '/\bleather\b/i',
            'Wallet' => '/\bwallet[s]?\b/i',
            'Bags' => '/\b(bag|bags|handbag|tote|purse|messenger)\b/i',
            'Backpack' => '/\bbackpack[s]?\b/i',
            'Belt' => '/\bbelt[s]?\b/i',
            'Electronics' => '/\b(engine|turbofan|charger|gadget|electronic|device|model kit)\b/i',
        ];

        $rows = [];
        $rows[] = ['Category', $category !== '' ? $category : 'Online retail'];

        if (filled($merchant['domain'] ?? null)) {
            $rows[] = ['Official domain', (string) $merchant['domain']];
        }

        foreach ($typeChecks as $label => $pattern) {
            if (preg_match($pattern, $haystack)) {
                $rows[] = [$label, 'Yes'];
            }
        }

        if (preg_match('/\bgenuine\s+leather\b/i', $haystack)) {
            $rows[] = ['Leather type', 'Genuine leather (per product copy)'];
        } elseif (preg_match('/\bfaux\s+leather|vegan\s+leather|pu\s+leather\b/i', $haystack)) {
            $rows[] = ['Leather type', 'Faux / vegan leather (per product copy)'];
        }

        $namedCount = collect($products)->pluck('name')->filter()->count();
        if ($namedCount > 0) {
            $rows[] = ['Featured products', $namedCount.' listing'.($namedCount === 1 ? '' : 's').' compared below'];
        }

        $priced = collect($products)->first(fn (array $p) => filled($p['price'] ?? null));
        if (is_array($priced)) {
            $rows[] = ['Sample listed price', (string) $priced['price']];
        }

        if ($offers !== []) {
            $offerTitles = collect($offers)->pluck('title')->filter()->take(2)->implode('; ');
            $rows[] = ['Offers tracked', count($offers).($offerTitles !== '' ? ' — '.$offerTitles : '')];
        }

        $shipping = $this->faqAnswerMatching($faqs, '/ship|deliver|worldwide|international|postage/i');
        $rows[] = ['Shipping', $shipping ?? 'Confirm shipping options on merchant checkout'];

        $returns = $this->faqAnswerMatching($faqs, '/return|refund|exchange|warranty/i');
        $rows[] = ['Returns', $returns ?? 'Confirm return policy on the merchant site'];

        return $rows;
    }

    /**
     * @param  array<int, array{question?: string, answer?: string}>  $faqs
     * @param  list<string>  $extraTexts  Extra haystacks (meta, product descriptions/features).
     */
    private function faqAnswerMatching(array $faqs, string $pattern, array $extraTexts = []): ?string
    {
        foreach ($faqs as $faq) {
            $question = (string) ($faq['question'] ?? '');
            $answer = (string) ($faq['answer'] ?? '');
            if ($answer === '') {
                continue;
            }

            if (preg_match($pattern, $question) || preg_match($pattern, $answer)) {
                return Str::limit(HtmlCleaner::textFromHtml($answer), 180);
            }
        }

        foreach ($extraTexts as $text) {
            $text = HtmlCleaner::textFromHtml((string) $text);
            if ($text === '' || ! preg_match($pattern, $text)) {
                continue;
            }

            foreach (preg_split('/(?<=[.!?])\s+/', $text) ?: [] as $sentence) {
                $sentence = trim($sentence);
                if ($sentence !== '' && preg_match($pattern, $sentence)) {
                    return Str::limit($sentence, 180);
                }
            }

            return Str::limit($text, 180);
        }

        return null;
    }

    /**
     * Prefer merchant FAQ, then product/meta copy that mentions Made in / manufactured in.
     *
     * @param  array<int, array{question?: string, answer?: string}>  $merchantFaqs
     * @param  array<int, array{name?: string, description?: ?string, features?: list<string>}>  $products
     */
    private function originFaqAnswer(string $name, array $merchantFaqs, array $products, ?string $metaDescription): string
    {
        $extra = [(string) $metaDescription];
        foreach ($products as $product) {
            $extra[] = (string) ($product['description'] ?? '');
            $extra[] = implode(' ', is_array($product['features'] ?? null) ? $product['features'] : []);
        }

        $fromFaq = $this->faqAnswerMatching($merchantFaqs, '/made|manufactur|origin|where.*made|country|formulated in|crafted in/i', $extra);
        if ($fromFaq) {
            return e($fromFaq).(str_contains(Str::lower($fromFaq), 'confirm') ? '' : ' Confirm on the specific product page if you need origin for one SKU.');
        }

        $haystack = implode(' ', array_filter($extra));

        if (preg_match('/\bmade in ([A-Za-z][A-Za-z .-]{1,40}?)(?:\.|,|;|\n|$)/i', $haystack, $match)) {
            return e('Product copy we reviewed mentions Made in '.trim($match[1]).'. Origin can vary by SKU — verify on the listing or packaging for the item you buy.');
        }

        if (preg_match('/\b(manufactured|formulated|crafted|produced)\s+in\s+([A-Za-z][A-Za-z .-]{1,40}?)(?:\.|,|;|\n|$)/i', $haystack, $match)) {
            return e('Merchant copy references products '.trim($match[1]).' in '.trim($match[2]).'. Treat that as SKU-level evidence and re-check the product page before buying.');
        }

        if (preg_match('/\b(USA|U\.S\.A\.|United States)-?\s*(made|formulated|crafted)\b|\b(made|formulated|crafted)\s+in\s+the\s+(USA|U\.S\.A\.|United States)\b/i', $haystack)) {
            return e('Some '.$name.' product or brand copy references U.S. made/formulated language. Confirm whether that applies to the exact SKU in your cart.');
        }

        return e('We did not find a clear, brand-wide country-of-origin statement on the public pages collected for this guide. That is common for multi-SKU catalogs — check the product page, ingredient/label section, or help center for the SKU you want rather than assuming one origin for every item.');
    }

    /**
     * @param  array{name?: string, description?: ?string, price?: ?string, image?: ?string, url?: ?string, features?: list<string>}  $product
     */
    private function sectionProductSpecTable(array $product, string $storeName): string
    {
        $name = (string) ($product['name'] ?? 'Product');
        $rows = [];

        if (filled($product['price'] ?? null)) {
            $rows[] = ['Listed price', (string) $product['price']];
        }

        $features = is_array($product['features'] ?? null) ? array_values(array_filter($product['features'])) : [];
        foreach (array_slice($features, 0, 6) as $index => $feature) {
            $rows[] = ['Detail '.($index + 1), Str::limit((string) $feature, 160)];
        }

        if (filled($product['url'] ?? null)) {
            $rows[] = ['Product page', 'Official '.$storeName.' listing'];
        }

        if ($rows === []) {
            return '';
        }

        $parts = [];
        $parts[] = '<h2>'.e($name).' specs snapshot</h2>';
        $parts[] = '<table class="comparison-table"><thead><tr><th>Feature</th><th>Details</th></tr></thead><tbody>';

        foreach ($rows as $row) {
            $parts[] = '<tr><td><strong>'.e($row[0]).'</strong></td><td>'.e($row[1]).'</td></tr>';
        }

        $parts[] = '</tbody></table>';

        return implode("\n", $parts);
    }

    /**
     * Inject Feature|Brand and/or product comparison tables when Gemini omits them.
     *
     * @param  array<string, mixed>  $merchant
     * @param  array<int, array{code?: ?string, title?: string, description?: ?string, type?: string}>  $offers
     */
    private function ensureComparisonTables(
        string $content,
        string $storeName,
        array $merchant = [],
        array $offers = [],
        ?string $category = null,
    ): string {
        $content = trim($content);

        if ($content === '') {
            return $content;
        }

        $products = is_array($merchant['products'] ?? null) ? $merchant['products'] : [];
        if (! empty($merchant['product_focus'])) {
            $products = array_slice($products, 0, 1);
        }

        $faqs = is_array($merchant['faqs'] ?? null) ? $merchant['faqs'] : [];
        $categoryName = $category ?: (string) ($merchant['category_name'] ?? 'online retail');

        if (! preg_match('/<table\b[^>]*class=["\'][^"\']*comparison-table/i', $content)
            && ! preg_match('/<h2[^>]*>[^<]*at a glance/i', $content)) {
            $table = $this->sectionStoreAtAGlanceTable(
                $storeName,
                $categoryName,
                $products,
                $offers,
                $faqs,
                $merchant['meta_description'] ?? null,
                $merchant
            );

            if ($table !== '') {
                if (preg_match('/(<h2[^>]*>\s*Why\s+Trust\s+Us\s*<\/h2>.*?<\/ul>)/is', $content, $match, PREG_OFFSET_CAPTURE)) {
                    $pos = $match[0][1] + strlen($match[0][0]);
                    $content = trim(substr($content, 0, $pos)."\n\n".$table."\n\n".ltrim(substr($content, $pos)));
                } elseif (preg_match('/<\/p>/i', $content, $match, PREG_OFFSET_CAPTURE)) {
                    $pos = $match[0][1] + strlen($match[0][0]);
                    $content = trim(substr($content, 0, $pos)."\n\n".$table."\n\n".ltrim(substr($content, $pos)));
                } else {
                    $content = $table."\n\n".$content;
                }
            }
        }

        if (count($products) >= 2
            && ! preg_match('/<h2[^>]*>[^<]*(Quick Comparison|Product comparison|Compare)/i', $content)
            && substr_count(Str::lower($content), '<table') < 2) {
            $productTable = $this->sectionProductComparisonTable($storeName, $products, $categoryName);

            if (preg_match('/(<h2[^>]*>[^<]*at a glance[^<]*<\/h2>.*?<\/table>)/is', $content, $match, PREG_OFFSET_CAPTURE)) {
                $pos = $match[0][1] + strlen($match[0][0]);
                $content = trim(substr($content, 0, $pos)."\n\n".$productTable."\n\n".ltrim(substr($content, $pos)));
            } else {
                $content .= "\n\n".$productTable;
            }
        }

        return trim($content);
    }

    /**
     * @param  array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>  $products
     */
    private function sectionProductComparisonTable(string $brandName, array $products, string $category): string
    {
        $parts = [];
        $parts[] = '<h2>Quick comparison: top '.e($brandName).' products</h2>';
        $parts[] = '<p>Use this table to compare headline details before reading the full breakdown below.</p>';
        $parts[] = '<table class="comparison-table"><thead><tr><th>Feature</th>';

        foreach ($products as $product) {
            $parts[] = '<th>'.e(Str::limit((string) $product['name'], 40)).'</th>';
        }

        $parts[] = '</tr></thead><tbody>';

        $parts[] = '<tr><td><strong>Best for</strong></td>';
        foreach ($products as $index => $product) {
            $bestFor = match ($index) {
                0 => 'Lead / flagship pick in '.strtolower($category),
                1 => 'Balanced alternative',
                default => 'Specialized / niche pick',
            };
            $parts[] = '<td>'.e($bestFor).'</td>';
        }
        $parts[] = '</tr>';

        $parts[] = '<tr><td><strong>Listed price</strong></td>';
        foreach ($products as $product) {
            $parts[] = '<td>'.e($product['price'] ?? 'See merchant site').'</td>';
        }
        $parts[] = '</tr>';

        $parts[] = '<tr><td><strong>Product page</strong></td>';
        foreach ($products as $product) {
            $parts[] = '<td>'.(filled($product['url'] ?? null) ? 'Official listing available' : 'See merchant catalog').'</td>';
        }
        $parts[] = '</tr>';

        $parts[] = '<tr><td><strong>Photo in this guide</strong></td>';
        foreach ($products as $product) {
            $parts[] = '<td>'.(filled($product['image'] ?? null) ? 'Yes' : 'Not scraped').'</td>';
        }
        $parts[] = '</tr>';

        $parts[] = '</tbody></table>';

        return implode("\n", $parts);
    }

    /**
     * @param  array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>  $products
     */
    private function sectionProductDeepDives(string $brandName, array $products, string $storeUrl): string
    {
        $parts = [];
        $parts[] = '<h2>Product details from the merchant listings</h2>';

        foreach ($products as $index => $product) {
            $productName = (string) ($product['name'] ?? 'Product');
            $parts[] = '<h3>' . ($index + 1) . '. ' . e($productName) . '</h3>';
            $parts[] = $this->productImageHtml($product, $brandName);

            if (filled($product['description'] ?? null)) {
                $parts[] = '<p>' . e(Str::limit(strip_tags((string) $product['description']), 500)) . '</p>';
            }

            if (filled($product['price'] ?? null)) {
                $parts[] = '<p><strong>Listed price:</strong> ' . e((string) $product['price']) . ' (confirm live pricing at checkout).</p>';
            }

            $features = is_array($product['features'] ?? null) ? array_values(array_filter($product['features'])) : [];
            if ($features !== []) {
                $parts[] = '<p><strong>Details pulled from the product page:</strong></p><ul>';
                foreach (array_slice($features, 0, 6) as $feature) {
                    $parts[] = '<li>' . e(Str::limit((string) $feature, 180)) . '</li>';
                }
                $parts[] = '</ul>';
            } elseif (! filled($product['description'] ?? null)) {
                $parts[] = '<p>No long description was available on the scraped product page. Open the merchant listing to confirm specs before buying.</p>';
            }

            if (filled($product['url'] ?? null)) {
                $parts[] = '<p><a href="' . e((string) $product['url']) . '" rel="nofollow sponsored">Open ' . e($productName) . ' on ' . e($brandName) . ' →</a></p>';
            }
        }

        $parts[] = '<p>Featured coupons for this brand are listed on our <a href="' . e($storeUrl) . '">' . e($brandName) . ' deals page</a>.</p>';

        return implode("\n", $parts);
    }

    /**
     * @param  array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string, features?: list<string>}>  $products
     */
    private function sectionWhichProductToChoose(string $brandName, array $products, string $category): string
    {
        $parts = [];
        $parts[] = '<h2>Which listing fits your cart?</h2>';
        $parts[] = '<ul>';

        foreach ($products as $index => $product) {
            $productName = (string) ($product['name'] ?? 'Product');
            $hint = match ($index) {
                0 => filled($product['price'] ?? null)
                    ? 'if you want the lead listing at about '.$product['price']
                    : 'if you want the first featured listing in this comparison',
                1 => 'if you are weighing a second option against the lead product',
                default => 'if you need a more specialized variant than the first two picks',
            };

            if (filled($product['description'] ?? null)) {
                $snippet = Str::limit(strip_tags((string) $product['description']), 120);
                $parts[] = '<li><strong>Choose '.e($productName).'</strong> '.$hint.'. Merchant copy notes: '.e($snippet).'</li>';
            } else {
                $parts[] = '<li><strong>Choose '.e($productName).'</strong> '.$hint.'.</li>';
            }
        }

        $parts[] = '</ul>';
        $parts[] = '<p>In the '.e(strtolower($category)).' category, pick based on the listed specs and price above — then apply a coupon from our '.e($brandName).' deals page if one fits your cart.</p>';

        return implode("\n", $parts);
    }

    /**
     * @param  array{name: string, description: ?string, price: ?string, image: ?string, url: ?string, features?: list<string>}  $product
     */
    private function sectionSingleProductProsCons(string $productName, array $product): string
    {
        $parts = [];
        $parts[] = '<h2>Pros &amp; cons for '.e($productName).'</h2>';
        $parts[] = '<h3>Pros</h3><ul>';

        if (filled($product['price'] ?? null)) {
            $parts[] = '<li>Public listing shows a price of '.e((string) $product['price']).' to compare against sale pricing.</li>';
        }
        if (filled($product['description'] ?? null)) {
            $parts[] = '<li>Merchant description is available to review before purchase: '.e(Str::limit(strip_tags((string) $product['description']), 160)).'</li>';
        }
        $features = is_array($product['features'] ?? null) ? array_slice($product['features'], 0, 2) : [];
        foreach ($features as $feature) {
            $parts[] = '<li>'.e(Str::limit((string) $feature, 160)).'</li>';
        }
        $parts[] = '<li>Sold through the official product page, so listed promo codes are easier to match at checkout.</li>';
        $parts[] = '</ul>';

        $parts[] = '<h3>Cons</h3><ul>';
        $parts[] = '<li>Live price and stock can change — re-check the merchant cart before paying.</li>';
        if (! filled($product['description'] ?? null) && $features === []) {
            $parts[] = '<li>Limited scraped detail on this listing; open the product URL for full specs.</li>';
        }
        $parts[] = '<li>Some promotions exclude sale items or specific SKUs — confirm eligibility when applying a code.</li>';
        $parts[] = '</ul>';

        if (filled($product['url'] ?? null)) {
            $parts[] = '<p><a href="' . e((string) $product['url']) . '" rel="nofollow sponsored">Check ' . e($productName) . ' availability →</a></p>';
        }

        return implode("\n", $parts);
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     * @param  array<int, array{question: string, answer: string}>  $merchantFaqs
     * @param  array<string, mixed>  $merchant
     */
    private function buildLongFormContent(
        Store $store,
        array $offers,
        string $category,
        ?string $metaDescription,
        array $merchantFaqs,
        array $merchant = []
    ): string {
        $name = $store->name;
        $storeUrl = route('stores.show', $store->slug);
        $parts = [];

        $products = is_array($merchant['products'] ?? null) ? $merchant['products'] : [];
        if (! empty($merchant['product_focus'])) {
            $products = array_slice($products, 0, 1);
        }

        $parts[] = $this->storeBannerHtml($merchant, $name);
        $parts[] = $this->storeLogoHtml($merchant, $name);

        $productCount = collect($products)->pluck('name')->filter()->count();
        $offerLabel = collect($offers)->pluck('title')->filter()->take(2)->implode(', ');

        $intro = 'This guide covers shopping at <strong>'.e($name).'</strong>';
        if ($productCount > 0) {
            $intro .= ', with '.$productCount.' featured product'.($productCount === 1 ? '' : 's').' researched from the merchant catalog';
        }
        $intro .= ', plus the current deals tracked on '.e(config('site.name'));
        if ($offerLabel !== '') {
            $intro .= ' (such as '.e($offerLabel).')';
        }
        $intro .= '. Details below come from the merchant\'s public pages and the offers listed here — not generic brand claims.';
        $parts[] = '<p>'.$intro.'</p>';

        $parts[] = $this->sectionWhyTrustUs($name, $products, $offers);

        if ($metaDescription) {
            $parts[] = '<p><strong>Merchant positioning:</strong> '.e($metaDescription).'</p>';
        }

        $parts[] = '<p>Browse live coupons on our <a href="'.e($storeUrl).'">'.e($name).' deals page</a> before checkout, then confirm the final cart total on the merchant site.</p>';

        $parts[] = $this->sectionStoreAtAGlanceTable($name, $category, $products, $offers, $merchantFaqs, $metaDescription, $merchant);
        $parts[] = $this->sectionBrandStory($name, $category, $metaDescription, $products, $merchant);
        $parts[] = $this->sectionAdvantages($name, $category, $offers, $metaDescription, $products);

        if (count($products) >= 2) {
            $parts[] = $this->sectionProductComparisonTable($name, $products, $category);
            $parts[] = $this->sectionProductDeepDives($name, $products, $storeUrl);
            $parts[] = $this->sectionWhichProductToChoose($name, $products, $category);
        } elseif (count($products) === 1) {
            $parts[] = $this->sectionProductSpecTable($products[0], $name);
            $parts[] = $this->sectionProductDeepDives($name, $products, $storeUrl);
            $parts[] = $this->sectionSingleProductProsCons($products[0]['name'], $products[0]);
        } else {
            $parts[] = $this->sectionBestSellers($name, $offers, $storeUrl);
        }

        $parts[] = $this->sectionCurrentOffers($name, $offers, $storeUrl, $store->affiliate_url);
        $parts[] = $this->sectionHowToSave($name, $storeUrl, $store->affiliate_url);
        $parts[] = $this->sectionFaq($name, $merchantFaqs, $storeUrl, $products, $offers, $merchant);
        $parts[] = $this->sectionCheckoutChecklist($store);

        $parts[] = '<h2>Bottom line</h2>';
        if (count($products) > 0) {
            $lead = e((string) ($products[0]['name'] ?? $name));
            $parts[] = '<p>Start with <strong>'.$lead.'</strong>'
                .(count($products) > 1 ? ' (or compare it with the other products above)' : '')
                .', apply a listed deal from our <a href="'.e($storeUrl).'">'.e($name).' coupon page</a>, and verify shipping plus exclusions at checkout.</p>';
        } else {
            $parts[] = '<p>Use the offers on our <a href="'.e($storeUrl).'">'.e($name).' coupon page</a>, confirm eligibility on the merchant site, and compare the final cart total before you pay.</p>';
        }

        return implode("\n\n", array_filter($parts));
    }

    /**
     * @param  array<int, array{name?: string, description?: ?string, price?: ?string, image?: ?string, url?: ?string, features?: list<string>}>  $products
     * @param  array<string, mixed>  $merchant
     */
    private function sectionBrandStory(
        string $name,
        string $category,
        ?string $metaDescription,
        array $products = [],
        array $merchant = [],
    ): string {
        $parts = [];
        $parts[] = '<h2>What '.e($name).' sells</h2>';

        $domain = filled($merchant['domain'] ?? null) ? (string) $merchant['domain'] : null;
        $opening = 'Public product pages';
        if ($domain) {
            $opening = 'Listings on '.e($domain);
        }
        $opening .= ' place '.e($name).' in the '.e(strtolower($category)).' category';

        $productCount = collect($products)->pluck('name')->filter()->count();
        if ($productCount > 0) {
            $opening .= ', with '.$productCount.' featured listing'.($productCount === 1 ? '' : 's').' broken down below';
        } else {
            $opening .= '. Public catalog product pages were limited or unavailable during research, so this guide focuses on brand positioning and current offers';
        }
        $opening .= '.';
        $parts[] = '<p>'.$opening.'</p>';

        if ($metaDescription) {
            $parts[] = '<p>The merchant describes itself this way: <em>'.e($metaDescription).'</em></p>';
        }

        foreach (array_slice($products, 0, 2) as $product) {
            if (! filled($product['description'] ?? null)) {
                continue;
            }

            $parts[] = '<p><strong>'.e((string) $product['name']).':</strong> '
                .e(Str::limit(strip_tags((string) $product['description']), 280)).'</p>';
        }

        $parts[] = '<p>For deal hunters on '.e(config('site.name')).', the useful angle is simple: match a specific product page to a listed promo, then confirm the discount still applies in cart.</p>';

        return implode("\n", $parts);
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     * @param  array<int, array{name?: string, description?: ?string, price?: ?string, image?: ?string, url?: ?string, features?: list<string>}>  $products
     */
    private function sectionAdvantages(
        string $name,
        string $category,
        array $offers,
        ?string $metaDescription,
        array $products = [],
    ): string {
        $advantages = $this->buildAdvantages($name, $category, $offers, $metaDescription, $products);
        $parts = [];
        $parts[] = '<h2>What stands out when shopping '.e($name).'</h2>';
        $parts[] = '<p>These points come from the products and offers we have on file — not generic brand praise:</p>';

        foreach ($advantages as $index => $advantage) {
            $parts[] = '<h3>'.($index + 1).'. '.e($advantage['title']).'</h3>';
            $parts[] = '<p>'.e($advantage['body']).'</p>';
        }

        return implode("\n", $parts);
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     * @param  array<int, array{name?: string, description?: ?string, price?: ?string, image?: ?string, url?: ?string, features?: list<string>}>  $products
     * @return array<int, array{title: string, body: string}>
     */
    private function buildAdvantages(
        string $name,
        string $category,
        array $offers,
        ?string $metaDescription,
        array $products = [],
    ): array {
        $items = [];

        foreach (array_slice($products, 0, 2) as $product) {
            $productName = (string) ($product['name'] ?? '');
            if ($productName === '') {
                continue;
            }

            $body = filled($product['description'] ?? null)
                ? Str::limit(strip_tags((string) $product['description']), 220)
                : "{$productName} appears among the merchant's highlighted listings in this guide.";

            if (filled($product['price'] ?? null)) {
                $body .= ' Listed price: '.$product['price'].'.';
            }

            $features = is_array($product['features'] ?? null) ? array_slice($product['features'], 0, 2) : [];
            if ($features !== [] && filled($product['description'] ?? null)) {
                // Prefer description; only append unique features that add new detail.
                $descLower = Str::lower(strip_tags((string) $product['description']));
                $extra = [];
                foreach ($features as $feature) {
                    $feature = (string) $feature;
                    if (! str_contains($descLower, Str::lower(Str::limit($feature, 40, '')))) {
                        $extra[] = $feature;
                    }
                }
                if ($extra !== []) {
                    $body .= ' Notable details from the product page: '.implode('; ', $extra).'.';
                }
            } elseif ($features !== []) {
                $body .= ' Notable details from the product page: '.implode('; ', $features).'.';
            }

            $items[] = ['title' => $productName, 'body' => $body];
        }

        foreach (array_slice($offers, 0, 2) as $offer) {
            $title = (string) ($offer['title'] ?? 'Current offer');
            $body = filled($offer['description'] ?? null)
                ? Str::limit(strip_tags((string) $offer['description']), 220)
                : "Featured on our {$name} deals list for shoppers comparing live savings.";

            if (($offer['type'] ?? '') === 'coupon' && filled($offer['code'] ?? null)) {
                $body .= ' Promo code: '.$offer['code'].'.';
            } else {
                $body .= ' Type: automatic discount (confirm at checkout).';
            }

            $items[] = ['title' => $title, 'body' => $body];
        }

        if ($metaDescription) {
            $items[] = [
                'title' => 'Merchant-stated positioning',
                'body' => Str::limit($metaDescription, 260),
            ];
        }

        if ($items === []) {
            $items[] = [
                'title' => 'Official store checkout',
                'body' => "Buying through the {$name} storefront lets you apply listed promo codes and confirm shipping on the merchant's own cart page.",
            ];
            $items[] = [
                'title' => 'Deal tracking on '.config('site.name'),
                'body' => "We keep publicly listed {$name} offers in one place so you can copy a code or confirm an automatic discount before visiting the merchant.",
            ];
        }

        return array_slice($items, 0, 5);
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     */
    private function sectionBestSellers(string $name, array $offers, string $storeUrl): string
    {
        $parts = [];
        $parts[] = '<h2>Popular Products &amp; Best-Selling Offers</h2>';
        $parts[] = '<p>While exact bestseller rankings change week to week, the offers below reflect what '
            . e(config('site.name')) . ' shoppers are clicking most often right now. Treat them as a snapshot of high-interest '
            . 'deals — product names, bundles, and promo types the brand is actively pushing.</p>';

        foreach ($offers as $index => $offer) {
            $rank = $index + 1;
            $parts[] = '<h3>#' . $rank . ': ' . e($offer['title']) . '</h3>';

            if (filled($offer['description'])) {
                $parts[] = '<p>' . nl2br(e($offer['description'])) . '</p>';
            }
        }

        $parts[] = '<p>See every active listing on our <a href="' . e($storeUrl) . '">' . e($name) . ' store page</a> for the most up-to-date mix of coupon codes and automatic discounts.</p>';

        return implode("\n", $parts);
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     */
    private function sectionCurrentOffers(string $name, array $offers, string $storeUrl, ?string $affiliateUrl = null): string
    {
        return DynamicCouponContent::placeholderMarkup($name);
    }

    private function sectionHowToSave(string $name, string $storeUrl, ?string $affiliateUrl = null): string
    {
        $parts = [];
        $parts[] = '<h2>How to Maximize Savings at ' . e($name) . '</h2>';
        $parts[] = '<ol>';
        $parts[] = '<li>Start on our <a href="' . e($storeUrl) . '">' . e($name) . ' deals page</a> to see coupon vs automatic offers.</li>';

        if (filled($affiliateUrl)) {
            $parts[] = '<li>Use our <a href="' . e($affiliateUrl) . '" rel="nofollow sponsored">affiliate shop link</a> so your order is tracked through ' . e(config('site.name')) . '.</li>';
        }

        $parts[] = '<li>Copy the promo code before you open the merchant site if a code is required.</li>';
        $parts[] = '<li>Check minimum spend rules, excluded categories, and expiration dates on the merchant checkout page.</li>';
        $parts[] = '<li>Compare the final total with and without the code — some sitewide sales cannot stack with additional coupons.</li>';
        $parts[] = '<li>Subscribe to the brand newsletter if you plan repeat purchases; many stores send exclusive codes to subscribers.</li>';
        $parts[] = '</ol>';

        return implode("\n", $parts);
    }

    /**
     * @param  array<int, array{question: string, answer: string}>  $merchantFaqs
     * @param  array<int, array{name?: string, description?: ?string, price?: ?string, image?: ?string, url?: ?string, features?: list<string>}>  $products
     * @param  array<int, array{code?: ?string, title?: string, description?: ?string, type?: string}>  $offers
     * @param  array<string, mixed>  $merchant
     */
    private function sectionFaq(
        string $name,
        array $merchantFaqs,
        string $storeUrl,
        array $products = [],
        array $offers = [],
        array $merchant = [],
    ): string {
        $parts = [];
        $parts[] = '<h2>Frequently Asked Questions</h2>';

        if ($merchantFaqs !== []) {
            $parts[] = '<p>These answers start with the merchant\'s own public FAQ content, then cover common search questions shoppers ask about '.e($name).':</p>';

            foreach ($merchantFaqs as $faq) {
                $parts[] = '<h3>' . e($faq['question']) . '</h3>';
                $parts[] = '<p>' . e($faq['answer']) . '</p>';
            }
        } else {
            $parts[] = '<p>Common search questions U.S. shoppers ask before buying from '.e($name).':</p>';
        }

        foreach ($this->searchIntentFaqs($name, $storeUrl, $products, $offers, $merchant, $merchantFaqs) as $faq) {
            $parts[] = '<h3>' . e($faq[0]) . '</h3>';
            $parts[] = '<p>' . $faq[1] . '</p>';
        }

        return implode("\n", $parts);
    }

    /**
     * High-intent FAQ pairs. Answers stay conservative when the scrape lacks a hard fact.
     *
     * @param  array<int, array{name?: string, description?: ?string, price?: ?string, image?: ?string, url?: ?string, features?: list<string>}>  $products
     * @param  array<int, array{code?: ?string, title?: string, description?: ?string, type?: string}>  $offers
     * @param  array<string, mixed>  $merchant
     * @param  array<int, array{question?: string, answer?: string}>  $merchantFaqs
     * @return list<array{0: string, 1: string}>
     */
    private function searchIntentFaqs(
        string $name,
        string $storeUrl,
        array $products,
        array $offers,
        array $merchant,
        array $merchantFaqs = [],
    ): array {
        $haystack = Str::lower(collect($products)->map(function (array $product) {
            return implode(' ', [
                (string) ($product['name'] ?? ''),
                (string) ($product['description'] ?? ''),
                implode(' ', is_array($product['features'] ?? null) ? $product['features'] : []),
            ]);
        })->implode(' ').' '.Str::lower((string) ($merchant['meta_description'] ?? '')));

        $dealsLink = '<a href="'.e($storeUrl).'">'.e($name).' deals page</a>';
        $faqs = [];

        // Material / leather
        if (preg_match('/\bleather\b/i', $haystack)) {
            if (preg_match('/\bgenuine\s+leather\b/i', $haystack)) {
                $leatherAnswer = e('Some '.$name.' product listings mention genuine leather. Check the specific product page for material wording before you buy, because materials can vary by SKU.');
            } elseif (preg_match('/\b(faux|vegan|pu)\s+leather\b/i', $haystack)) {
                $leatherAnswer = e('Listings we reviewed point to faux/vegan or PU leather rather than a blanket genuine-leather claim. Confirm the material line on the product page you plan to order.');
            } else {
                $leatherAnswer = e($name.' product copy references leather materials, but not every item is labeled the same way. Look for "genuine leather" (or the exact material) on the individual listing.');
            }
            $faqs[] = ['Is '.$name.' genuine leather?', $leatherAnswer];
        }

        // Country of origin
        $faqs[] = [
            'Where is '.$name.' made?',
            $this->originFaqAnswer($name, $merchantFaqs, $products, $merchant['meta_description'] ?? null),
        ];

        // International shipping
        $shipAnswer = $this->faqAnswerMatching($merchantFaqs, '/ship|deliver|worldwide|international|postage|shipping/i', [
            (string) ($merchant['meta_description'] ?? ''),
        ]);
        $faqs[] = [
            'Does '.$name.' ship internationally?',
            $shipAnswer
                ? e($shipAnswer)
                : e('International availability depends on the merchant checkout options and your address. Confirm shipping countries, rates, and any duties on the '.$name.' checkout page before paying.'),
        ];

        // Worth buying — avoid stuffing the same product-name list again.
        $offerTitles = collect($offers)->pluck('title')->filter()->take(2)->values()->all();
        $leadProduct = collect($products)->pluck('name')->filter()->first();
        $worth = e($name).' can be worth buying if a featured listing matches what you need';
        if (is_string($leadProduct) && $leadProduct !== '') {
            $worth .= ' (start with '.e($leadProduct).' if that is your use case)';
        }
        $worth .= ' and the final cart total looks fair after shipping.';
        if ($offerTitles !== []) {
            $worth .= ' Pair the purchase with a current offer such as '.e(implode(' / ', $offerTitles)).' from our '.$dealsLink.'.';
        } else {
            $worth .= ' Compare the live price on our '.$dealsLink.' before checkout.';
        }
        $faqs[] = ['Is '.$name.' worth buying?', $worth];

        // Student discount
        $studentHaystack = $haystack.' '.Str::lower(collect($offers)->map(fn (array $o) => ($o['title'] ?? '').' '.($o['description'] ?? ''))->implode(' '));
        $studentFaq = $this->faqAnswerMatching($merchantFaqs, '/student|edu|campus|teacher|military/i');
        if ($studentFaq || preg_match('/\bstudent\b|\bedu\b|teacher|military/i', $studentHaystack)) {
            $faqs[] = [
                'Does '.$name.' offer student discounts?',
                $studentFaq
                    ? e($studentFaq)
                    : e('We found student/education-related discount language in the merchant or offer copy. Confirm eligibility and verification steps on the merchant site.'),
            ];
        } else {
            $faqs[] = [
                'Does '.$name.' offer student discounts?',
                e('We did not find a dedicated student discount among the '.$name.' offers tracked here. Check the merchant site, student-discount portals, or newsletter for any campus/education promotions.'),
            ];
        }

        // Coupon frequency
        $offerCount = count($offers);
        $couponFreq = $offerCount > 0
            ? 'We currently track '.$offerCount.' '.e($name).' offer'.($offerCount === 1 ? '' : 's').' on '.e((string) config('site.name')).'. Brands often refresh codes around seasonal sales, product launches, and newsletter campaigns — check our '.$dealsLink.' before you buy.'
            : e($name).' promo codes appear most often around seasonal sales and newsletter drops. Bookmark our '.$dealsLink.' to catch newly listed codes.';
        $faqs[] = ['How often does '.$name.' release coupon codes?', $couponFreq];

        // Practical coupon FAQs shoppers still need
        $faqs[] = [
            'Does '.$name.' require a promo code for every deal?',
            'No. Some promotions apply automatically at checkout, while others need a code. Our listings separate coupon codes from no-code discounts on the '.$dealsLink.'.',
        ];
        $faqs[] = [
            'What if my '.$name.' promo code does not work?',
            'Confirm expiration dates, product exclusions, and minimum order values on the merchant site. Retailers can change or end offers without notice — then try another active listing from our '.$dealsLink.'.',
        ];
        $faqs[] = [
            'Is it safe to shop through '.config('site.name').'?',
            e('We link to official merchant checkout flows. Always verify you are on the brand\'s legitimate domain before entering payment details.'),
        ];

        return $faqs;
    }

    /**
     * Append search-intent FAQs when the article FAQ block is thin or missing key queries.
     *
     * @param  array<string, mixed>  $merchant
     * @param  array<int, array{code?: ?string, title?: string, description?: ?string, type?: string}>  $offers
     */
    private function ensureSearchIntentFaqs(
        string $content,
        string $storeName,
        array $merchant = [],
        array $offers = [],
        ?Store $store = null,
    ): string {
        $content = trim($content);
        if ($content === '') {
            return $content;
        }

        $requiredSnippets = [
            'worth buying',
            'ship internationally',
            'coupon codes',
            'student discount',
        ];

        $missing = 0;
        foreach ($requiredSnippets as $snippet) {
            if (! str_contains(Str::lower($content), $snippet)) {
                $missing++;
            }
        }

        // Already has a strong FAQ set covering search intents.
        if ($missing <= 1 && preg_match('/<h2[^>]*>\s*Frequently Asked Questions\s*<\/h2>/i', $content)) {
            return $content;
        }

        $products = is_array($merchant['products'] ?? null) ? $merchant['products'] : [];
        if (! empty($merchant['product_focus'])) {
            $products = array_slice($products, 0, 1);
        }
        $faqs = is_array($merchant['faqs'] ?? null) ? $merchant['faqs'] : [];
        $storeUrl = $store
            ? route('stores.show', $store->slug)
            : url('/stores/'.Str::slug($storeName));

        $block = $this->sectionFaq($storeName, $faqs, $storeUrl, $products, $offers, $merchant);

        // Drop merchant-only intro duplication when appending.
        if (preg_match('/<h2[^>]*>\s*Frequently Asked Questions\s*<\/h2>/i', $content)) {
            // Replace existing FAQ section with the stronger block.
            $replaced = preg_replace(
                '/<h2[^>]*>\s*Frequently Asked Questions\s*<\/h2>.*?(?=<h2\b|$)/is',
                $block."\n\n",
                $content,
                1
            );

            return trim($replaced ?? ($content."\n\n".$block));
        }

        return trim($content."\n\n".$block);
    }

    /**
     * @param  array<string, mixed>  $merchant
     */
    /**
     * EEAT trust block used by large affiliate publishers near the top of guides.
     *
     * @param  array<int, array{name?: string, description?: ?string, price?: ?string, image?: ?string, url?: ?string}>  $products
     * @param  array<int, array{code?: ?string, title?: string, description?: ?string, type?: string}>  $offers
     */
    private function sectionWhyTrustUs(string $storeName, array $products = [], array $offers = []): string
    {
        $site = (string) config('site.name');
        $productCount = collect($products)
            ->pluck('name')
            ->filter(fn ($name) => filled($name))
            ->count();
        $offerCount = count($offers);

        $researchLine = $productCount > 0
            ? 'We researched '.e($storeName).' products'
                .($productCount === 1 ? ' (the featured listing in this guide)' : ' ('.$productCount.' featured listings in this guide)')
                .', customer reviews, pricing, and verified coupon availability before publishing this guide.'
            : 'We researched '.e($storeName).' brand details, public product information, pricing cues, and verified coupon availability before publishing this guide.';

        $parts = [];
        $parts[] = '<h2>Why Trust Us</h2>';
        $parts[] = '<p>'.$researchLine.'</p>';
        $parts[] = '<p>'.e($site).' publishes independent shopping guides for U.S. readers. Our editorial process focuses on facts shoppers can check themselves — not unverified ratings or invented testimonials.</p>';
        $parts[] = '<ul>';
        $parts[] = '<li><strong>Product research:</strong> Details come from the merchant\'s public product pages and publicly available listing information'
            .($productCount > 0 ? ' for the products compared below.' : '.');
        $parts[] = '</li>';
        $parts[] = '<li><strong>Coupon verification:</strong> Featured offers on this page are reviewed for format and availability when we publish'
            .($offerCount > 0 ? ' ('.$offerCount.' offer'.($offerCount === 1 ? '' : 's').' featured)' : '')
            .'. Always confirm terms on the merchant checkout page before paying.</li>';
        $parts[] = '<li><strong>Transparent affiliate links:</strong> Some links may earn '.e($site).' a commission at no extra cost to you. That support helps us keep deal pages updated.</li>';
        $parts[] = '<li><strong>Practical buying advice:</strong> We highlight shipping, return, and promo-stacking considerations that affect the final cart total — not just sticker price.</li>';
        $parts[] = '</ul>';
        $parts[] = '<p>If a code stops working or a product page changes, we update the guide when new information is available. Treat this as a research starting point, then verify the live price and promotion at checkout.</p>';

        return implode("\n", $parts);
    }

    /**
     * Inject Why Trust Us near the top when Gemini/templates omit it.
     *
     * @param  array<string, mixed>  $merchant
     * @param  array<int, array{code?: ?string, title?: string, description?: ?string, type?: string}>  $offers
     */
    private function ensureWhyTrustUs(string $content, string $storeName, array $merchant = [], array $offers = []): string
    {
        $content = trim($content);

        if ($content === '' || preg_match('/<h2[^>]*>\s*Why\s+Trust\s+Us\s*<\/h2>/i', $content)) {
            return $content;
        }

        $products = is_array($merchant['products'] ?? null) ? $merchant['products'] : [];
        if (! empty($merchant['product_focus'])) {
            $products = array_slice($products, 0, 1);
        }

        $section = $this->sectionWhyTrustUs($storeName, $products, $offers);

        if (preg_match('/((?:<figure[^>]*>\s*)?(?:<p>\s*)?<img[^>]+alt=["\'][^"\']*(?:store banner|brand logo)[^"\']*["\'][^>]*>\s*(?:<\/p>\s*)?(?:<figcaption>.*?<\/figcaption>\s*)?(?:<\/figure>\s*)*)/is', $content, $match, PREG_OFFSET_CAPTURE)) {
            $pos = $match[0][1] + strlen($match[0][0]);

            return trim(substr($content, 0, $pos)."\n\n".$section."\n\n".ltrim(substr($content, $pos)));
        }

        if (preg_match('/<\/p>/i', $content, $match, PREG_OFFSET_CAPTURE)) {
            $pos = $match[0][1] + strlen($match[0][0]);

            return trim(substr($content, 0, $pos)."\n\n".$section."\n\n".ltrim(substr($content, $pos)));
        }

        return $section."\n\n".$content;
    }

    /**
     * @param  array{banner?: ?string}  $merchant
     */
    private function storeBannerHtml(array $merchant, string $storeName): string
    {
        $banner = $merchant['banner'] ?? null;

        if (! filled($banner)) {
            return '';
        }

        return '<figure class="article-media article-media--banner">'
            .'<img src="'.e((string) $banner).'" alt="'.e($storeName).' official store banner" loading="lazy">'
            .'<figcaption>'.e($storeName).' store banner</figcaption>'
            .'</figure>';
    }

    /**
     * @param  array{logo?: ?string}  $merchant
     */
    private function storeLogoHtml(array $merchant, string $storeName): string
    {
        $logo = $merchant['logo'] ?? null;

        if (! filled($logo)) {
            return '';
        }

        return '<figure class="article-media article-media--logo">'
            .'<img src="'.e((string) $logo).'" alt="'.e($storeName).' brand logo" width="160" height="160" loading="lazy">'
            .'<figcaption>'.e($storeName).' logo</figcaption>'
            .'</figure>';
    }

    /**
     * @param  array{name?: string, image?: ?string}  $product
     */
    private function productImageHtml(array $product, ?string $storeName = null): string
    {
        $image = $product['image'] ?? null;

        if (! filled($image)) {
            return '';
        }

        $name = filled($product['name'] ?? null) ? (string) $product['name'] : 'Product';
        $alt = $storeName
            ? "{$name} product photo from {$storeName}"
            : "{$name} product photo";

        return '<figure class="article-media article-media--product">'
            .'<img src="'.e((string) $image).'" alt="'.e($alt).'" loading="lazy">'
            .'<figcaption>'.e($name).'</figcaption>'
            .'</figure>';
    }

    /**
     * Ensure logo/banner sit near the top and product photos appear under product headings.
     *
     * @param  array<string, mixed>  $merchant
     */
    private function ensureArticleImages(string $content, array $merchant, string $storeName): string
    {
        $content = trim($content);
        $banner = filled($merchant['banner'] ?? null) ? (string) $merchant['banner'] : null;
        $logo = filled($merchant['logo'] ?? null) ? (string) $merchant['logo'] : null;

        // Normalize: one banner + one logo at the top.
        $content = preg_replace(
            '/(?:<figure[^>]*>\s*)?(?:<p>\s*)?<img[^>]+alt=["\'][^"\']*(?:store banner|brand logo)[^"\']*["\'][^>]*>\s*(?:<\/p>\s*)?(?:<figcaption>.*?<\/figcaption>\s*)?(?:<\/figure>\s*)?/is',
            '',
            $content
        ) ?? $content;

        if ($banner) {
            $bannerNeedle = preg_quote($banner, '/');
            $bannerNeedleAmp = preg_quote(e($banner), '/');
            $content = preg_replace(
                '/(?:<figure[^>]*>\s*)?(?:<p>\s*)?<img[^>]+src=["\'](?:'.$bannerNeedle.'|'.$bannerNeedleAmp.')["\'][^>]*>\s*(?:<\/p>\s*)?(?:<figcaption>.*?<\/figcaption>\s*)?(?:<\/figure>\s*)?/is',
                '',
                $content
            ) ?? $content;
        }

        $content = trim($content);
        $lead = trim($this->storeBannerHtml($merchant, $storeName)."\n".$this->storeLogoHtml($merchant, $storeName));
        if ($lead !== '') {
            $content = $lead."\n\n".$content;
        }

        $products = is_array($merchant['products'] ?? null) ? $merchant['products'] : [];

        foreach ($products as $product) {
            if (! is_array($product)) {
                continue;
            }

            $image = filled($product['image'] ?? null) ? (string) $product['image'] : null;
            $name = filled($product['name'] ?? null) ? (string) $product['name'] : null;

            if (! $image || ! $name) {
                continue;
            }

            $figure = $this->productImageHtml($product, $storeName);
            $headingMatch = $this->findProductHeadingMatch($content, $name);

            if ($headingMatch === null) {
                if (! str_contains($content, $image) && ! str_contains($content, e($image))) {
                    $content .= "\n\n".$figure;
                }

                continue;
            }

            // Already has an image right under this heading.
            if (preg_match('/'.preg_quote($headingMatch, '/').'\s*(?:<(?:figure|p)[^>]*>\s*)?<img\b/i', $content)) {
                continue;
            }

            $content = preg_replace(
                '/(?:<figure[^>]*>\s*)?(?:<p>\s*)?<img[^>]+src=["\'](?:'.preg_quote($image, '/').'|'.preg_quote(e($image), '/').')["\'][^>]*>\s*(?:<\/p>\s*)?(?:<figcaption>.*?<\/figcaption>\s*)?(?:<\/figure>\s*)?/is',
                '',
                $content
            ) ?? $content;

            $content = preg_replace(
                '/('.preg_quote($headingMatch, '/').')/i',
                '$1'."\n".$figure,
                $content,
                1
            ) ?? $content;
        }

        return trim($content);
    }

    /**
     * Find the full <h3>...</h3> markup for a product, allowing shortened Gemini titles.
     */
    private function findProductHeadingMatch(string $content, string $productName): ?string
    {
        if (! preg_match_all('/<h3[^>]*>.*?<\/h3>/is', $content, $matches)) {
            return null;
        }

        $normalizedProduct = Str::lower(HtmlCleaner::textFromHtml($productName));
        $productTokens = array_values(array_filter(
            preg_split('/[^a-z0-9]+/i', $normalizedProduct) ?: [],
            fn (string $t) => strlen($t) >= 3
        ));

        $best = null;
        $bestScore = 0;

        foreach ($matches[0] as $headingHtml) {
            $headingText = Str::lower(HtmlCleaner::textFromHtml($headingHtml));

            if ($headingText === '' || strlen($headingText) < 3) {
                continue;
            }

            if (str_contains($headingText, $normalizedProduct) || str_contains($normalizedProduct, $headingText)) {
                return $headingHtml;
            }

            $headingTokens = array_values(array_filter(
                preg_split('/[^a-z0-9]+/i', $headingText) ?: [],
                fn (string $t) => strlen($t) >= 3
            ));

            if ($productTokens === [] || $headingTokens === []) {
                continue;
            }

            $overlap = count(array_intersect($productTokens, $headingTokens));
            $score = $overlap / max(count($headingTokens), 1);

            if ($overlap >= 2 && $score > $bestScore) {
                $bestScore = $score;
                $best = $headingHtml;
            }
        }

        return $bestScore >= 0.4 ? $best : null;
    }
}
