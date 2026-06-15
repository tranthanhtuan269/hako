@extends('layouts.member')

@section('title', 'Referral Program')

@section('content')
<h1 style="margin-bottom:.5rem;">Referral Program</h1>
<p style="color:var(--muted);margin-bottom:1.5rem;">Share your link. When referred users place paid orders, you earn commission.</p>

<div class="stats-grid" style="margin-bottom:1.5rem;">
    <div class="stat-card"><strong>{{ number_format($stats['balance'], 2) }} {{ $currency }}</strong> Available Balance</div>
    <div class="stat-card"><strong>{{ $stats['referrals'] }}</strong> Sign-ups</div>
    <div class="stat-card"><strong>{{ $stats['visits'] }}</strong> Link Clicks</div>
    <div class="stat-card"><strong>{{ $stats['paid_orders'] }}</strong> Paid Orders</div>
    <div class="stat-card"><strong>{{ number_format($stats['total_commission'], 2) }} {{ $currency }}</strong> Total Earned</div>
    <div class="stat-card"><strong>{{ $stats['pending_payouts'] }}</strong> Pending Payouts</div>
</div>

<div class="form-card" style="margin-bottom:1.5rem;">
    <h2>Your Affiliate Link</h2>
    <p class="form-hint" style="margin-bottom:.75rem;">Code: <code>{{ $user->referral_code }}</code></p>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
        <input type="text" id="referral-url" value="{{ $referralUrl }}" readonly style="flex:1;min-width:240px;padding:.6rem .75rem;border:1px solid var(--border);border-radius:6px;">
        <button type="button" class="btn btn-primary" id="copy-referral">Copy Link</button>
    </div>
    <p class="form-hint" style="margin-top:.75rem;">You can also append <code>?ref={{ $user->referral_code }}</code> to any page URL.</p>
</div>

<div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.5rem;">
    <a href="{{ route('member.affiliate.orders') }}" class="btn btn-outline">View Orders</a>
    <a href="{{ route('member.affiliate.payouts') }}" class="btn btn-outline">Payout Requests</a>
    @if($stats['balance'] >= $minPayout)
        <a href="{{ route('member.affiliate.payouts') }}#request-payout" class="btn btn-primary">Request Payout</a>
    @endif
</div>

<h2 style="margin-bottom:1rem;">Recent Orders</h2>
@if($recentOrders->isEmpty())
    <p>No referred orders yet.</p>
@else
<table class="admin-table">
    <thead>
        <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Commission</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($recentOrders as $order)
        <tr>
            <td><code>{{ $order->order_number }}</code></td>
            <td>{{ $order->customer_name ?: ($order->referredUser?->name ?: '—') }}</td>
            <td>{{ number_format($order->amount, 2) }} {{ $currency }}</td>
            <td>{{ $order->commission_credited ? number_format($order->commission_amount, 2) : '—' }} {{ $currency }}</td>
            <td>{{ $order->statusLabel() }}</td>
            <td>{{ $order->created_at->format('M j, Y') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
@endsection

@push('scripts')
<script>
document.getElementById('copy-referral')?.addEventListener('click', function () {
    const input = document.getElementById('referral-url');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        this.textContent = 'Copied!';
        setTimeout(() => { this.textContent = 'Copy Link'; }, 2000);
    });
});
</script>
@endpush
