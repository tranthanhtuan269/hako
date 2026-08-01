<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Post;
use App\Models\Store;
use App\Support\SiteHeroSlides;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $categories = Category::active()->orderBy('sort_order')->take(12)->get();
        $stores = Store::homeFeaturedQuery(18)
            ->withCount(['coupons as active_coupons_count' => function ($query) {
                $query->valid();
            }])
            ->get();
        $trendingStores = Store::homeFeaturedQuery(24)->get();
        if ($trendingStores->count() < 8) {
            $trendingStores = Store::active()->orderByDesc('view_count')->orderBy('name')->take(24)->get();
        }

        $exclusiveCoupons = Coupon::publicCatalogQuery()
            ->with(['store.category'])
            ->take(8)
            ->get();

        $latestPosts = Post::homeFeaturedQuery(6)->get();

        $stats = [
            'coupons' => Coupon::valid()->count(),
            'stores' => Store::active()->count(),
            'categories' => Category::active()->count(),
        ];

        $heroSlides = SiteHeroSlides::forHome();

        return view('home', compact(
            'categories',
            'stores',
            'trendingStores',
            'exclusiveCoupons',
            'latestPosts',
            'stats',
            'heroSlides',
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
