<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\ThemeManager;
use App\Models\Store;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function index(): View
    {
        $stores = Store::active()
            ->withCount(['coupons' => fn ($q) => $q->valid()])
            ->orderBy('sort_order')
            ->paginate(24);

        return view('stores.index', compact('stores'));
    }

    public function show(string $slug): View
    {
        $store = Store::with('category')
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();
        $store->incrementViews();

        $coupons = $store->coupons()
            ->valid()
            ->latest()
            ->paginate(16);

        $similarStores = Store::active()
            ->when($store->category_id, fn ($q) => $q->where('category_id', $store->category_id))
            ->where('id', '!=', $store->id)
            ->orderBy('sort_order')
            ->take(4)
            ->get();

        $topCategories = Category::active()->orderBy('sort_order')->take(8)->get();

        $viewName = 'stores.show';
        $themeView = 'themes.'.ThemeManager::current().'.store-show';
        if (view()->exists($themeView)) {
            $viewName = $themeView;
        }

        return view($viewName, compact('store', 'coupons', 'similarStores', 'topCategories'));
    }
}
