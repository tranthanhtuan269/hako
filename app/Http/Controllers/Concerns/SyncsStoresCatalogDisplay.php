<?php

namespace App\Http\Controllers\Concerns;

use App\Models\SiteSetting;
use App\Models\Store;
use Illuminate\Http\Request;

trait SyncsStoresCatalogDisplay
{
    protected function validatedStoresCatalogDisplay(Request $request): array
    {
        return $request->validate([
            'stores_page_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'show_on_stores' => ['nullable', 'array'],
            'show_on_stores.*' => ['integer', 'exists:stores,id'],
            'stores_list_sort_order' => ['nullable', 'array'],
            'stores_list_sort_order.*' => ['integer', 'min:0', 'max:9999'],
        ]);
    }

    protected function syncStoresCatalogDisplay(Request $request): void
    {
        $data = $this->validatedStoresCatalogDisplay($request);

        SiteSetting::set('stores_page_limit', (string) (int) $data['stores_page_limit']);

        $visibleIds = collect($data['show_on_stores'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $sortOrders = $data['stores_list_sort_order'] ?? [];

        Store::query()->each(function (Store $store) use ($visibleIds, $sortOrders) {
            $store->update([
                'show_on_stores' => in_array($store->id, $visibleIds, true),
                'stores_list_sort_order' => (int) ($sortOrders[$store->id] ?? $sortOrders[(string) $store->id] ?? 0),
            ]);
        });
    }

    protected function storesForCatalogDisplayForm()
    {
        return Store::with('category')
            ->withCount(['coupons' => fn ($q) => $q->valid()])
            ->orderByDesc('stores_list_sort_order')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function storesPageLimit(): int
    {
        return max(1, (int) SiteSetting::get('stores_page_limit', 24));
    }

    protected function applyStoresCatalogSortOrder(array $orderedIds): void
    {
        $count = count($orderedIds);

        foreach ($orderedIds as $index => $id) {
            Store::whereKey((int) $id)->update([
                'stores_list_sort_order' => max(1, $count - $index),
            ]);
        }
    }
}
