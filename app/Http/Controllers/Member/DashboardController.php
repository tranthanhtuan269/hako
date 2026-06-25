<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Post;
use App\Models\Store;
use App\Support\CouponQuerySort;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $userId = auth()->id();

        $stats = [
            'coupons' => Coupon::ownedBy($userId)->count(),
            'active_coupons' => Coupon::ownedBy($userId)->valid()->count(),
            'stores' => Store::ownedBy($userId)->count(),
            'published_posts' => Post::ownedBy($userId)->where('is_published', true)->count(),
            'clicks' => (int) Coupon::ownedBy($userId)->sum('click_count'),
            'store_views' => (int) Store::ownedBy($userId)->sum('view_count'),
        ];

        $sorted = CouponQuerySort::apply(
            Coupon::with('store')->ownedBy($userId),
            $request
        );
        $recentCoupons = $sorted['query']->take(10)->get();
        $sort = $sorted['sort'];
        $dir = $sorted['dir'];

        return view('member.dashboard', compact('stats', 'recentCoupons', 'sort', 'dir'));
    }
}
