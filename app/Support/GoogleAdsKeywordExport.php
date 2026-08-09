<?php

namespace App\Support;

use App\Models\Store;
use Illuminate\Support\Str;

final class GoogleAdsKeywordExport
{
    /** @var list<string> Google Ads Editor column headers (English). */
    public const HEADERS = [
        'Campaign',
        'Ad Group',
        'Keyword',
        'Criterion Type',
        'Max CPC',
        'Final URL',
        'Status',
        'EU political ads',
    ];

    /** Campaign-level CSV so Editor can set EU political ads before location targeting. */
    public const CAMPAIGN_HEADERS = [
        'Campaign',
        'Campaign Type',
        'Campaign Status',
        'Budget',
        'Budget type',
        'Bid Strategy Type',
        'Networks',
        'Languages',
        'EU political ads',
    ];

    /** Coupon/search campaigns: declare no EU political advertising. */
    public const EU_POLITICAL_ADS = 'No';

    public const BUDGET_TYPE = 'Daily';

    public const BID_STRATEGY_TYPE = 'Manual CPC';

    /** @var list<string> */
    public const MATCH_TYPES = ['Broad', 'Phrase', 'Exact'];

    /** @var list<string> */
    public const AD_GROUP_MODES = ['single', 'standard'];

    /** @var list<string> */
    public const STATUSES = ['Enabled', 'Paused'];

    /** @var list<string> */
    public const CPC_CURRENCIES = ['VND', 'USD'];

    /** @var list<string> */
    public const TARGETING_TYPES = ['Location', 'Excluded location', 'Language', 'Network'];

    /**
     * @return array{
     *     campaign_name: string,
     *     ad_group_mode: string,
     *     ad_group_name: string,
     *     brand_ad_group_suffix: string,
     *     match_type: string,
     *     all_match_types: bool,
     *     keyword_status: string,
     *     max_cpc: string,
     *     max_cpc_currency: string,
     *     final_url: string,
     *     target_locations: list<string>,
     *     excluded_locations: list<string>,
     *     languages: list<string>,
     *     network_search: bool,
     *     network_search_partners: bool,
     *     network_display: bool,
     *     targeting_status: string
     * }
     */
    public function defaultsForStore(Store $store): array
    {
        return array_merge($this->emptyDefaults(), [
            'campaign_name' => $this->defaultCampaignName($store),
            'final_url' => $this->resolveFinalUrl($store),
        ]);
    }

    public function defaultCampaignName(Store $store, ?\DateTimeInterface $date = null): string
    {
        $brand = trim((string) $store->name);
        $dateStr = ($date ?? now())->format('Y-m-d');

        return $brand !== '' ? "{$brand} {$dateStr}" : $dateStr;
    }

    /**
     * @return array{
     *     campaign_name: string,
     *     ad_group_mode: string,
     *     ad_group_name: string,
     *     brand_ad_group_suffix: string,
     *     match_type: string,
     *     all_match_types: bool,
     *     keyword_status: string,
     *     max_cpc: string,
     *     max_cpc_currency: string,
     *     final_url: string,
     *     target_locations: list<string>,
     *     excluded_locations: list<string>,
     *     languages: list<string>,
     *     network_search: bool,
     *     network_search_partners: bool,
     *     network_display: bool,
     *     targeting_status: string
     * }
     */
    public function emptyDefaults(): array
    {
        return [
            'campaign_name' => '',
            'ad_group_mode' => 'standard',
            'ad_group_name' => 'All Keywords',
            'brand_ad_group_suffix' => 'Brand',
            'match_type' => 'Phrase',
            'all_match_types' => false,
            'keyword_status' => 'Enabled',
            'budget' => '250000',
            'budget_type' => self::BUDGET_TYPE,
            'bid_strategy_type' => self::BID_STRATEGY_TYPE,
            'max_cpc' => '',
            'max_cpc_currency' => 'VND',
            'final_url' => '',
            'target_locations' => ['United States'],
            'excluded_locations' => [],
            'languages' => ['English'],
            'network_search' => true,
            'network_search_partners' => false,
            'network_display' => false,
            'targeting_status' => 'Enabled',
        ];
    }

    public function resolveFinalUrl(Store $store): string
    {
        if (filled($store->affiliate_url)) {
            return (string) $store->affiliate_url;
        }

        if (filled($store->website)) {
            return (string) $store->website;
        }

        if (filled($store->slug)) {
            return route('stores.show', $store->slug);
        }

        return rtrim((string) config('site.url'), '/');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     campaign_name: string,
     *     ad_group_mode: string,
     *     ad_group_name: string,
     *     brand_ad_group_suffix: string,
     *     match_type: string,
     *     all_match_types: bool,
     *     keyword_status: string,
     *     max_cpc: string,
     *     max_cpc_currency: string,
     *     final_url: string,
     *     target_locations: list<string>,
     *     excluded_locations: list<string>,
     *     languages: list<string>,
     *     network_search: bool,
     *     network_search_partners: bool,
     *     network_display: bool,
     *     targeting_status: string
     * }
     */
    public function normalizeSettings(array $input, Store $store): array
    {
        $defaults = $this->defaultsForStore($store);

        $campaignName = $this->defaultCampaignName($store);
        $adGroupMode = strtolower(trim((string) ($input['ad_group_mode'] ?? $defaults['ad_group_mode'])));
        if ($adGroupMode === 'brand_and_products') {
            $adGroupMode = 'standard';
        }
        $adGroupName = trim((string) ($input['ad_group_name'] ?? $defaults['ad_group_name']));
        $brandSuffix = trim((string) ($input['brand_ad_group_suffix'] ?? $defaults['brand_ad_group_suffix']));
        $matchType = 'Phrase';
        $allMatchTypes = false;
        $status = $this->normalizeStatus((string) ($input['keyword_status'] ?? $defaults['keyword_status']));
        $targetingStatus = $this->normalizeStatus((string) ($input['targeting_status'] ?? $defaults['targeting_status']));
        $maxCpc = $this->normalizeMaxCpc($input['max_cpc'] ?? '', $input['max_cpc_currency'] ?? 'VND');
        $maxCpcCurrency = $this->normalizeMaxCpcCurrency((string) ($input['max_cpc_currency'] ?? 'VND'));
        $budget = $this->normalizeBudget($input['budget'] ?? $defaults['budget'], $maxCpcCurrency);
        if ($budget === '') {
            $budget = $maxCpcCurrency === 'VND' ? '250000' : '10.00';
        }
        $finalUrl = trim((string) ($input['final_url'] ?? $defaults['final_url']));

        if ($finalUrl === '') {
            $finalUrl = $this->resolveFinalUrl($store);
        }

        return [
            'campaign_name' => $campaignName,
            'ad_group_mode' => in_array($adGroupMode, self::AD_GROUP_MODES, true)
                ? $adGroupMode
                : $defaults['ad_group_mode'],
            'ad_group_name' => $adGroupName !== '' ? $adGroupName : $defaults['ad_group_name'],
            'brand_ad_group_suffix' => $brandSuffix !== '' ? $brandSuffix : $defaults['brand_ad_group_suffix'],
            'match_type' => $matchType,
            'all_match_types' => $allMatchTypes,
            'keyword_status' => $status,
            'budget' => $budget,
            'budget_type' => self::BUDGET_TYPE,
            'bid_strategy_type' => self::BID_STRATEGY_TYPE,
            'max_cpc' => $maxCpc,
            'max_cpc_currency' => $maxCpcCurrency,
            'final_url' => $finalUrl,
            'target_locations' => $this->normalizeLocationList($input['target_locations'] ?? [], $defaults['target_locations']),
            'excluded_locations' => $this->normalizeLocationList($input['excluded_locations'] ?? [], []),
            'languages' => $this->normalizeLanguageList($input['languages'] ?? [], $defaults['languages']),
            'network_search' => filter_var($input['network_search'] ?? $defaults['network_search'], FILTER_VALIDATE_BOOL),
            'network_search_partners' => filter_var($input['network_search_partners'] ?? $defaults['network_search_partners'], FILTER_VALIDATE_BOOL),
            'network_display' => filter_var($input['network_display'] ?? $defaults['network_display'], FILTER_VALIDATE_BOOL),
            'targeting_status' => $targetingStatus,
        ];
    }

    /**
     * @param  array{brand: list<string>, by_product: array<string, list<string>>, all: list<string>}  $result
     * @param  array<string, mixed>  $settings
     * @return list<array<string, string>>
     */
    public function rows(array $result, array $settings, Store $store): array
    {
        $settings = $this->normalizeSettings($settings, $store);
        $matchTypes = $settings['all_match_types']
            ? self::MATCH_TYPES
            : [$settings['match_type']];

        $rows = [];
        $campaign = $settings['campaign_name'];
        $status = $settings['keyword_status'];
        $maxCpc = $settings['max_cpc'];
        $finalUrl = $settings['final_url'];
        $brandLabel = trim($store->name);

        foreach ($result['brand'] as $keyword) {
            $adGroup = $this->adGroupName($settings, $brandLabel, null);
            $rows = array_merge($rows, $this->rowsForKeyword($campaign, $adGroup, $keyword, $matchTypes, $maxCpc, $finalUrl, $status));
        }

        foreach ($result['by_product'] as $product => $keywords) {
            $adGroup = $this->adGroupName($settings, $brandLabel, (string) $product);

            foreach ($keywords as $keyword) {
                $rows = array_merge($rows, $this->rowsForKeyword($campaign, $adGroup, $keyword, $matchTypes, $maxCpc, $finalUrl, $status));
            }
        }

        return $rows;
    }

    /**
     * @param  array{brand: list<string>, by_product: array<string, list<string>>, all: list<string>}  $result
     * @param  array<string, mixed>  $settings
     */
    public function toCsv(array $result, array $settings, Store $store): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, self::HEADERS);

        foreach ($this->rows($result, $settings, $store) as $row) {
            fputcsv($handle, [
                $row['Campaign'],
                $row['Ad Group'],
                $row['Keyword'],
                $row['Criterion Type'],
                $row['Max CPC'],
                $row['Final URL'],
                $row['Status'],
                $row['EU political ads'] ?? self::EU_POLITICAL_ADS,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return is_string($csv) ? $csv : '';
    }

    public function downloadFilename(Store $store): string
    {
        $slug = Str::slug($store->name) ?: 'store';

        return "google-ads-keywords-{$slug}.csv";
    }

    public function assetsDownloadFilename(Store $store): string
    {
        $slug = Str::slug($store->name) ?: 'store';

        return "google-ads-assets-{$slug}.csv";
    }

    public function campaignDownloadFilename(Store $store): string
    {
        $slug = Str::slug($store->name) ?: 'store';

        return "google-ads-campaign-{$slug}.csv";
    }

    /**
     * Campaign settings CSV for Google Ads Editor (budget + EU political ads declaration).
     *
     * @param  array<string, mixed>  $settings
     */
    public function toCampaignCsv(array $settings): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        $campaignName = trim((string) ($settings['campaign_name'] ?? ''));
        $status = (string) ($settings['keyword_status'] ?? 'Enabled');
        if (! in_array($status, self::STATUSES, true)) {
            $status = 'Enabled';
        }

        $budget = trim((string) ($settings['budget'] ?? ''));
        if ($budget === '') {
            $currency = $this->normalizeMaxCpcCurrency((string) ($settings['max_cpc_currency'] ?? 'VND'));
            $budget = $currency === 'VND' ? '250000' : '10.00';
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, self::CAMPAIGN_HEADERS);
        fputcsv($handle, [
            $campaignName,
            'Search',
            $status,
            $budget,
            self::BUDGET_TYPE,
            self::BID_STRATEGY_TYPE,
            GoogleAdsTargetingCatalog::networksCsv($settings),
            GoogleAdsTargetingCatalog::languageCodesCsv(
                is_array($settings['languages'] ?? null) ? $settings['languages'] : []
            ),
            self::EU_POLITICAL_ADS,
        ]);

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return is_string($csv) ? $csv : '';
    }

    /** @var list<string> */
    public const ASSET_HEADERS = [
        'Campaign',
        'Link text',
        'Final URL',
        'Description line 1',
        'Description line 2',
        'Callout text',
        'Status',
    ];

    public function toAssetsCsv(string $campaignName): string
    {
        $assets = SiteAdsSettings::get();
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, self::ASSET_HEADERS);

        foreach ($assets['sitelinks'] as $sitelink) {
            fputcsv($handle, [
                $campaignName,
                $sitelink['link_text'],
                $sitelink['final_url'],
                $sitelink['description_1'],
                $sitelink['description_2'],
                '',
                $sitelink['status'],
            ]);
        }

        foreach ($assets['callouts'] as $callout) {
            fputcsv($handle, [
                $campaignName,
                '',
                '',
                '',
                '',
                $callout['text'],
                $callout['status'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return is_string($csv) ? $csv : '';
    }

    public function targetingDownloadFilename(Store $store): string
    {
        $slug = Str::slug($store->name) ?: 'store';

        return "google-ads-targeting-{$slug}.csv";
    }

    /** @var list<string> Google Ads Editor location import columns. */
    public const TARGETING_HEADERS = [
        'Campaign',
        'Location',
        'Type',
    ];

    /**
     * Location targeting CSV for Google Ads Editor.
     * Languages/networks belong on Campaign CSV — Editor rejects mixed Type/Value rows.
     *
     * @param  array<string, mixed>  $settings
     */
    public function toTargetingCsv(array $settings, Store $store): string
    {
        $settings = $this->normalizeSettings($settings, $store);
        $campaign = $settings['campaign_name'];

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, self::TARGETING_HEADERS);

        foreach ($settings['target_locations'] as $location) {
            fputcsv($handle, [$campaign, $location, '']);
        }

        foreach ($settings['excluded_locations'] as $location) {
            fputcsv($handle, [$campaign, $location, 'Negative']);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return is_string($csv) ? $csv : '';
    }

    /**
     * @param  list<string>  $matchTypes
     * @return list<array<string, string>>
     */
    private function rowsForKeyword(
        string $campaign,
        string $adGroup,
        string $keyword,
        array $matchTypes,
        string $maxCpc,
        string $finalUrl,
        string $status
    ): array {
        $rows = [];

        foreach ($matchTypes as $matchType) {
            $rows[] = [
                'Campaign' => $campaign,
                'Ad Group' => $adGroup,
                'Keyword' => $keyword,
                'Criterion Type' => $matchType,
                'Max CPC' => $maxCpc,
                'Final URL' => $finalUrl,
                'Status' => $status,
                'EU political ads' => self::EU_POLITICAL_ADS,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function adGroupName(array $settings, string $brandLabel, ?string $product): string
    {
        if ($settings['ad_group_mode'] === 'single') {
            return $settings['ad_group_name'];
        }

        // Legacy product-keyword export: brand group + one group per product.
        if ($product === null) {
            return trim($brandLabel.' - '.($settings['brand_ad_group_suffix'] ?: 'Brand'));
        }

        return trim($brandLabel.' - '.Str::title($product));
    }

    private function normalizeMatchType(string $value): string
    {
        $value = ucfirst(strtolower(trim($value)));

        return in_array($value, self::MATCH_TYPES, true) ? $value : 'Phrase';
    }

    private function normalizeStatus(string $value): string
    {
        $value = ucfirst(strtolower(trim($value)));

        return in_array($value, self::STATUSES, true) ? $value : 'Enabled';
    }

    private function normalizeMaxCpc(mixed $value, mixed $currency = 'USD'): string
    {
        if (! filled($value)) {
            return '';
        }

        $currency = $this->normalizeMaxCpcCurrency((string) $currency);
        $normalized = preg_replace('/[^0-9.]/', '', (string) $value) ?? '';

        if ($normalized === '' || ! is_numeric($normalized)) {
            return '';
        }

        if ($currency === 'VND') {
            return (string) max(0, (int) round((float) $normalized));
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function normalizeBudget(mixed $value, mixed $currency = 'USD'): string
    {
        $normalized = $this->normalizeMaxCpc($value, $currency);

        if ($normalized === '' || (float) $normalized <= 0) {
            return '';
        }

        return $normalized;
    }

    private function normalizeMaxCpcCurrency(string $value): string
    {
        $value = strtoupper(trim($value));

        return in_array($value, self::CPC_CURRENCIES, true) ? $value : 'VND';
    }

    /**
     * @param  mixed  $values
     * @param  list<string>  $fallback
     * @return list<string>
     */
    private function normalizeLocationList(mixed $values, array $fallback): array
    {
        if (! is_array($values)) {
            return $fallback;
        }

        $normalized = collect($values)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $normalized !== [] ? $normalized : $fallback;
    }

    /**
     * @param  mixed  $values
     * @param  list<string>  $fallback
     * @return list<string>
     */
    private function normalizeLanguageList(mixed $values, array $fallback): array
    {
        if (! is_array($values)) {
            return $fallback;
        }

        $normalized = collect($values)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($normalized === []) {
            return $fallback;
        }

        if (in_array('All languages', $normalized, true)) {
            return GoogleAdsTargetingCatalog::concreteLanguages();
        }

        $allowed = GoogleAdsTargetingCatalog::concreteLanguages();
        $normalized = array_values(array_intersect($normalized, $allowed));

        return $normalized !== [] ? $normalized : $fallback;
    }
}
