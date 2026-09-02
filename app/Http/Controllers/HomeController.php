<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Post;
use App\Models\Store;
use App\Support\ThemeManager;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $categories = Category::active()->orderBy('sort_order')->take(18)->get();
        $latestPosts = Post::homeFeaturedQuery(6)->get();

        $stats = [
            'coupons' => Coupon::valid()->count(),
            'stores' => Store::active()->count(),
            'categories' => Category::active()->count(),
        ];

        $themeView = 'themes.'.ThemeManager::current().'.home';
        $useThemeHome = view()->exists($themeView);

        $storeQuery = Store::homeFeaturedQuery($useThemeHome ? 14 : 12);
        if ($useThemeHome) {
            $storeQuery->withCount([
                'coupons as active_coupons_count' => fn ($q) => $q->valid(),
            ]);
        }
        $stores = $storeQuery->get();

        $data = compact('categories', 'stores', 'latestPosts', 'stats');

        if ($useThemeHome) {
            $data['featuredCoupons'] = Coupon::with('store')
                ->valid()
                ->orderByDesc('is_featured')
                ->latest()
                ->take(12)
                ->get();

            return view($themeView, $data);
        }

        return view('home', $data);
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
