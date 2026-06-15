<?php

namespace App\Support;

use App\Models\Store;
use Illuminate\Support\Str;

final class AffiliateImportContentBuilder
{
    public function storeDescription(string $storeName, ?string $metaDescription, ?string $categoryName): string
    {
        $categoryLine = $categoryName
            ? "<p>{$storeName} is listed under our <strong>{$categoryName}</strong> deals collection on " . config('site.name') . '.</p>'
            : '';

        $intro = $metaDescription
            ? '<p>' . e($metaDescription) . '</p>'
            : "<p>{$storeName} is a popular U.S. online retailer featured on " . config('site.name') . '. '
                . 'We track publicly available coupon codes and promotional deals so shoppers can save at checkout.</p>';

        return $intro . $categoryLine
            . '<p>Click through from our store page to shop with your affiliate tracking link. '
            . 'Offer terms, exclusions, and expiration dates are set by the merchant and may change without notice.</p>';
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     * @param  array<string, mixed>  $merchant
     * @return array{title: string, excerpt: string, meta_title: string, meta_description: string, content: string}
     */
    public function blogPost(Store $store, array $offers, array $merchant = []): array
    {
        $monthYear = now()->format('F Y');
        $category = $store->category?->name ?? ($merchant['category_name'] ?? 'online retail');
        $metaDescription = $merchant['meta_description'] ?? null;
        $faqs = $merchant['faqs'] ?? [];
        $offerCount = count($offers);

        $title = "{$store->name} Review: Products, Pros, Coupons & FAQ ({$monthYear})";

        $excerpt = "In-depth {$store->name} guide for U.S. shoppers: brand overview, five key advantages, "
            . "best current deals, category comparisons, shopper insights, and {$offerCount} featured offers on "
            . config('site.name') . '.';

        $metaTitle = Str::limit("{$store->name} Review & Coupons {$monthYear} | " . config('site.name'), 70, '');
        $metaDescriptionSeo = Str::limit(
            "Read our {$store->name} review with pros, product highlights, comparisons, FAQs, and {$offerCount} verified coupon codes for {$monthYear}.",
            320,
            ''
        );

        $content = $this->buildLongFormContent(
            $store,
            $offers,
            $category,
            $metaDescription,
            is_array($faqs) ? $faqs : [],
            $monthYear
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
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     * @param  array<int, array{question: string, answer: string}>  $merchantFaqs
     */
    private function buildLongFormContent(
        Store $store,
        array $offers,
        string $category,
        ?string $metaDescription,
        array $merchantFaqs,
        string $monthYear
    ): string {
        $name = $store->name;
        $storeUrl = route('stores.show', $store->slug);
        $parts = [];

        $parts[] = '<p>' . e($name) . ' has become a recognizable name among U.S. online shoppers browsing the '
            . e($category) . ' space. Whether you are comparing features, hunting for a promo code, or deciding if this '
            . 'retailer fits your budget, this guide breaks down what matters: product strengths, how the brand compares '
            . 'with similar stores, what current deals look like on ' . e(config('site.name')) . ', and answers to common '
            . 'questions shoppers ask before checkout.</p>';

        if ($metaDescription) {
            $parts[] = '<p>' . e($metaDescription) . '</p>';
        }

        $parts[] = '<p>We keep this article focused on practical buying decisions for American customers — shipping expectations, '
            . 'value for money, offer types (coupon vs automatic discount), and how to stack savings when the merchant allows it. '
            . 'Browse the latest featured offers on our <a href="' . e($storeUrl) . '">' . e($name) . ' deals page</a> before you shop.</p>';

        $parts[] = $this->sectionBrandStory($name, $category, $metaDescription);
        $parts[] = $this->sectionAdvantages($name, $category, $offers, $metaDescription);
        $parts[] = $this->sectionBestSellers($name, $offers, $monthYear, $storeUrl);
        $parts[] = $this->sectionComparison($name, $category);
        $parts[] = $this->sectionShopperFeedback($name, $offers, $metaDescription);
        $parts[] = $this->sectionCurrentOffers($name, $offers, $storeUrl, $monthYear);
        $parts[] = $this->sectionHowToSave($name, $storeUrl);
        $parts[] = $this->sectionFaq($name, $merchantFaqs, $storeUrl);

        $parts[] = '<h2>Final Thoughts</h2>';
        $parts[] = '<p>' . e($name) . ' remains a worthwhile option for shoppers who prioritize '
            . $this->categoryValueProp($category) . '. Pair the brand\'s strengths with a current promo from our '
            . '<a href="' . e($storeUrl) . '">' . e($name) . ' coupon listing</a>, confirm terms on the merchant site, '
            . 'and you can often improve the total value of your order. Bookmark this page — we update featured deals on '
            . e(config('site.name')) . ' as new codes and discounts are published.</p>';

        return implode("\n\n", array_filter($parts));
    }

    private function sectionBrandStory(string $name, string $category, ?string $metaDescription): string
    {
        $parts = [];
        $parts[] = '<h2>Brand Background &amp; Growth</h2>';
        $parts[] = '<p>Like many modern ' . e(strtolower($category)) . ' brands, ' . e($name) . ' built its audience by selling directly online, '
            . 'refining its product line based on repeat purchases, reviews, and seasonal demand. Shoppers typically discover the brand '
            . 'through search, social recommendations, or deal communities when a new collection or accessory line launches.</p>';

        if ($metaDescription) {
            $parts[] = '<p>According to the merchant\'s public positioning, ' . lcfirst(e($metaDescription)) . ' '
                . 'That focus helps explain why the catalog resonates with buyers who want a specialized option rather than a generic marketplace listing.</p>';
        } else {
            $parts[] = '<p>Over time, ' . e($name) . ' expanded its catalog within ' . e(strtolower($category)) . ', adding variants, bundles, '
                . 'and limited promotions tied to product launches. For U.S. customers, the direct-to-consumer model often means clearer product pages, '
                . 'streamlined checkout, and promotional events tied to the brand\'s own site rather than a third-party seller.</p>';
        }

        $parts[] = '<p>From a savings perspective, brand-owned stores frequently publish newsletter codes, cart-wide discounts, and free-shipping thresholds. '
            . 'That makes it useful to monitor official promotions — and third-party deal hubs like ' . e(config('site.name')) . ' — before placing a larger order.</p>';

        return implode("\n", $parts);
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     */
    private function sectionAdvantages(string $name, string $category, array $offers, ?string $metaDescription): string
    {
        $advantages = $this->buildAdvantages($name, $category, $offers, $metaDescription);
        $parts = [];
        $parts[] = '<h2>5 Key Advantages of Shopping at ' . e($name) . '</h2>';
        $parts[] = '<p>Every retailer has trade-offs, but these are the standout reasons U.S. shoppers often choose '
            . e($name) . ' over generic alternatives in the ' . e(strtolower($category)) . ' category:</p>';

        foreach ($advantages as $index => $advantage) {
            $parts[] = '<h3>' . ($index + 1) . '. ' . e($advantage['title']) . '</h3>';
            $parts[] = '<p>' . e($advantage['body']) . '</p>';
        }

        return implode("\n", $parts);
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     * @return array<int, array{title: string, body: string}>
     */
    private function buildAdvantages(string $name, string $category, array $offers, ?string $metaDescription): array
    {
        $offerHint = collect($offers)
            ->pluck('description')
            ->filter()
            ->first();

        $templates = match ($category) {
            'Electronics' => [
                ['Specialized product focus', "{$name} concentrates on tech-forward products with specs and use cases spelled out on each product page, which helps buyers compare models quickly."],
                ['Direct brand support', 'Purchasing from the official store often simplifies warranty questions, firmware updates, and accessory compatibility compared with unknown marketplace sellers.'],
                ['Bundle-friendly promotions', 'Electronics brands frequently run bundle discounts, trade-in credits, or seasonal sale events that reward higher cart values.'],
                ['Transparent feature lists', 'Detailed spec sheets and comparison tables make it easier to match a device to your workflow, whether you need portability, battery life, or pro-level performance.'],
                ['Online-first convenience', 'U.S. shoppers can order from home, track shipments, and apply digital coupon codes at checkout without visiting a physical location.'],
            ],
            'Fashion' => [
                ['Curated seasonal styles', "{$name} organizes collections around current trends and seasonal drops, making it easier to shop coordinated outfits instead of random single items."],
                ['Size and fit guidance', 'Product pages typically include sizing notes and fabric details, which reduces guesswork when ordering apparel online.'],
                ['Style-focused promotions', 'Fashion retailers often publish limited-time codes for new arrivals, clearance events, and holiday sales.'],
                ['Return-friendly policies', 'Many apparel brands competing online offer structured return windows so customers can exchange sizes or styles with less risk.'],
                ['Brand identity you can trust', 'Shopping the official store helps avoid counterfeit listings and ensures you receive authentic materials and construction.'],
            ],
            'Beauty' => [
                ['Ingredient-forward listings', "{$name} usually highlights active ingredients, skin types, and usage instructions — important for beauty buyers comparing formulas."],
                ['Routine-based merchandising', 'Products are often grouped into regimens (cleanse, treat, moisturize, protect), which simplifies building a full routine from one cart.'],
                ['Sample and gift incentives', 'Beauty brands frequently add gifts-with-purchase or deluxe samples during promotional periods.'],
                ['Shade and variant clarity', 'Online shade charts and undertone guidance help customers select colors that match their preferences before ordering.'],
                ['Authentic product guarantee', 'Buying direct reduces the risk of expired or counterfeit cosmetics common on unauthorized reseller channels.'],
            ],
            'Health' => [
                ['Wellness-oriented catalog', "{$name} focuses on health-related products with usage guidance suited to supplement, fitness, or personal care shoppers."],
                ['Quality transparency', 'Reputable health retailers publish ingredient sources, certifications, and usage limits so buyers can shop with confidence.'],
                ['Subscription savings', 'Many wellness brands offer subscribe-and-save pricing for repeat purchases of staples customers reorder monthly.'],
                ['Educational content', 'Product pages and FAQs often explain how items fit into daily routines, which supports informed purchasing decisions.'],
                ['Promo events for stock-up orders', 'Free shipping thresholds and multi-item discounts reward customers planning larger wellness restocks.'],
            ],
            'Travel' => [
                ['Booking convenience', "{$name} centralizes travel products or services so planners can compare options without switching between multiple tabs."],
                ['Seasonal fare sales', 'Travel brands frequently discount off-peak windows, early-bird bookings, or bundled packages for U.S. travelers.'],
                ['Flexible search filters', 'Online tools help narrow results by dates, destinations, or budget — saving time during high-demand booking seasons.'],
                ['Member-only rates', 'Email subscribers and app users often receive exclusive promo codes not advertised on comparison sites alone.'],
                ['Clear cancellation terms', 'Official booking channels typically display change and refund policies up front, which reduces surprises after purchase.'],
            ],
            'Food & Dining' => [
                ['Menu and category clarity', "{$name} makes it easy to browse meals, groceries, or delivery options with filters for dietary preferences."],
                ['First-order promotions', 'Food and delivery services commonly offer new-customer discounts that significantly reduce trial orders.'],
                ['Reorder convenience', 'Saved favorites and repeat-order flows help busy households restock staples quickly.'],
                ['Local and national availability', 'Shoppers can check service areas and delivery windows before committing, which improves planning for events or weekly meal prep.'],
                ['Stackable limited-time deals', 'Combo offers and free delivery thresholds appear frequently during weekends and holiday peaks.'],
            ],
            default => [
                ['Focused product selection', "{$name} curates a catalog aimed at a specific shopper need instead of overwhelming visitors with unrelated categories."],
                ['Official store reliability', 'Ordering direct helps ensure authentic products, valid warranties, and access to the merchant\'s latest promotions.'],
                ['Regular promotional cycles', 'The brand participates in seasonal sales, newsletter exclusives, and cart-wide discounts throughout the year.'],
                ['Detailed product pages', 'Descriptions, specifications, and usage notes give buyers enough context to choose confidently online.'],
                ['U.S. e-commerce convenience', 'Customers can shop anytime, apply digital codes at checkout, and track orders without visiting a retail location.'],
            ],
        };

        if ($offerHint) {
            $templates[0] = [
                'Featured deal value',
                Str::limit("Current promotions highlight real savings: {$offerHint}", 280),
            ];
        }

        if ($metaDescription && ! $offerHint) {
            $templates[1] = [
                'Clear brand positioning',
                Str::limit("The merchant emphasizes: {$metaDescription}", 280),
            ];
        }

        return array_map(fn (array $item) => ['title' => $item[0], 'body' => $item[1]], $templates);
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     */
    private function sectionBestSellers(string $name, array $offers, string $monthYear, string $storeUrl): string
    {
        $parts = [];
        $parts[] = '<h2>Popular Products &amp; Best-Selling Offers (' . e($monthYear) . ')</h2>';
        $parts[] = '<p>While exact bestseller rankings change week to week, the offers below reflect what '
            . e(config('site.name')) . ' shoppers are clicking most often right now. Treat them as a snapshot of high-interest '
            . 'deals — product names, bundles, and promo types the brand is actively pushing this month.</p>';

        foreach ($offers as $index => $offer) {
            $rank = $index + 1;
            $parts[] = '<h3>#' . $rank . ': ' . e($offer['title']) . '</h3>';

            if (filled($offer['description'])) {
                $parts[] = '<p>' . nl2br(e($offer['description'])) . '</p>';
            } else {
                $parts[] = '<p>This featured offer is popular with shoppers looking for immediate savings at '
                    . e($name) . ' without hunting across multiple coupon sites.</p>';
            }

            if ($offer['type'] === 'coupon' && filled($offer['code'])) {
                $parts[] = '<p><strong>Promo code:</strong> <code>' . e($offer['code']) . '</code></p>';
            }
        }

        $parts[] = '<p>See every active listing on our <a href="' . e($storeUrl) . '">' . e($name) . ' store page</a> for the most up-to-date mix of coupon codes and automatic discounts.</p>';

        return implode("\n", $parts);
    }

    private function sectionComparison(string $name, string $category): string
    {
        $competitors = $this->competitorsFor($category);
        $competitorList = collect($competitors)->map(fn ($c) => e($c))->implode(', ');

        $parts = [];
        $parts[] = '<h2>How ' . e($name) . ' Compares to Similar Brands</h2>';
        $parts[] = '<p>Shoppers rarely choose a store in isolation. In the ' . e(strtolower($category)) . ' segment, '
            . e($name) . ' is often compared with names like ' . $competitorList . '. Here is a practical way to think about the decision:</p>';
        $parts[] = '<ul>';
        $parts[] = '<li><strong>Specialization vs breadth:</strong> ' . e($name) . ' tends to win when you want a focused catalog and brand-specific support. Large generalists may win on sheer selection or overnight shipping breadth.</li>';
        $parts[] = '<li><strong>Promotion style:</strong> Niche brands often publish direct coupon codes and bundle offers on their own site, while big-box competitors rely on membership programs or rotating weekly ads.</li>';
        $parts[] = '<li><strong>Product authenticity:</strong> Buying from ' . e($name) . ' directly reduces third-party seller risk — an important factor for categories where counterfeit goods appear on open marketplaces.</li>';
        $parts[] = '<li><strong>Total landed cost:</strong> Compare not only sticker price but shipping, tax, return fees, and whether a promo code from ' . e(config('site.name')) . ' lowers your final total.</li>';
        $parts[] = '<li><strong>Post-purchase experience:</strong> Warranty handling, customer service channels, and spare parts availability can matter more than saving a few dollars upfront.</li>';
        $parts[] = '</ul>';
        $parts[] = '<p>There is no universal winner — the best choice depends on whether you prioritize lowest price, fastest delivery, brand trust, or a specific product feature set. '
            . 'Use comparisons as a checklist, then apply an active ' . e($name) . ' deal if the brand matches your priorities.</p>';

        return implode("\n", $parts);
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     */
    private function sectionShopperFeedback(string $name, array $offers, ?string $metaDescription): string
    {
        $parts = [];
        $parts[] = '<h2>What Shoppers Say About ' . e($name) . '</h2>';
        $parts[] = '<p>We do not publish unverified star ratings. Instead, this section summarizes themes that commonly appear in merchant copy, product descriptions, and shopper discussions around ' . e($name) . ':</p>';
        $parts[] = '<ul>';

        $themes = [
            'Buyers appreciate clear product pages that explain what is included before checkout.',
            'Promotional events — especially percentage-off codes and bundle pricing — influence repeat purchase timing.',
            'Customer service responsiveness matters when orders include sizing, compatibility, or subscription adjustments.',
            'Shipping speed and tracking transparency are frequent decision factors for U.S. online orders.',
            'Value perception improves when shoppers combine official sales with a verified coupon from a deal hub.',
        ];

        foreach ($themes as $theme) {
            $parts[] = '<li>' . e($theme) . '</li>';
        }

        $parts[] = '</ul>';

        foreach ($offers as $offer) {
            if (filled($offer['description'])) {
                $parts[] = '<p><strong>On “' . e($offer['title']) . '”:</strong> ' . e(Str::limit(strip_tags($offer['description']), 320)) . '</p>';
                break;
            }
        }

        if ($metaDescription) {
            $parts[] = '<p>The brand itself highlights: <em>' . e($metaDescription) . '</em> — a message that aligns with what deal-seeking customers say they want from the shopping experience.</p>';
        }

        return implode("\n", $parts);
    }

    /**
     * @param  array<int, array{code: ?string, title: string, description: ?string, type: string}>  $offers
     */
    private function sectionCurrentOffers(string $name, array $offers, string $storeUrl, string $monthYear): string
    {
        $parts = [];
        $parts[] = '<h2>Current ' . e($name) . ' Coupon Codes &amp; Deals (' . e($monthYear) . ')</h2>';
        $parts[] = '<p>These are the offers we feature today on ' . e(config('site.name')) . '. Copy any code listed, or use automatic discounts as noted:</p>';

        foreach ($offers as $index => $offer) {
            $parts[] = '<h3>Deal ' . ($index + 1) . ': ' . e($offer['title']) . '</h3>';

            if ($offer['type'] === 'coupon' && filled($offer['code'])) {
                $parts[] = '<p><strong>Type:</strong> Coupon code — <code>' . e($offer['code']) . '</code></p>';
            } else {
                $parts[] = '<p><strong>Type:</strong> Automatic discount (no code required)</p>';
            }

            if (filled($offer['description'])) {
                $parts[] = '<p>' . nl2br(e($offer['description'])) . '</p>';
            }
        }

        $parts[] = '<p><a href="' . e($storeUrl) . '">View all ' . e($name) . ' offers →</a></p>';

        return implode("\n", $parts);
    }

    private function sectionHowToSave(string $name, string $storeUrl): string
    {
        $parts = [];
        $parts[] = '<h2>How to Maximize Savings at ' . e($name) . '</h2>';
        $parts[] = '<ol>';
        $parts[] = '<li>Start on our <a href="' . e($storeUrl) . '">' . e($name) . ' deals page</a> to see coupon vs automatic offers.</li>';
        $parts[] = '<li>Copy the promo code before you open the merchant site if a code is required.</li>';
        $parts[] = '<li>Check minimum spend rules, excluded categories, and expiration dates on the merchant checkout page.</li>';
        $parts[] = '<li>Compare the final total with and without the code — some sitewide sales cannot stack with additional coupons.</li>';
        $parts[] = '<li>Subscribe to the brand newsletter if you plan repeat purchases; many stores send exclusive codes to subscribers.</li>';
        $parts[] = '</ol>';

        return implode("\n", $parts);
    }

    /**
     * @param  array<int, array{question: string, answer: string}>  $merchantFaqs
     */
    private function sectionFaq(string $name, array $merchantFaqs, string $storeUrl): string
    {
        $parts = [];
        $parts[] = '<h2>Frequently Asked Questions</h2>';

        if ($merchantFaqs !== []) {
            $parts[] = '<p>These questions are sourced from the merchant\'s public website content (FAQ schema or help pages):</p>';

            foreach ($merchantFaqs as $faq) {
                $parts[] = '<h3>' . e($faq['question']) . '</h3>';
                $parts[] = '<p>' . e($faq['answer']) . '</p>';
            }
        }

        $defaults = [
            ['Does ' . $name . ' require a promo code for every deal?', 'No. Some promotions apply automatically at checkout. Our listings label coupon codes separately from no-code discounts.'],
            ['Where can I find the newest ' . $name . ' coupons?', 'Bookmark our deals page and check back when seasonal sales begin.'],
            ['What if my ' . $name . ' promo code does not work?', 'Confirm expiration dates, product exclusions, and minimum order values on the merchant site. Retailers can change or end offers without notice.'],
            ['Is it safe to shop through ' . config('site.name') . '?', 'We link to official merchant checkout flows. Always verify you are on the brand\'s legitimate domain before entering payment details.'],
        ];

        if ($merchantFaqs === []) {
            $parts[] = '<p>Common questions U.S. shoppers ask before buying:</p>';
        } else {
            $parts[] = '<p>Additional coupon and shopping questions:</p>';
        }

        foreach ($defaults as $faq) {
            $parts[] = '<h3>' . e($faq[0]) . '</h3>';
            $parts[] = '<p>' . e($faq[1]) . ' <a href="' . e($storeUrl) . '">See current deals</a>.</p>';
        }

        return implode("\n", $parts);
    }

    /** @return list<string> */
    private function competitorsFor(string $category): array
    {
        return match ($category) {
            'Electronics' => ['Amazon', 'Best Buy', 'Newegg', 'B&H Photo'],
            'Fashion' => ['Nordstrom', 'Zara', 'H&M', 'Macy\'s'],
            'Beauty' => ['Sephora', 'Ulta', 'Target', 'Amazon Beauty'],
            'Health' => ['CVS', 'Walgreens', 'iHerb', 'Amazon Wellness'],
            'Travel' => ['Expedia', 'Booking.com', 'Hotels.com', 'Kayak'],
            'Food & Dining' => ['DoorDash', 'Uber Eats', 'Grubhub', 'Instacart'],
            default => ['Amazon', 'Walmart', 'Target', 'eBay'],
        };
    }

    private function categoryValueProp(string $category): string
    {
        return match ($category) {
            'Electronics' => 'performance, warranty clarity, and tech-specific support',
            'Fashion' => 'style curation, fit guidance, and seasonal wardrobe updates',
            'Beauty' => 'formula transparency, routine building, and authentic products',
            'Health' => 'wellness goals, ingredient clarity, and repeat-order convenience',
            'Travel' => 'flexible booking options and seasonal fare discounts',
            'Food & Dining' => 'convenient delivery, menu variety, and first-order savings',
            default => 'focused selection, trustworthy checkout, and competitive promotions',
        };
    }
}
