<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\Store;
use Illuminate\Support\Str;

/**
 * Builds a Search campaign with 3 ad groups (Coupons / Discounts / Promo)
 * from the "Chạy ADS Chuẩn" template: keywords + RSA headlines/descriptions.
 */
final class GoogleAdsStandardCampaign
{
    public const AD_GROUPS = ['Coupons', 'Discounts', 'Promo'];

    public const HEADLINE_MAX = 30;

    public const DESCRIPTION_MAX = 90;

    public const PATH_MAX = 15;

    /** @var list<string> */
    public const KEYWORD_HEADERS = [
        'Campaign',
        'Ad Group',
        'Keyword',
        'Criterion Type',
        'Max CPC',
        'Final URL',
        'Status',
        'EU political ads',
    ];

    /** @var list<string> */
    public const AD_HEADERS = [
        'Campaign',
        'Ad Group',
        'Ad type',
        'Headline 1',
        'Headline 2',
        'Headline 3',
        'Headline 4',
        'Headline 5',
        'Headline 6',
        'Headline 7',
        'Headline 8',
        'Headline 9',
        'Headline 10',
        'Headline 11',
        'Headline 12',
        'Headline 13',
        'Headline 14',
        'Headline 15',
        'Description 1',
        'Description 2',
        'Description 3',
        'Description 4',
        'Path 1',
        'Path 2',
        'Final URL',
        'Status',
    ];

    /**
     * @return array{
     *     Coupons: array{keywords: list<string>, headlines: list<string>, descriptions: list<string>},
     *     Discounts: array{keywords: list<string>, headlines: list<string>, descriptions: list<string>},
     *     Promo: array{keywords: list<string>, headlines: list<string>, descriptions: list<string>}
     * }
     */
    public function templates(): array
    {
        return [
            'Coupons' => [
                'keywords' => [
                    '{store} coupon',
                    '{store} coupon code',
                    '{store} promo code',
                    '{store} promo',
                    'Promo code {store}',
                    'Coupon {store}',
                    'Coupon code {store}',
                    'Coupon code for {store}',
                    'Coupon for {store}',
                    'Promo code for {store}',
                ],
                // 15 unique RSA headlines (max) — keyword, offer, CTA, trust, urgency.
                'headlines' => [
                    '{store} Coupon Code',
                    '{store} Coupons',
                    '{store} Promo Codes',
                    'Save {pct}% at {store}',
                    'Get {pct}% Off Today',
                    'Verified Coupon Codes',
                    'Working Codes {year}',
                    'Apply Code & Save',
                    'Exclusive {store} Deals',
                    'Shop {store} & Save',
                    'Codes Updated Daily',
                    'Claim {pct}% Off Now',
                    'Trusted {store} Coupons',
                    'Save More at Checkout',
                    'Top Coupons This Week',
                ],
                'descriptions' => [
                    'Find verified {store} coupon codes and save up to {pct}% on your order today. Updated daily!',
                    'Copy a working {store} coupon, apply at checkout, and unlock savings before you pay today!',
                    'Shop {store} with trusted promo codes. Save {pct}% while deals last. Codes are tested daily.',
                    'Get tested {store} coupons for {year}. Fresh working codes help you pay less on every order!',
                ],
            ],
            'Discounts' => [
                'keywords' => [
                    '{store} discount code',
                    '{store} discount',
                    'Discount code for {store}',
                    'Discount {store}',
                    'Discount code {store}',
                    '{store} discount coupon',
                ],
                'headlines' => [
                    '{store} Discount Code',
                    '{store} Discounts',
                    '{store} Sale Savings',
                    'Save {pct}% at {store}',
                    'Get {pct}% Off Today',
                    'Verified Discount Codes',
                    'Best Deals in {year}',
                    'Apply Discount Now',
                    'Exclusive {store} Offers',
                    'Shop {store} Cheaper',
                    'Discounts Updated Daily',
                    'Claim {pct}% Off Now',
                    'Trusted {store} Deals',
                    'Lower Price at Checkout',
                    'Top Discounts This Week',
                ],
                'descriptions' => [
                    'Use a verified {store} discount code and save up to {pct}% on your order today at checkout.',
                    'Apply your {store} discount at checkout to cut costs fast. Working codes are checked daily.',
                    'Discover current {store} discounts for {year}. Save {pct}% before this limited offer expires.',
                    'Shop smarter at {store} with working discount codes. Real savings on every order await you.',
                ],
            ],
            'Promo' => [
                'keywords' => [
                    '{store} promo code',
                    '{store} promo',
                    'Promo code for {store}',
                    'Promo {store}',
                    'Promo code {store}',
                    '{store} Promo coupon',
                ],
                'headlines' => [
                    '{store} Promo Code',
                    '{store} Promos',
                    '{store} Promo Deals',
                    'Save {pct}% at {store}',
                    'Get {pct}% Off Today',
                    'Verified Promo Codes',
                    'Hot Promos for {year}',
                    'Enter Promo & Save',
                    'Exclusive {store} Promo',
                    'Shop {store} Promos',
                    'Promos Updated Daily',
                    'Claim {pct}% Off Now',
                    'Trusted {store} Codes',
                    'Promo Ready to Apply',
                    'Top Promos This Week',
                ],
                'descriptions' => [
                    'Grab a verified {store} promo code and save up to {pct}% instantly when you check out today!',
                    'Enter your {store} promo today for exclusive savings. Fresh working codes are tested daily.',
                    'Unlock {store} promo deals for {year}. Save {pct}% while these limited-time offers still last!',
                    'Shop {store} with working promo codes. Fast to apply, clear savings ready on your order!',
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     campaign_name: string,
     *     ad_group_mode: string,
     *     final_url: string,
     *     discount_percent: int,
     *     path_1: string,
     *     path_2: string,
     *     status: string,
     *     match_type: string,
     *     max_cpc: string,
     *     groups: array<string, array{
     *         keywords: list<string>,
     *         headlines: list<string>,
     *         descriptions: list<string>
     *     }>,
     *     keyword_count: int,
     *     generated_at: string
     * }
     */
    public function generate(Store $store, array $settings = []): array
    {
        $storeName = trim((string) $store->name);
        $percent = $this->normalizePercent($settings['discount_percent'] ?? $this->suggestDiscountPercent($store));
        $adsExport = new GoogleAdsKeywordExport;
        $campaignName = $adsExport->defaultCampaignName($store);

        $adGroupMode = strtolower(trim((string) ($settings['ad_group_mode'] ?? 'standard')));
        if ($adGroupMode === 'brand_and_products') {
            $adGroupMode = 'standard';
        }
        if (! in_array($adGroupMode, GoogleAdsKeywordExport::AD_GROUP_MODES, true)) {
            $adGroupMode = 'standard';
        }

        $finalUrl = trim((string) ($settings['final_url'] ?? ''));
        if ($finalUrl === '') {
            $finalUrl = $adsExport->resolveFinalUrl($store);
        }

        $status = (string) ($settings['keyword_status'] ?? 'Enabled');
        if (! in_array($status, GoogleAdsKeywordExport::STATUSES, true)) {
            $status = 'Enabled';
        }

        $matchType = 'Phrase';
        $maxCpc = trim((string) ($settings['max_cpc'] ?? ''));

        $vars = [
            'store' => $storeName,
            'pct' => (string) $percent,
            'year' => (string) ($settings['year'] ?? now()->year),
        ];

        $groups = [];
        $keywordCount = 0;

        foreach ($this->templates() as $groupName => $template) {
            $keywords = $this->fillList($template['keywords'], $vars, null);
            $headlines = $this->fillList($template['headlines'], $vars, self::HEADLINE_MAX);
            $descriptions = $this->fillList($template['descriptions'], $vars, self::DESCRIPTION_MAX);

            // RSA Excellent targets: as many unique headlines as possible (up to 15) + 4 descriptions.
            $headlines = $this->ensureMinimumHeadlines($headlines, $storeName, $percent, $groupName);
            $descriptions = $this->ensureMinimumDescriptions($descriptions, $storeName, $percent);

            $groups[$groupName] = [
                'keywords' => $keywords,
                'headlines' => array_slice($headlines, 0, 15),
                'descriptions' => array_slice($descriptions, 0, 4),
            ];
            $keywordCount += count($keywords);
        }

        if ($adGroupMode === 'single') {
            $groups = $this->mergeIntoSingleAdGroup($groups, (string) ($settings['ad_group_name'] ?? 'All Keywords'));
            $keywordCount = count($groups[array_key_first($groups)]['keywords'] ?? []);
        }

        $slug = Str::slug($store->slug ?: $storeName);

        return [
            'campaign_name' => $campaignName,
            'ad_group_mode' => $adGroupMode,
            'final_url' => $finalUrl,
            'discount_percent' => $percent,
            'path_1' => $this->clip('coupons', self::PATH_MAX),
            'path_2' => $this->clip($slug !== '' ? $slug : 'deals', self::PATH_MAX),
            'status' => $status,
            'match_type' => $matchType,
            'max_cpc' => $maxCpc,
            'groups' => $groups,
            'keyword_count' => $keywordCount,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, array{keywords: list<string>, headlines: list<string>, descriptions: list<string>}>  $groups
     * @return array<string, array{keywords: list<string>, headlines: list<string>, descriptions: list<string>}>
     */
    private function mergeIntoSingleAdGroup(array $groups, string $adGroupName): array
    {
        $name = trim($adGroupName) !== '' ? trim($adGroupName) : 'All Keywords';
        $keywords = [];
        $headlines = [];
        $descriptions = [];

        foreach ($groups as $group) {
            foreach ($group['keywords'] as $keyword) {
                if (! in_array($keyword, $keywords, true)) {
                    $keywords[] = $keyword;
                }
            }
            foreach ($group['headlines'] as $headline) {
                if (! in_array($headline, $headlines, true)) {
                    $headlines[] = $headline;
                }
            }
            foreach ($group['descriptions'] as $description) {
                if (! in_array($description, $descriptions, true)) {
                    $descriptions[] = $description;
                }
            }
        }

        return [
            $name => [
                'keywords' => $keywords,
                'headlines' => array_slice($headlines, 0, 15),
                'descriptions' => array_slice($descriptions, 0, 4),
            ],
        ];
    }

    public function suggestDiscountPercent(Store $store): int
    {
        $max = (int) Coupon::query()
            ->where('store_id', $store->id)
            ->where('discount_type', 'percent')
            ->where('is_active', true)
            ->max('discount_value');

        if ($max >= 1 && $max <= 90) {
            return $max;
        }

        // Fallback: parse titles like "40% OFF" from active coupons.
        $titles = Coupon::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->orderByDesc('is_featured')
            ->orderByDesc('store_sort_order')
            ->limit(20)
            ->pluck('title');

        $found = 0;
        foreach ($titles as $title) {
            if (preg_match('/(\d{1,2})\s*%/', (string) $title, $m)) {
                $n = (int) $m[1];
                if ($n >= 1 && $n <= 90) {
                    $found = max($found, $n);
                }
            }
        }

        return $found > 0 ? $found : 40;
    }

    /**
     * @param  array<string, mixed>  $campaign
     * @return list<array<string, string>>
     */
    public function keywordRows(array $campaign): array
    {
        $rows = [];
        $groups = is_array($campaign['groups'] ?? null) ? $campaign['groups'] : [];

        foreach ($groups as $groupName => $group) {
            $keywords = is_array($group) ? ($group['keywords'] ?? []) : [];
            if (! is_array($keywords)) {
                continue;
            }

            foreach ($keywords as $keyword) {
                $rows[] = [
                    'Campaign' => (string) ($campaign['campaign_name'] ?? ''),
                    'Ad Group' => (string) $groupName,
                    'Keyword' => (string) $keyword,
                    'Criterion Type' => (string) ($campaign['match_type'] ?? 'Phrase'),
                    'Max CPC' => (string) ($campaign['max_cpc'] ?? ''),
                    'Final URL' => (string) ($campaign['final_url'] ?? ''),
                    'Status' => (string) ($campaign['status'] ?? 'Enabled'),
                    'EU political ads' => GoogleAdsKeywordExport::EU_POLITICAL_ADS,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $campaign
     * @return list<array<string, string>>
     */
    public function adRows(array $campaign): array
    {
        $rows = [];
        $groups = is_array($campaign['groups'] ?? null) ? $campaign['groups'] : [];

        foreach ($groups as $groupName => $group) {
            if (! is_array($group)) {
                continue;
            }

            $headlines = array_values(array_filter(array_map('strval', $group['headlines'] ?? [])));
            $descriptions = array_values(array_filter(array_map('strval', $group['descriptions'] ?? [])));

            if (count($headlines) < 3 || count($descriptions) < 2) {
                continue;
            }

            $row = [
                'Campaign' => (string) ($campaign['campaign_name'] ?? ''),
                'Ad Group' => (string) $groupName,
                'Ad type' => 'Responsive search ad',
            ];

            for ($i = 1; $i <= 15; $i++) {
                $row['Headline '.$i] = $headlines[$i - 1] ?? '';
            }
            for ($i = 1; $i <= 4; $i++) {
                $row['Description '.$i] = $descriptions[$i - 1] ?? '';
            }

            $row['Path 1'] = (string) ($campaign['path_1'] ?? 'coupons');
            $row['Path 2'] = (string) ($campaign['path_2'] ?? '');
            $row['Final URL'] = (string) ($campaign['final_url'] ?? '');
            $row['Status'] = (string) ($campaign['status'] ?? 'Enabled');

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $campaign
     */
    public function toKeywordsCsv(array $campaign): string
    {
        return $this->toCsv(self::KEYWORD_HEADERS, $this->keywordRows($campaign));
    }

    /**
     * @param  array<string, mixed>  $campaign
     */
    public function toAdsCsv(array $campaign): string
    {
        $rows = [];
        foreach ($this->adRows($campaign) as $row) {
            $ordered = [];
            foreach (self::AD_HEADERS as $header) {
                $ordered[] = $row[$header] ?? '';
            }
            $rows[] = $ordered;
        }

        return $this->toCsv(self::AD_HEADERS, $rows, alreadyOrdered: true);
    }

    public function keywordsDownloadFilename(Store $store): string
    {
        $slug = Str::slug($store->name) ?: 'store';

        return "google-ads-standard-keywords-{$slug}.csv";
    }

    public function adsDownloadFilename(Store $store): string
    {
        $slug = Str::slug($store->name) ?: 'store';

        return "google-ads-standard-ads-{$slug}.csv";
    }

    private function normalizePercent(mixed $value): int
    {
        if (is_string($value)) {
            $value = preg_replace('/[^\d]/', '', $value) ?? '';
        }

        $n = (int) $value;

        if ($n < 1) {
            return 40;
        }

        return min(90, $n);
    }

    /**
     * @param  list<string>  $templates
     * @param  array{store: string, pct: string, year: string}  $vars
     * @return list<string>
     */
    private function fillList(array $templates, array $vars, ?int $maxLen): array
    {
        $out = [];

        foreach ($templates as $template) {
            $text = $this->fill($template, $vars);
            if ($maxLen !== null) {
                $text = $this->fitLength($text, $maxLen, $vars);
            }
            if ($text === '') {
                continue;
            }
            $out[] = $text;
        }

        return array_values(array_unique($out));
    }

    /**
     * @param  array{store: string, pct: string, year: string}  $vars
     */
    private function fill(string $template, array $vars): string
    {
        return str_replace(
            ['{store}', '{pct}', '{year}'],
            [$vars['store'], $vars['pct'], $vars['year']],
            $template
        );
    }

    /**
     * @param  array{store: string, pct: string, year: string}  $vars
     */
    private function fitLength(string $text, int $max, array $vars): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        // Try shorter store nickname (first word).
        $shortStore = Str::before($vars['store'], ' ');
        if ($shortStore !== '' && $shortStore !== $vars['store']) {
            $alt = str_replace($vars['store'], $shortStore, $text);
            $alt = trim(preg_replace('/\s+/', ' ', $alt) ?? $alt);
            if (mb_strlen($alt) <= $max) {
                return $alt;
            }
        }

        // Drop "Off {store}" style endings if still too long.
        $clipped = $this->clip($text, $max);

        return $clipped;
    }

    private function clip(string $text, int $max): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $slice = rtrim(mb_substr($text, 0, $max));
        $break = mb_strrpos($slice, ' ');
        if ($break !== false && $break >= (int) floor($max * 0.6)) {
            $slice = rtrim(mb_substr($slice, 0, $break));
        }

        return rtrim($slice, " \t.,;:!-");
    }

    /**
     * @param  list<string>  $headlines
     * @return list<string>
     */
    private function ensureMinimumHeadlines(array $headlines, string $store, int $percent, string $groupName = 'Coupons'): array
    {
        $label = match ($groupName) {
            'Discounts' => 'Discounts',
            'Promo' => 'Promos',
            default => 'Coupons',
        };

        $fallbacks = [
            $this->clip("{$store} {$label}", self::HEADLINE_MAX),
            $this->clip("{$store} Promo Codes", self::HEADLINE_MAX),
            $this->clip("Save {$percent}% at {$store}", self::HEADLINE_MAX),
            "Get {$percent}% Off Today",
            'Verified Codes Only',
            'Codes Updated Daily',
            'Apply Code & Save',
            'Claim Your Deal Now',
            'Trusted Savings Inside',
            'Shop Smarter Today',
            'Limited-Time Savings',
            'Unlock Extra Savings',
            'Checkout & Save More',
            'Fresh Deals This Week',
            'Start Saving Instantly',
        ];

        foreach ($fallbacks as $fb) {
            if (count($headlines) >= 15) {
                break;
            }
            if ($fb !== '' && ! in_array($fb, $headlines, true)) {
                $headlines[] = $fb;
            }
        }

        return $headlines;
    }

    /**
     * @param  list<string>  $descriptions
     * @return list<string>
     */
    private function ensureMinimumDescriptions(array $descriptions, string $store, int $percent): array
    {
        $fallbacks = [
            $this->clip("Save up to {$percent}% with verified {$store} coupon codes today. Fresh deals updated daily for shoppers.", self::DESCRIPTION_MAX),
            $this->clip("Apply a working {$store} code at checkout and unlock exclusive savings fast on your next order.", self::DESCRIPTION_MAX),
            $this->clip("Shop {$store} smarter with trusted promo codes. Save {$percent}% now before limited offers end today.", self::DESCRIPTION_MAX),
            $this->clip("Get tested {$store} coupons for this season. Clear savings on every order with codes checked daily.", self::DESCRIPTION_MAX),
        ];

        foreach ($fallbacks as $fb) {
            if (count($descriptions) >= 4) {
                break;
            }
            if ($fb !== '' && ! in_array($fb, $descriptions, true)) {
                $descriptions[] = $fb;
            }
        }

        return $descriptions;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<string, string>|list<string>>  $rows
     */
    private function toCsv(array $headers, array $rows, bool $alreadyOrdered = false): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            if ($alreadyOrdered) {
                fputcsv($handle, $row);
                continue;
            }

            $ordered = [];
            foreach ($headers as $header) {
                $ordered[] = $row[$header] ?? '';
            }
            fputcsv($handle, $ordered);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return is_string($csv) ? $csv : '';
    }
}
