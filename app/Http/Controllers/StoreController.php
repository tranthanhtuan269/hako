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
    public function index(): View
    {
        $limit = max(1, (int) SiteSetting::get('stores_page_limit', 24));

        $stores = Store::publicCatalogQuery()
            ->with('category')
            ->withCount(['coupons' => fn ($q) => $q->valid()])
            ->paginate($limit);

        return view('stores.index', compact('stores'));
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

        $viewName = 'stores.show';
        $themeView = 'themes.'.ThemeManager::current().'.store-show';
        if (view()->exists($themeView)) {
            $viewName = $themeView;
        }

        return view($viewName, compact(
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
