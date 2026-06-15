<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->get('type');

        $coupons = Coupon::with(['store.category'])
            ->valid()
            ->ofType($type)
            ->latest()
            ->paginate(16)
            ->withQueryString();

        return view('coupons.index', compact('coupons', 'type'));
    }

    public function show(string $slug): View
    {
        $coupon = Coupon::with(['store.category'])
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        $related = Coupon::with(['store'])
            ->valid()
            ->where('store_id', $coupon->store_id)
            ->where('id', '!=', $coupon->id)
            ->take(6)
            ->get();

        return view('coupons.show', compact('coupon', 'related'));
    }

    public function reveal(string $slug): JsonResponse
    {
        $coupon = Coupon::where('slug', $slug)->active()->firstOrFail();
        $coupon->incrementClicks();

        return response()->json([
            'code' => $coupon->code,
            'affiliate_url' => $coupon->affiliate_url ?: $coupon->store?->shopUrl(),
            'title' => $coupon->title,
        ]);
    }

    public function go(string $slug): RedirectResponse
    {
        $coupon = Coupon::where('slug', $slug)->active()->firstOrFail();
        $coupon->incrementClicks();

        $url = $coupon->affiliate_url ?: $coupon->store?->shopUrl();

        if (! $url) {
            return back()->with('error', 'No shopping link is available for this offer yet.');
        }

        return redirect()->away($url);
    }
}
