<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Post;
use App\Models\Store;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
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

        $recentCoupons = Coupon::with('store')
            ->ownedBy($userId)
            ->latest()
            ->take(10)
            ->get();

        return view('member.dashboard', compact('stats', 'recentCoupons'));
    }
}
