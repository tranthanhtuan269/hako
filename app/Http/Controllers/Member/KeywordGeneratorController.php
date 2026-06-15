<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreKeywordSet;
use App\Support\KeywordGenerationEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KeywordGeneratorController extends Controller
{
    public function create(KeywordGenerationEngine $engine): View
    {
        $this->authorize('viewAny', Store::class);

        $selectedStoreId = (int) old('store_id', 0);
        $saved = null;
        $products = [''];
        $result = null;
        $brandLabel = null;
        $savedAt = null;

        if ($selectedStoreId > 0) {
            $store = Store::query()->find($selectedStoreId);

            if ($store && auth()->user()->can('view', $store)) {
                $saved = $this->hydrateFromSaved($store, $engine);
                $products = $saved['products'];
                $result = $saved['result'];
                $brandLabel = $saved['brandLabel'];
                $savedAt = $saved['savedAt'];
            }
        }

        return view('member.keywords.form', [
            'stores' => $this->storesForUser(),
            'engine' => $engine,
            'selectedStoreId' => $selectedStoreId ?: null,
            'products' => $products,
            'result' => $result,
            'brandLabel' => $brandLabel,
            'savedAt' => $savedAt,
            'fromSaved' => $saved !== null && $savedAt !== null,
        ]);
    }

    public function load(Request $request, KeywordGenerationEngine $engine): JsonResponse
    {
        $this->authorize('viewAny', Store::class);

        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $store = Store::query()->findOrFail($data['store_id']);
        $this->authorize('view', $store);

        $saved = $this->hydrateFromSaved($store, $engine);

        if (! $saved) {
            return response()->json([
                'ok' => true,
                'found' => false,
                'products' => [''],
            ]);
        }

        return response()->json([
            'ok' => true,
            'found' => true,
            'store_name' => $saved['brandLabel'],
            'products' => $saved['products'],
            'result' => $saved['result'],
            'saved_at' => $saved['savedAt'],
            'results_html' => view('member.keywords.partials.results', [
                'result' => $saved['result'],
                'brandLabel' => $saved['brandLabel'],
                'engine' => $engine,
                'savedAt' => $saved['savedAt'],
                'fromSaved' => true,
            ])->render(),
        ]);
    }

    public function generate(Request $request, KeywordGenerationEngine $engine): View
    {
        $this->authorize('viewAny', Store::class);

        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'products' => ['nullable', 'array'],
            'products.*' => ['nullable', 'string', 'max:120'],
        ]);

        $store = Store::query()->findOrFail($data['store_id']);
        $this->authorize('view', $store);

        $products = $this->normalizeProducts($data['products'] ?? []);
        $result = $engine->generate($store->name, $products);

        StoreKeywordSet::updateOrCreate(
            ['store_id' => $store->id],
            [
                'user_id' => auth()->id(),
                'products' => $products,
                'result' => $result,
            ]
        );

        return view('member.keywords.form', [
            'stores' => $this->storesForUser(),
            'engine' => $engine,
            'selectedStoreId' => $store->id,
            'products' => $products === [] ? [''] : $products,
            'result' => $result,
            'brandLabel' => $store->name,
            'savedAt' => now()->format('M j, Y g:i A'),
            'fromSaved' => false,
        ]);
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
     * @return array{products: list<string>, result: array<string, mixed>, brandLabel: string, savedAt: string}|null
     */
    private function hydrateFromSaved(Store $store, KeywordGenerationEngine $engine): ?array
    {
        $set = $store->keywordSet;

        if (! $set) {
            return null;
        }

        $products = is_array($set->products) ? array_values($set->products) : [];

        return [
            'products' => $products === [] ? [''] : $products,
            'result' => is_array($set->result) ? $set->result : $engine->generate($store->name, $products),
            'brandLabel' => $store->name,
            'savedAt' => $set->updated_at?->format('M j, Y g:i A') ?? '',
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

        return $query->get(['id', 'name']);
    }
}
