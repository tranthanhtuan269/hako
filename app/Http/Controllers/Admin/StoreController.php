<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ValidatesStoreInput;
use App\Http\Controllers\Concerns\SyncsStoreCouponDisplay;
use App\Http\Controllers\Concerns\SyncsStoresCatalogDisplay;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Store;
use App\Support\ClickStatsPeriod;
use App\Support\PublicImage;
use App\Support\StoreQuerySort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoreController extends Controller
{
    use ValidatesStoreInput;
    use SyncsStoreCouponDisplay;
    use SyncsStoresCatalogDisplay;

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $period = ClickStatsPeriod::fromRequest($request);

        $query = Store::with('category');

        if ($q !== '') {
            $query->where('name', 'like', '%'.$q.'%');
        }

        $period->applyDayMonthYearSums($query);

        $sorted = StoreQuerySort::apply($query, $request);
        $stores = $sorted['query']->get();
        $sort = $sorted['sort'];
        $dir = $sorted['dir'];
        $statsDate = $period->day;

        return view('admin.stores.index', compact('stores', 'sort', 'dir', 'q', 'statsDate', 'period'));
    }

    public function export(Request $request): StreamedResponse
    {
        $q = trim((string) $request->query('q', ''));

        $query = Store::with('category');

        if ($q !== '') {
            $query->where('name', 'like', '%'.$q.'%');
        }

        $stores = StoreQuerySort::apply($query, $request)['query']->get();
        $filename = 'store-links-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(static function () use ($stores): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Name', 'Store URL', 'Website', 'Affiliate URL', 'Category', 'Status']);

            foreach ($stores as $store) {
                fputcsv($out, [
                    $store->name,
                    route('stores.show', $store->slug),
                    (string) ($store->website ?? ''),
                    (string) ($store->affiliate_url ?? ''),
                    $store->category?->name ?? '',
                    $store->is_active ? 'Active' : 'Inactive',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function catalogDisplay(): View
    {
        return view('admin.stores.catalog-display', [
            'stores' => $this->storesForCatalogDisplayForm(),
            'storesPageLimit' => $this->storesPageLimit(),
        ]);
    }

    public function updateCatalogDisplay(Request $request): RedirectResponse
    {
        $this->syncStoresCatalogDisplay($request);

        return redirect()
            ->route('admin.stores.catalog-display')
            ->with('success', 'Stores page display settings saved.');
    }

    public function updateSortOrder(Request $request)
    {
        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'exists:stores,id'],
        ]);

        $this->applyStoresCatalogSortOrder($data['order']);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()
            ->route('admin.stores.index')
            ->with('success', 'Stores page display order saved.');
    }

    public function create(): View
    {
        return view('admin.stores.form', [
            'store' => new Store(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedStore($request);
        $data['logo'] = $this->resolveLogo($request, $data['logo'] ?? null);
        $data['user_id'] = auth()->id();

        $store = Store::create($data);
        $store->ensureLogoStored($request->input('logo'));

        return redirect()->route('admin.stores.index')->with('success', 'Store created successfully.');
    }

    public function edit(Store $store): View
    {
        return view('admin.stores.form', [
            'store' => $store,
            'categories' => Category::orderBy('name')->get(),
            'storeCoupons' => $this->storeCouponsForDisplayForm($store),
        ]);
    }

    public function update(Request $request, Store $store): RedirectResponse
    {
        $data = $this->validatedStore($request, $store);
        $data['logo'] = $this->resolveLogo($request, $request->input('logo'), $store->logo, $store);

        $store->update($data);
        $store->ensureLogoStored($request->input('logo'));
        $this->syncStoreCouponDisplay($store, $request);

        return redirect()->route('admin.stores.index')->with('success', 'Store updated successfully.');
    }

    public function destroy(Store $store): RedirectResponse
    {
        PublicImage::delete($store->logo);
        $store->delete();

        return redirect()->route('admin.stores.index')->with('success', 'Store deleted successfully.');
    }

    public function toggleHomePin(Store $store): RedirectResponse
    {
        if ($store->is_pinned_home) {
            $store->update([
                'is_pinned_home' => false,
                'home_pin_sort_order' => 0,
            ]);

            return back()->with('success', "{$store->name} unpinned from homepage.");
        }

        $nextOrder = (int) Store::query()->pinnedHome()->max('home_pin_sort_order') + 1;

        $store->update([
            'is_pinned_home' => true,
            'home_pin_sort_order' => $nextOrder,
        ]);

        return back()->with('success', "{$store->name} pinned to homepage.");
    }

    public function coupons(Store $store): JsonResponse
    {
        $coupons = $this->storeCouponsForDisplayForm($store)->map(fn (Coupon $coupon) => [
            'id' => $coupon->id,
            'title' => $coupon->title,
            'description' => $coupon->description,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'type_label' => $coupon->typeLabel(),
            'is_active' => (bool) $coupon->is_active,
            'is_featured' => (bool) $coupon->is_featured,
            'show_on_store' => (bool) $coupon->show_on_store,
            'store_sort_order' => (int) $coupon->store_sort_order,
            'expires_at' => $coupon->expires_at?->format('Y-m-d\TH:i'),
            'is_expired' => $coupon->isExpired(),
            'edit_url' => route('admin.coupons.edit', $coupon),
            'update_url' => route('admin.stores.coupons.update', [$store, $coupon]),
        ]);

        return response()->json([
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
            ],
            'sort_url' => route('admin.stores.coupons.sort-order', $store),
            'create_url' => route('admin.coupons.create', ['store_id' => $store->id]),
            'coupons' => $coupons,
        ]);
    }

    public function updateCouponsSortOrder(Request $request, Store $store)
    {
        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'exists:coupons,id'],
        ]);

        $orderedIds = collect($data['order'])
            ->map(fn ($id) => (int) $id)
            ->intersect($store->coupons()->pluck('id'))
            ->values()
            ->all();

        $count = count($orderedIds);

        foreach ($orderedIds as $index => $id) {
            Coupon::whereKey($id)->where('store_id', $store->id)->update([
                'store_sort_order' => max(1, $count - $index),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()
            ->route('admin.stores.index')
            ->with('success', 'Store coupon order saved.');
    }

    public function updateCoupon(Request $request, Store $store, Coupon $coupon): JsonResponse
    {
        abort_unless((int) $coupon->store_id === (int) $store->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'max:100'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'show_on_store' => ['boolean'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $data['description'] = filled($data['description'] ?? null) ? $data['description'] : null;
        $data['code'] = filled($data['code'] ?? null) ? trim($data['code']) : null;
        $data['type'] = filled($data['code']) ? 'coupon' : 'discount';
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');
        $data['show_on_store'] = $request->boolean('show_on_store');
        $data['expires_at'] = filled($data['expires_at'] ?? null) ? $data['expires_at'] : null;

        $coupon->update($data);

        return response()->json([
            'ok' => true,
            'coupon' => [
                'id' => $coupon->id,
                'title' => $coupon->title,
                'description' => $coupon->description,
                'code' => $coupon->code,
                'type' => $coupon->type,
                'type_label' => $coupon->typeLabel(),
                'is_active' => (bool) $coupon->is_active,
                'is_featured' => (bool) $coupon->is_featured,
                'show_on_store' => (bool) $coupon->show_on_store,
                'expires_at' => $coupon->expires_at?->format('Y-m-d\TH:i'),
                'is_expired' => $coupon->isExpired(),
            ],
        ]);
    }

    private function resolveLogo(Request $request, ?string $logoUrl, ?string $existing = null, ?Store $store = null): ?string
    {
        $userId = $store?->user_id ?? auth()->id() ?? 'admin';

        if ($request->hasFile('logo_file')) {
            PublicImage::delete($existing);

            return PublicImage::storeForUser($request->file('logo_file'), $userId);
        }

        if (filled($logoUrl)) {
            if ($existing && PublicImage::isStored($existing) && $logoUrl !== $existing) {
                PublicImage::delete($existing);
            }

            $stored = PublicImage::ingestRemote($logoUrl, "stores/{$userId}/logos");

            if ($stored) {
                return $stored;
            }
        }

        if ($existing && PublicImage::isValidImage($existing)) {
            return $existing;
        }

        return null;
    }
}
