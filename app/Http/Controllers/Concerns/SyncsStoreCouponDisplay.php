<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Store;
use Illuminate\Http\Request;

trait SyncsStoreCouponDisplay
{
    protected function validatedStoreCouponDisplay(Request $request): array
    {
        return $request->validate([
            'store_coupon_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'show_on_store' => ['nullable', 'array'],
            'show_on_store.*' => ['integer', 'exists:coupons,id'],
            'store_coupon_sort' => ['nullable', 'array'],
            'store_coupon_sort.*' => ['integer', 'min:0', 'max:9999'],
        ]);
    }

    protected function syncStoreCouponDisplay(Store $store, Request $request): void
    {
        if (! $store->exists) {
            return;
        }

        $data = $this->validatedStoreCouponDisplay($request);
        $visibleIds = collect($data['show_on_store'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->intersect($store->coupons()->pluck('id'))
            ->unique()
            ->values()
            ->all();
        $sortOrders = $data['store_coupon_sort'] ?? [];

        $store->update([
            'store_coupon_limit' => (int) $data['store_coupon_limit'],
        ]);

        $store->coupons()->each(function ($coupon) use ($visibleIds, $sortOrders) {
            $coupon->update([
                'show_on_store' => in_array($coupon->id, $visibleIds, true),
                'store_sort_order' => (int) ($sortOrders[$coupon->id] ?? $sortOrders[(string) $coupon->id] ?? 0),
            ]);
        });
    }

    protected function storeCouponsForDisplayForm(Store $store)
    {
        if (! $store->exists) {
            return collect();
        }

        return $store->coupons()
            ->orderByDesc('store_sort_order')
            ->orderByDesc('is_featured')
            ->latest()
            ->get();
    }
}
