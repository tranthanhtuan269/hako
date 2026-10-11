<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Post;
use App\Models\Store;
use App\Support\ThemeManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $categories = Category::active()->withCount(['stores' => fn ($q) => $q->active()])->orderByDesc('stores_count')->orderBy('sort_order')->take(18)->get();
        $latestPosts = Post::homeFeaturedQuery(6)->get();

        $stats = [
            'coupons' => Coupon::valid()->count(),
            'stores' => Store::active()->count(),
            'categories' => Category::active()->count(),
        ];

        $themeView = 'themes.'.ThemeManager::current().'.home';
        $useThemeHome = view()->exists($themeView);

        if (! $useThemeHome) {
            $stores = Store::homeFeaturedQuery(12)->get();

            return view('home', compact('categories', 'stores', 'latestPosts', 'stats'));
        }

        $storeCoupons = fn ($q) => $q->valid()
            ->where('show_on_store', true)
            ->select(['id', 'store_id', 'title', 'type', 'discount_type', 'discount_value']);
        $storeCounts = ['coupons as active_coupons_count' => fn ($q) => $q->valid()];

        $stores = Store::homeFeaturedQuery(14)
            ->with(['coupons' => $storeCoupons])
            ->withCount($storeCounts)
            ->get();

        $storeGrid = Store::homeFeaturedQuery(12)
            ->with(['coupons' => $storeCoupons])
            ->withCount($storeCounts)
            ->paginate(12, ['*'], 'sp')
            ->withQueryString()
            ->fragment('top-stores');

        // 1.2 Featured Coupons & Deals:
        // Only pinned stores (is_pinned_home = true)
        // Each store only displays its single highest deal or coupon
        $pinnedStores = Store::query()
            ->active()
            ->pinnedHome()
            ->orderByDesc('home_pin_sort_order')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with(['coupons' => fn ($q) => $q->valid()->orderByDesc('is_featured')->latest()])
            ->get();

        $selectedCoupons = $pinnedStores->map(function (Store $store) {
            return $store->coupons
                ->sortByDesc(fn (Coupon $c) => $c->discountScore())
                ->first();
        })->filter()->sortByDesc(function (Coupon $c) {
            return [
                $c->store?->home_pin_sort_order ?? 0,
                $c->discountScore(),
            ];
        })->values();

        $page = LengthAwarePaginator::resolveCurrentPage('cp');
        $perPage = 12;
        $currentPageItems = $selectedCoupons->slice(($page - 1) * $perPage, $perPage)->values();

        $featuredCoupons = new LengthAwarePaginator(
            $currentPageItems,
            $selectedCoupons->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'cp',
            ]
        );
        $featuredCoupons->withQueryString()->fragment('featured-coupons');

        return view($themeView, compact(
            'categories',
            'stores',
            'storeGrid',
            'featuredCoupons',
            'latestPosts',
            'stats',
        ));
    }

    public function search(Request $request): View
    {
        $q = trim($request->get('q', ''));

        $coupons = Coupon::with(['store.category'])
            ->valid()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhereHas('store', fn ($s) => $s->where('name', 'like', "%{$q}%"));
                });
            })
            ->latest()
            ->paginate(16)
            ->withQueryString();

        $posts = $q
            ? Post::published()
                ->with('user')
                ->where(function ($query) use ($q) {
                    $query->where('title', 'like', "%{$q}%")
                        ->orWhere('excerpt', 'like', "%{$q}%")
                        ->orWhere('content', 'like', "%{$q}%");
                })
                ->orderByDesc('published_at')
                ->take(12)
                ->get()
            : collect();

        return view('search', compact('coupons', 'posts', 'q'));
    }
}
