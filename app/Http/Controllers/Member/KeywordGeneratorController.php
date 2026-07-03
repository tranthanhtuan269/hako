<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreKeywordSet;
use App\Support\GoogleAdsKeywordExport;
use App\Support\GoogleAdsTargetingCatalog;
use App\Support\KeywordGenerationEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KeywordGeneratorController extends Controller
{
    public function create(KeywordGenerationEngine $engine, GoogleAdsKeywordExport $adsExport): View
    {
        $this->authorize('viewAny', Store::class);

        $selectedStoreId = (int) old('store_id', 0);
        $saved = null;
        $products = [''];
        $result = null;
        $brandLabel = null;
        $savedAt = null;
        $adsSettings = null;
        $selectedStore = null;

        if ($selectedStoreId > 0) {
            $store = Store::query()->find($selectedStoreId);

            if ($store && auth()->user()->can('view', $store)) {
                $selectedStore = $store;
                $saved = $this->hydrateFromSaved($store, $engine, $adsExport);
                $products = $saved['products'];
                $result = $saved['result'];
                $brandLabel = $saved['brandLabel'];
                $savedAt = $saved['savedAt'];
                $adsSettings = $saved['adsSettings'];
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
        ]);
    }

    public function load(Request $request, KeywordGenerationEngine $engine, GoogleAdsKeywordExport $adsExport): JsonResponse
    {
        $this->authorize('viewAny', Store::class);

        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $store = Store::query()->findOrFail($data['store_id']);
        $this->authorize('view', $store);

        $saved = $this->hydrateFromSaved($store, $engine, $adsExport);

        if (! $saved) {
            return response()->json([
                'ok' => true,
                'found' => false,
                'products' => [''],
                'ads_settings' => $adsExport->defaultsForStore($store),
            ]);
        }

        return response()->json([
            'ok' => true,
            'found' => true,
            'store_name' => $saved['brandLabel'],
            'products' => $saved['products'],
            'result' => $saved['result'],
            'saved_at' => $saved['savedAt'],
            'ads_settings' => $saved['adsSettings'],
            'export_csv_url' => route('member.keywords.export-csv', ['store_id' => $store->id]),
            'export_assets_csv_url' => route('member.keywords.export-assets-csv', ['store_id' => $store->id]),
            'export_targeting_csv_url' => route('member.keywords.export-targeting-csv', ['store_id' => $store->id]),
            'results_html' => view('member.keywords.partials.results', [
                'result' => $saved['result'],
                'brandLabel' => $saved['brandLabel'],
                'engine' => $engine,
                'savedAt' => $saved['savedAt'],
                'fromSaved' => true,
                'adsSettings' => $saved['adsSettings'],
                'exportCsvUrl' => route('member.keywords.export-csv', ['store_id' => $store->id]),
                'exportAssetsCsvUrl' => route('member.keywords.export-assets-csv', ['store_id' => $store->id]),
                'exportTargetingCsvUrl' => route('member.keywords.export-targeting-csv', ['store_id' => $store->id]),
            ])->render(),
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
        ]);
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
            'max_cpc' => ['nullable', 'string', 'max:20'],
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
        $adsSettings = is_array($set->ads_settings)
            ? $adsExport->normalizeSettings($set->ads_settings, $store)
            : $adsExport->defaultsForStore($store);

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
