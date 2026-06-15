<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateOrder;
use App\Models\AffiliatePayoutRequest;
use App\Models\User;
use App\Support\AffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateOrderController extends Controller
{
    public function index(): View
    {
        $orders = AffiliateOrder::query()
            ->with(['referrer', 'referredUser'])
            ->latest()
            ->paginate(20);

        return view('admin.affiliate.orders.index', [
            'orders' => $orders,
            'currency' => config('affiliate.currency', 'USD'),
        ]);
    }

    public function create(): View
    {
        return view('admin.affiliate.orders.form', [
            'order' => new AffiliateOrder([
                'commission_rate' => config('affiliate.default_commission_rate', 10),
                'status' => AffiliateOrder::STATUS_PENDING,
            ]),
            'referrers' => User::query()->orderBy('name')->get(['id', 'name', 'email', 'referral_code']),
            'currency' => config('affiliate.currency', 'USD'),
        ]);
    }

    public function store(Request $request, AffiliateService $affiliate): RedirectResponse
    {
        $data = $this->validated($request);
        $targetStatus = $data['status'];
        unset($data['status']);

        $order = AffiliateOrder::create([
            ...$data,
            'status' => AffiliateOrder::STATUS_PENDING,
            'order_number' => $affiliate->generateOrderNumber(),
            'commission_amount' => 0,
            'commission_credited' => false,
            'created_by_user_id' => auth()->id(),
        ]);

        if ($targetStatus !== AffiliateOrder::STATUS_PENDING) {
            $affiliate->applyOrderStatusChange($order, $targetStatus);
        }

        return redirect()
            ->route('admin.affiliate.orders.index')
            ->with('success', 'Affiliate order logged.');
    }

    public function edit(AffiliateOrder $order): View
    {
        $order->load(['referrer', 'referredUser']);

        return view('admin.affiliate.orders.form', [
            'order' => $order,
            'referrers' => User::query()->orderBy('name')->get(['id', 'name', 'email', 'referral_code']),
            'currency' => config('affiliate.currency', 'USD'),
        ]);
    }

    public function update(Request $request, AffiliateOrder $order, AffiliateService $affiliate): RedirectResponse
    {
        $data = $this->validated($request, $order);
        $newStatus = $data['status'];
        unset($data['status']);

        $order->fill($data);
        $order->save();

        $affiliate->applyOrderStatusChange($order->fresh(), $newStatus);

        return redirect()
            ->route('admin.affiliate.orders.index')
            ->with('success', 'Affiliate order updated.');
    }

    private function validated(Request $request, ?AffiliateOrder $order = null): array
    {
        $data = $request->validate([
            'referrer_user_id' => ['required', 'exists:users,id'],
            'referred_user_id' => ['nullable', 'exists:users,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'in:' . implode(',', AffiliateOrder::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);

        if ($order) {
            $data['order_number'] = $order->order_number;
        }

        return $data;
    }
}
