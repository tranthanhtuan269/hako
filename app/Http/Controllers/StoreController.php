<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SiteSetting;
use App\Support\ScrollCouponPopup;
use App\Support\ThemeManager;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function index(Request $request): View
    {
        $limit = max(1, (int) SiteSetting::get('stores_page_limit', 24));
        $q = trim((string) $request->get('q', ''));

        $stores = Store::publicCatalogQuery()
            ->with([
                'category',
                'coupons' => fn ($query) => $query->valid()->where('show_on_store', true)
                    ->select(['id', 'store_id', 'title', 'type', 'discount_type', 'discount_value']),
            ])
            ->withCount(['coupons' => fn ($query) => $query->valid()])
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->paginate($limit)
            ->withQueryString();

        return view(ThemeManager::view('stores.index'), compact('stores', 'q'));
    }

    public function show(Request $request, string $slug): View
    {
        $store = Store::with('category')
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();
        $store->incrementViews();

        $offerType = $request->get('type');
        if (! in_array($offerType, ['coupon', 'discount'], true)) {
            $offerType = null;
        }

        $baseQuery = $store->publicStoreCouponsQuery();
        $codeCount = (clone $baseQuery)->where('type', 'coupon')->count();
        $dealCount = (clone $baseQuery)->where('type', 'discount')->count();
        $bestOffer = $store->bestOfferLabel();

        $coupons = $store->publicStoreCouponsQuery()
            ->when($offerType, fn ($q) => $q->where('type', $offerType))
            ->paginate($store->storeCouponLimit())
            ->withQueryString();

        $similarStores = Store::active()
            ->when($store->category_id, fn ($q) => $q->where('category_id', $store->category_id))
            ->where('id', '!=', $store->id)
            ->with(['coupons' => fn ($q) => $q->valid()->where('show_on_store', true)])
            ->orderBy('sort_order')
            ->take(6)
            ->get();

        $topCategories = Category::active()->orderBy('sort_order')->take(8)->get();

        $scrollPopup = ScrollCouponPopup::forStore($store, openAffiliateOnCopy: true);

        return view(ThemeManager::view('stores.show', 'store-show'), compact(
            'store',
            'coupons',
            'similarStores',
            'topCategories',
            'scrollPopup',
            'codeCount',
            'dealCount',
            'bestOffer',
            'offerType',
        ));
    }
}
