@extends('layouts.admin')

@section('title', 'Affiliate Orders')

@section('content')
<div style="display:flex;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
    <h1>Affiliate Orders</h1>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
        <a href="{{ route('admin.affiliate.payouts.index') }}" class="btn btn-outline">Payout Requests</a>
        <a href="{{ route('admin.affiliate.orders.create') }}" class="btn btn-primary">+ Log Order</a>
    </div>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>Order</th>
            <th>Referrer</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Commission</th>
            <th>Status</th>
            <th class="table-actions-col">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($orders as $order)
        <tr>
            <td>
                <code>{{ $order->order_number }}</code>
                @if($order->description)
                    <br><small style="color:var(--muted);">{{ $order->description }}</small>
                @endif
            </td>
            <td>{{ $order->referrer?->name }}<br><small style="color:var(--muted);">{{ $order->referrer?->email }}</small></td>
            <td>
                {{ $order->customer_name ?: ($order->referredUser?->name ?: '—') }}
                @if($order->customer_email)
                    <br><small style="color:var(--muted);">{{ $order->customer_email }}</small>
                @endif
            </td>
            <td>{{ number_format($order->amount, 2) }} {{ $currency }}</td>
            <td>
                {{ number_format($order->commission_rate, 0) }}%
                @if($order->commission_credited)
                    <br><strong>{{ number_format($order->commission_amount, 2) }}</strong>
                @endif
            </td>
            <td>{{ $order->statusLabel() }}</td>
            <td>
                <a href="{{ route('admin.affiliate.orders.edit', $order) }}" class="btn btn-outline btn-sm">Edit</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7">No affiliate orders yet. <a href="{{ route('admin.affiliate.orders.create') }}">Log the first order</a>.</td>
        </tr>
        @endforelse
    </tbody>
</table>
{{ $orders->links() }}
@endsection
