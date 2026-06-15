<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliatePayoutRequest;
use App\Support\AffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliatePayoutController extends Controller
{
    public function index(): View
    {
        $payouts = AffiliatePayoutRequest::query()
            ->with(['user', 'processedBy'])
            ->latest()
            ->paginate(20);

        $pendingCount = AffiliatePayoutRequest::query()
            ->where('status', AffiliatePayoutRequest::STATUS_PENDING)
            ->count();

        return view('admin.affiliate.payouts.index', [
            'payouts' => $payouts,
            'pendingCount' => $pendingCount,
            'currency' => config('affiliate.currency', 'USD'),
        ]);
    }

    public function edit(AffiliatePayoutRequest $payout): View
    {
        $payout->load(['user', 'processedBy']);

        return view('admin.affiliate.payouts.form', [
            'payout' => $payout,
            'currency' => config('affiliate.currency', 'USD'),
        ]);
    }

    public function update(Request $request, AffiliatePayoutRequest $payout, AffiliateService $affiliate): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', AffiliatePayoutRequest::STATUSES)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $affiliate->updatePayoutStatus(
                $payout,
                $data['status'],
                $data['admin_note'] ?? null,
                auth()->user()
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.affiliate.payouts.index')
            ->with('success', 'Payout request updated.');
    }
}
