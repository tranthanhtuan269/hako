<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Post;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $categories = Category::active()->orderBy('sort_order')->take(18)->get();
        $stores = Store::active()->orderBy('sort_order')->take(12)->get();

        $latestPosts = Post::published()
            ->with('user')
            ->orderByDesc('published_at')
            ->take(6)
            ->get();

        $stats = [
            'coupons' => Coupon::valid()->count(),
            'stores' => Store::active()->count(),
            'categories' => Category::active()->count(),
        ];

        return view('home', compact(
            'categories',
            'stores',
            'latestPosts',
            'stats'
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

        return view('search', compact('coupons', 'q'));
    }
}
