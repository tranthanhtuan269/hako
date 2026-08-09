<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreKeywordSet;
use App\Support\GoogleAdsKeywordExport;
use App\Support\GoogleAdsStandardCampaign;
use App\Support\GoogleAdsTargetingCatalog;
use App\Support\KeywordGenerationEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KeywordGeneratorController extends Controller
{
    public function create(Request $request, KeywordGenerationEngine $engine, GoogleAdsKeywordExport $adsExport, GoogleAdsStandardCampaign $standardCampaign): View
    {
        $this->authorize('viewAny', Store::class);

        $selectedStoreId = (int) old('store_id', $request->query('store_id', 0));
        $saved = null;
        $products = [''];
        $result = null;
        $brandLabel = null;
        $savedAt = null;
        $adsSettings = null;
        $selectedStore = null;
        $standardCampaignData = null;
        $suggestedDiscount = 40;

        if ($selectedStoreId > 0) {
            $store = Store::query()->find($selectedStoreId);

            if ($store && auth()->user()->can('view', $store)) {
                $selectedStore = $store;
                $saved = $this->hydrateFromSaved($store, $engine, $adsExport);
                if ($saved) {
                    $products = $saved['products'];
                    $result = $saved['result'];
                    $brandLabel = $saved['brandLabel'];
                    $savedAt = $saved['savedAt'];
                    $adsSettings = $saved['adsSettings'];
                    $standardCampaignData = is_array($adsSettings['standard_campaign'] ?? null)
                        ? $adsSettings['standard_campaign']
                        : null;
                    $suggestedDiscount = (int) ($adsSettings['discount_percent']
                        ?? $standardCampaign->suggestDiscountPercent($store));
                } else {
                    $adsSettings = $adsExport->defaultsForStore($store);
                    $brandLabel = $store->name;
                    $suggestedDiscount = $standardCampaign->suggestDiscountPercent($store);
                }
            }
        }

        return view('member.keywords.form', [
            'stores' => $this->storesForUser(),
            'engine' => $engine,
            'adsExport' => $adsExport,
            'selectedStoreId' => $selectedStoreId ?: null,
            'selectedStore' => $selectedStore,
            'products' => $products,
            'result' => $result,
            'brandLabel' => $brandLabel,
            'savedAt' => $savedAt,
            'fromSaved' => $saved !== null && $savedAt !== null,
            'adsSettings' => $adsSettings,
            'standardCampaign' => $standardCampaignData,
            'suggestedDiscount' => $suggestedDiscount,
        ]);
    }

    public function load(Request $request, KeywordGenerationEngine $engine, GoogleAdsKeywordExport $adsExport, GoogleAdsStandardCampaign $standardCampaign): JsonResponse
    {
        $this->authorize('viewAny', Store::class);

        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $store = Store::query()->findOrFail($data['store_id']);
        $this->authorize('view', $store);

        $saved = $this->hydrateFromSaved($store, $engine, $adsExport);
        $suggestedDiscount = $standardCampaign->suggestDiscountPercent($store);

        if (! $saved) {
            return response()->json([
                'ok' => true,
                'found' => false,
                'products' => [''],
                'ads_settings' => $adsExport->defaultsForStore($store),
                'suggested_discount' => $suggestedDiscount,
                'standard_campaign' => null,
                'standard_html' => '',
            ]);
        }

        $adsSettings = $saved['adsSettings'];
        $standard = is_array($adsSettings['standard_campaign'] ?? null)
            ? $adsSettings['standard_campaign']
            : null;
        $suggestedDiscount = (int) ($adsSettings['discount_percent'] ?? $suggestedDiscount);

        return response()->json([
            'ok' => true,
            'found' => true,
            'store_name' => $saved['brandLabel'],
            'products' => $saved['products'],
            'result' => $saved['result'],
            'saved_at' => $saved['savedAt'],
            'ads_settings' => $adsSettings,
            'suggested_discount' => $suggestedDiscount,
            'standard_campaign' => $standard,
            'export_csv_url' => route('member.keywords.export-csv', ['store_id' => $store->id]),
            'export_assets_csv_url' => route('member.keywords.export-assets-csv', ['store_id' => $store->id]),
            'export_targeting_csv_url' => route('member.keywords.export-targeting-csv', ['store_id' => $store->id]),
            'export_standard_keywords_csv_url' => route('member.keywords.export-standard-keywords-csv', ['store_id' => $store->id]),
            'export_standard_ads_csv_url' => route('member.keywords.export-standard-ads-csv', ['store_id' => $store->id]),
            'results_html' => view('member.keywords.partials.results', [
                'result' => $saved['result'],
                'brandLabel' => $saved['brandLabel'],
                'engine' => $engine,
                'savedAt' => $saved['savedAt'],
                'fromSaved' => true,
                'adsSettings' => $adsSettings,
                'exportCsvUrl' => route('member.keywords.export-csv', ['store_id' => $store->id]),
                'exportAssetsCsvUrl' => route('member.keywords.export-assets-csv', ['store_id' => $store->id]),
                'exportTargetingCsvUrl' => route('member.keywords.export-targeting-csv', ['store_id' => $store->id]),
            ])->render(),
            'standard_html' => $standard
                ? view('member.keywords.partials.standard-results', [
                    'standardCampaign' => $standard,
                    'brandLabel' => $saved['brandLabel'],
                    'exportCampaignUrl' => route('member.keywords.export-campaign-csv', ['store_id' => $store->id]),
                    'exportKeywordsUrl' => route('member.keywords.export-standard-keywords-csv', ['store_id' => $store->id]),
                    'exportAdsUrl' => route('member.keywords.export-standard-ads-csv', ['store_id' => $store->id]),
                    'exportAssetsCsvUrl' => route('member.keywords.export-assets-csv', ['store_id' => $store->id]),
                    'exportTargetingCsvUrl' => route('member.keywords.export-targeting-csv', ['store_id' => $store->id]),
                ])->render()
                : '',
        ]);
    }

    public function generate(Request $request, KeywordGenerationEngine $engine, GoogleAdsKeywordExport $adsExport): View
    {
        $this->authorize('viewAny', Store::class);

        $data = $request->validate(array_merge([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'products' => ['nullable', 'array'],
            'products.*' => ['nullable', 'string', 'max:120'],
        ], $this->adsSettingsRules()));

        $store = Store::query()->findOrFail($data['store_id']);
        $this->authorize('view', $store);

        $products = $this->normalizeProducts($data['products'] ?? []);
        $result = $engine->generate($store->name, $products);
        $adsSettings = $adsExport->normalizeSettings($data, $store);

        $existingSettings = is_array($store->keywordSet?->ads_settings)
            ? $store->keywordSet->ads_settings
            : [];
        if (isset($existingSettings['standard_campaign'])) {
            $adsSettings['standard_campaign'] = $existingSettings['standard_campaign'];
        }
        if (isset($existingSettings['discount_percent'])) {
            $adsSettings['discount_percent'] = $existingSettings['discount_percent'];
        }

        StoreKeywordSet::updateOrCreate(
            ['store_id' => $store->id],
            [
                'user_id' => auth()->id(),
                'products' => $products,
                'result' => $result,
                'ads_settings' => $adsSettings,
            ]
        );

        return view('member.keywords.form', [
            'stores' => $this->storesForUser(),
            'engine' => $engine,
            'adsExport' => $adsExport,
            'selectedStoreId' => $store->id,
            'selectedStore' => $store,
            'products' => $products === [] ? [''] : $products,
            'result' => $result,
            'brandLabel' => $store->name,
            'savedAt' => now()->format('M j, Y g:i A'),
            'fromSaved' => false,
            'adsSettings' => $adsSettings,
            'standardCampaign' => is_array($adsSettings['standard_campaign'] ?? null)
                ? $adsSettings['standard_campaign']
                : null,
            'suggestedDiscount' => (int) ($adsSettings['discount_percent'] ?? 40),
        ]);
    }

    public function generateStandard(Request $request, GoogleAdsKeywordExport $adsExport, GoogleAdsStandardCampaign $standardCampaign): RedirectResponse
    {
        $this->authorize('viewAny', Store::class);

        $data = $request->validate(array_merge([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'discount_percent' => ['nullable', 'integer', 'min:1', 'max:90'],
        ], $this->adsSettingsRules()));

        $store = Store::query()->findOrFail($data['store_id']);
        $this->authorize('view', $store);

        $adsSettings = $adsExport->normalizeSettings($data, $store);
        $campaign = $standardCampaign->generate($store, array_merge($adsSettings, [
            'discount_percent' => $data['discount_percent'] ?? null,
        ]));

        $adsSettings['discount_percent'] = $campaign['discount_percent'];
        $adsSettings['standard_campaign'] = $campaign;

        $existing = $store->keywordSet;
        $products = is_array($existing?->products) ? $existing->products : [];
        $result = is_array($existing?->result) ? $existing->result : null;

        StoreKeywordSet::updateOrCreate(
            ['store_id' => $store->id],
            [
                'user_id' => auth()->id(),
                'products' => $products,
                'result' => $result ?? ['brand' => [], 'by_product' => [], 'all' => []],
                'ads_settings' => $adsSettings,
            ]
        );

        return redirect()
            ->route('member.keywords.create', ['store_id' => $store->id])
            ->with('success', 'Standard campaign generated. Download the CSV files below for Google Ads Editor.')
            ->withFragment('standard-campaign-results');
    }

    public function exportStandardKeywordsCsv(Request $request, GoogleAdsStandardCampaign $standardCampaign): StreamedResponse
    {
        $campaign = $this->resolveSavedStandardCampaign($request, $standardCampaign);
        $store = $campaign['store'];
        $csv = $standardCampaign->toKeywordsCsv($campaign['data']);
        $filename = $standardCampaign->keywordsDownloadFilename($store);

        return response()->streamDownload(
            static function () use ($csv): void {
                echo $csv;
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    public function exportStandardAdsCsv(Request $request, GoogleAdsStandardCampaign $standardCampaign): StreamedResponse
    {
        $campaign = $this->resolveSavedStandardCampaign($request, $standardCampaign);
        $store = $campaign['store'];
        $csv = $standardCampaign->toAdsCsv($campaign['data']);
        $filename = $standardCampaign->adsDownloadFilename($store);

        return response()->streamDownload(
            static function () use ($csv): void {
                echo $csv;
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    public function exportCsv(Request $request, GoogleAdsKeywordExport $adsExport): StreamedResponse
    {
        $this->authorize('viewAny', Store::class);

        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $store = Store::query()->findOrFail($data['store_id']);
        $this->authorize('view', $store);

        $set = $store->keywordSet;

        if (! $set || ! is_array($set->result) || $set->result === []) {
            abort(404, 'No saved keyword set for this store.');
        }

        $adsSettings = is_array($set->ads_settings)
            ? $set->ads_settings
            : $adsExport->defaultsForStore($store);

        $csv = $adsExport->toCsv($set->result, $adsSettings, $store);
        $filename = $adsExport->downloadFilename($store);

        return response()->streamDownload(
            static function () use ($csv): void {
                echo $csv;
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    public function exportCampaignCsv(Request $request, GoogleAdsKeywordExport $adsExport): StreamedResponse
    {
        $this->authorize('viewAny', Store::class);

        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $store = Store::query()->findOrFail($data['store_id']);
        $this->authorize('view', $store);

        $set = $store->keywordSet;

        if (! $set) {
            abort(404, 'No saved keyword set for this store.');
        }

        $adsSettings = is_array($set->ads_settings)
            ? $adsExport->normalizeSettings($set->ads_settings, $store)
            : $adsExport->defaultsForStore($store);

        $csv = $adsExport->toCampaignCsv($adsSettings);
        $filename = $adsExport->campaignDownloadFilename($store);

        return response()->streamDownload(
            static function () use ($csv): void {
                echo $csv;
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    public function exportAssetsCsv(Request $request, GoogleAdsKeywordExport $adsExport): StreamedResponse
    {
        $this->authorize('viewAny', Store::class);

        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $store = Store::query()->findOrFail($data['store_id']);
        $this->authorize('view', $store);

        $set = $store->keywordSet;

        if (! $set) {
            abort(404, 'No saved keyword set for this store.');
        }

        $adsSettings = is_array($set->ads_settings)
            ? $adsExport->normalizeSettings($set->ads_settings, $store)
            : $adsExport->defaultsForStore($store);

        $csv = $adsExport->toAssetsCsv($adsSettings['campaign_name']);
        $filename = $adsExport->assetsDownloadFilename($store);

        return response()->streamDownload(
            static function () use ($csv): void {
                echo $csv;
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    public function exportTargetingCsv(Request $request, GoogleAdsKeywordExport $adsExport): StreamedResponse
    {
        $this->authorize('viewAny', Store::class);

        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $store = Store::query()->findOrFail($data['store_id']);
        $this->authorize('view', $store);

        $set = $store->keywordSet;

        if (! $set) {
            abort(404, 'No saved keyword set for this store.');
        }

        $adsSettings = is_array($set->ads_settings)
            ? $adsExport->normalizeSettings($set->ads_settings, $store)
            : $adsExport->defaultsForStore($store);

        $csv = $adsExport->toTargetingCsv($adsSettings, $store);
        $filename = $adsExport->targetingDownloadFilename($store);

        return response()->streamDownload(
            static function () use ($csv): void {
                echo $csv;
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    /**
     * @return array{store: Store, data: array<string, mixed>}
     */
    private function resolveSavedStandardCampaign(Request $request, GoogleAdsStandardCampaign $standardCampaign): array
    {
        $this->authorize('viewAny', Store::class);

        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $store = Store::query()->findOrFail($data['store_id']);
        $this->authorize('view', $store);

        $set = $store->keywordSet;
        $campaign = is_array($set?->ads_settings['standard_campaign'] ?? null)
            ? $set->ads_settings['standard_campaign']
            : null;

        if (! is_array($campaign) || ($campaign['groups'] ?? null) === null) {
            abort(404, 'No saved standard campaign for this store. Generate it first.');
        }

        return ['store' => $store, 'data' => $campaign];
    }

    /**
     * @return array<string, list<string>>
     */
    private function adsSettingsRules(): array
    {
        $matchTypes = implode(',', GoogleAdsKeywordExport::MATCH_TYPES);
        $modes = implode(',', GoogleAdsKeywordExport::AD_GROUP_MODES);
        $statuses = implode(',', GoogleAdsKeywordExport::STATUSES);

        return [
            'campaign_name' => ['nullable', 'string', 'max:120'],
            'ad_group_mode' => ['nullable', 'string', 'in:'.$modes],
            'ad_group_name' => ['nullable', 'string', 'max:120'],
            'brand_ad_group_suffix' => ['nullable', 'string', 'max:80'],
            'match_type' => ['nullable', 'string', 'in:'.$matchTypes],
            'all_match_types' => ['nullable', 'boolean'],
            'keyword_status' => ['nullable', 'string', 'in:'.$statuses],
            'budget' => ['required', 'string', 'max:20'],
            'max_cpc' => ['nullable', 'string', 'max:20'],
            'max_cpc_currency' => ['nullable', 'string', 'in:'.implode(',', GoogleAdsKeywordExport::CPC_CURRENCIES)],
            'final_url' => ['nullable', 'url', 'max:500'],
            'target_locations' => ['nullable', 'array'],
            'target_locations.*' => ['string', Rule::in(GoogleAdsTargetingCatalog::LOCATIONS)],
            'excluded_locations' => ['nullable', 'array'],
            'excluded_locations.*' => ['string', Rule::in(GoogleAdsTargetingCatalog::LOCATIONS)],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', Rule::in(GoogleAdsTargetingCatalog::LANGUAGES)],
            'network_search' => ['nullable', 'boolean'],
            'network_search_partners' => ['nullable', 'boolean'],
            'network_display' => ['nullable', 'boolean'],
            'targeting_status' => ['nullable', 'string', 'in:'.$statuses],
        ];
    }

    /**
     * @param  list<string|null>  $raw
     * @return list<string>
     */
    private function normalizeProducts(array $raw): array
    {
        return collect($raw)
            ->map(fn ($p) => is_string($p) ? trim($p) : '')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     products: list<string>,
     *     result: array<string, mixed>,
     *     brandLabel: string,
     *     savedAt: string,
     *     adsSettings: array<string, mixed>
     * }|null
     */
    private function hydrateFromSaved(Store $store, KeywordGenerationEngine $engine, GoogleAdsKeywordExport $adsExport): ?array
    {
        $set = $store->keywordSet;

        if (! $set) {
            return null;
        }

        $products = is_array($set->products) ? array_values($set->products) : [];
        $rawAds = is_array($set->ads_settings) ? $set->ads_settings : [];
        $adsSettings = $rawAds !== []
            ? $adsExport->normalizeSettings($rawAds, $store)
            : $adsExport->defaultsForStore($store);

        // normalizeSettings only keeps export fields — keep generated campaign payload.
        if (is_array($rawAds['standard_campaign'] ?? null)) {
            $adsSettings['standard_campaign'] = $rawAds['standard_campaign'];
        }
        if (isset($rawAds['discount_percent'])) {
            $adsSettings['discount_percent'] = (int) $rawAds['discount_percent'];
        }

        return [
            'products' => $products === [] ? [''] : $products,
            'result' => is_array($set->result) ? $set->result : $engine->generate($store->name, $products),
            'brandLabel' => $store->name,
            'savedAt' => $set->updated_at?->format('M j, Y g:i A') ?? '',
            'adsSettings' => $adsSettings,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Store>
     */
    private function storesForUser()
    {
        $query = Store::query()->orderBy('name');

        if (! auth()->user()->isAdmin()) {
            $query->ownedBy(auth()->id());
        }

        return $query->get(['id', 'name', 'slug', 'website', 'affiliate_url']);
    }
}
