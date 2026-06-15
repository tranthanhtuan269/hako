<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AffiliateOrder;
use App\Models\AffiliatePayoutRequest;
use App\Models\AffiliateVisitLog;
use App\Models\User;
use App\Support\AffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateController extends Controller
{
    public function index(AffiliateService $affiliate): View
    {
        $user = auth()->user();
        $affiliate->ensureReferralCode($user);

        $stats = [
            'balance' => (float) $user->affiliate_balance,
            'referrals' => User::query()->where('referred_by_user_id', $user->id)->count(),
            'visits' => AffiliateVisitLog::query()->where('referrer_user_id', $user->id)->count(),
            'orders' => AffiliateOrder::query()->where('referrer_user_id', $user->id)->count(),
            'paid_orders' => AffiliateOrder::query()->where('referrer_user_id', $user->id)->where('status', AffiliateOrder::STATUS_PAID)->count(),
            'total_commission' => (float) AffiliateOrder::query()
                ->where('referrer_user_id', $user->id)
                ->where('commission_credited', true)
                ->sum('commission_amount'),
            'pending_payouts' => AffiliatePayoutRequest::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [AffiliatePayoutRequest::STATUS_PENDING, AffiliatePayoutRequest::STATUS_APPROVED])
                ->count(),
        ];

        $recentOrders = AffiliateOrder::query()
            ->where('referrer_user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('member.affiliate.index', [
            'user' => $user,
            'referralUrl' => $affiliate->referralUrl($user),
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'currency' => config('affiliate.currency', 'USD'),
            'minPayout' => (float) config('affiliate.min_payout_amount', 50),
        ]);
    }

    public function orders(): View
    {
        $orders = AffiliateOrder::query()
            ->where('referrer_user_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view('member.affiliate.orders', [
            'orders' => $orders,
            'currency' => config('affiliate.currency', 'USD'),
        ]);
    }

    public function payouts(): View
    {
        $payouts = AffiliatePayoutRequest::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view('member.affiliate.payouts', [
            'payouts' => $payouts,
            'balance' => (float) auth()->user()->affiliate_balance,
            'currency' => config('affiliate.currency', 'USD'),
            'minPayout' => (float) config('affiliate.min_payout_amount', 50),
        ]);
    }

    public function storePayout(Request $request, AffiliateService $affiliate): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_details' => ['required', 'string', 'max:2000'],
            'member_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $affiliate->createPayoutRequest(auth()->user(), $data);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('member.affiliate.payouts')
            ->with('success', 'Payout request submitted. We will review and process it soon.');
    }
}
