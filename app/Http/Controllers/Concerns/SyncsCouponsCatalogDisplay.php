<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Coupon;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

trait SyncsCouponsCatalogDisplay
{
    protected function validatedCouponsCatalogDisplay(Request $request): array
    {
        return $request->validate([
            'coupons_page_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'show_on_coupons' => ['nullable', 'array'],
            'show_on_coupons.*' => ['integer', 'exists:coupons,id'],
            'coupons_sort_order' => ['nullable', 'array'],
            'coupons_sort_order.*' => ['integer', 'min:0', 'max:9999'],
        ]);
    }

    protected function syncCouponsCatalogDisplay(Request $request): void
    {
        $data = $this->validatedCouponsCatalogDisplay($request);

        SiteSetting::set('coupons_page_limit', (string) (int) $data['coupons_page_limit']);

        $visibleIds = collect($data['show_on_coupons'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $sortOrders = $data['coupons_sort_order'] ?? [];

        Coupon::query()->each(function (Coupon $coupon) use ($visibleIds, $sortOrders) {
            $coupon->update([
                'show_on_coupons' => in_array($coupon->id, $visibleIds, true),
                'coupons_sort_order' => (int) ($sortOrders[$coupon->id] ?? $sortOrders[(string) $coupon->id] ?? 0),
            ]);
        });
    }

    protected function couponsForCatalogDisplayForm()
    {
        return Coupon::with('store')
            ->orderByDesc('coupons_sort_order')
            ->orderByDesc('is_featured')
            ->latest()
            ->get();
    }

    protected function couponsPageLimit(): int
    {
        return max(1, (int) SiteSetting::get('coupons_page_limit', 16));
    }

    protected function applyCouponsCatalogSortOrder(array $orderedIds): void
    {
        $count = count($orderedIds);

        foreach ($orderedIds as $index => $id) {
            Coupon::whereKey((int) $id)->update([
                'coupons_sort_order' => max(1, $count - $index),
            ]);
        }
    }
}
