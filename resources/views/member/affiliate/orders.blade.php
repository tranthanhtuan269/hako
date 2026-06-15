@extends('layouts.member')

@section('title', 'Affiliate Orders')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
    <h1>Affiliate Orders</h1>
    <a href="{{ route('member.affiliate.index') }}" class="btn btn-outline">← Back to Referral Program</a>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Description</th>
            <th>Amount</th>
            <th>Rate</th>
            <th>Commission</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse($orders as $order)
        <tr>
            <td><code>{{ $order->order_number }}</code></td>
            <td>
                {{ $order->customer_name ?: ($order->referredUser?->name ?: '—') }}
                @if($order->customer_email)
                    <br><small style="color:var(--muted);">{{ $order->customer_email }}</small>
                @endif
            </td>
            <td>{{ $order->description ?: '—' }}</td>
            <td>{{ number_format($order->amount, 2) }} {{ $currency }}</td>
            <td>{{ number_format($order->commission_rate, 0) }}%</td>
            <td>
                @if($order->commission_credited)
                    {{ number_format($order->commission_amount, 2) }} {{ $currency }}
                @else
                    —
                @endif
            </td>
            <td>{{ $order->statusLabel() }}</td>
            <td>{{ $order->created_at->format('M j, Y') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="8">No orders attributed to your referral link yet.</td>
        </tr>
        @endforelse
    </tbody>
</table>
{{ $orders->links() }}
@endsection
