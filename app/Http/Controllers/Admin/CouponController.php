<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\SyncsCouponsCatalogDisplay;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Store;
use App\Support\ClickStatsPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CouponController extends Controller
{
    use SyncsCouponsCatalogDisplay;

    public function index(Request $request): View
    {
        $title = trim((string) $request->query('title', ''));
        $storeId = $request->integer('store_id') ?: null;
        $period = ClickStatsPeriod::fromRequest($request);

        $query = Coupon::with(['store.category'])
            ->filterSearch($title !== '' ? $title : null, $storeId);

        $period->applyDayMonthYearSums($query);

        $coupons = $query
            ->orderByDesc('coupons_sort_order')
            ->orderByDesc('created_at')
            ->get();

        $stores = Store::orderBy('name')->get(['id', 'name']);
        $statsDate = $period->day;

        return view('admin.coupons.index', compact('coupons', 'stores', 'statsDate', 'period'));
    }

    public function updateSortOrder(Request $request)
    {
        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'exists:coupons,id'],
        ]);

        $this->applyCouponsCatalogSortOrder($data['order']);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon display order saved for /coupons, store pages, and popup.');
    }

    public function catalogDisplay(): View
    {
        return view('admin.coupons.catalog-display', [
            'coupons' => $this->couponsForCatalogDisplayForm(),
            'couponsPageLimit' => $this->couponsPageLimit(),
        ]);
    }

    public function updateCatalogDisplay(Request $request): RedirectResponse
    {
        $this->syncCouponsCatalogDisplay($request);

        return redirect()
            ->route('admin.coupons.catalog-display')
            ->with('success', 'Coupons page display settings saved.');
    }

    public function create(Request $request): View
    {
        $stores = Store::orderBy('name')->get();
        $coupon = new Coupon();

        if ($request->filled('store_id')) {
            $coupon->store_id = $request->integer('store_id') ?: null;
        }

        return view('admin.coupons.form', [
            'coupon' => $coupon,
            'stores' => $stores,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['user_id'] = $this->resolveOwnerId((int) $data['store_id']);

        Coupon::create($data);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon): View
    {
        $stores = Store::orderBy('name')->get();

        return view('admin.coupons.form', compact('coupon', 'stores'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $data = $this->validated($request);
        $coupon->update($data);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted successfully.');
    }

    /** Coupons belong to the store owner so they stay visible in that member's dashboard. */
    private function resolveOwnerId(int $storeId): ?int
    {
        return Store::whereKey($storeId)->value('user_id') ?? auth()->id();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'max:100'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'show_on_coupons' => ['boolean'],
            'coupons_sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $data['code'] = filled($data['code'] ?? null) ? trim($data['code']) : null;
        $data['type'] = filled($data['code']) ? 'coupon' : 'discount';
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['show_on_coupons'] = $request->boolean('show_on_coupons', true);
        $data['coupons_sort_order'] = (int) ($data['coupons_sort_order'] ?? 0);
        $data['expires_at'] = filled($data['expires_at'] ?? null) ? $data['expires_at'] : null;

        return $data;
    }

    private function uniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $i = 1;

        while (Coupon::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $i++;
        }

        return $slug;
    }
}
