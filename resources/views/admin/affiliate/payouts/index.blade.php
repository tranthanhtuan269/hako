@extends('layouts.admin')

@section('title', 'Affiliate Payout Requests')

@section('content')
<div style="display:flex;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
    <h1>Affiliate Payout Requests @if($pendingCount > 0)<span style="font-size:.9rem;color:var(--primary);">({{ $pendingCount }} pending)</span>@endif</h1>
    <a href="{{ route('admin.affiliate.orders.index') }}" class="btn btn-outline">Affiliate Orders</a>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Member</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Status</th>
            <th>Requested</th>
            <th class="table-actions-col">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($payouts as $payout)
        <tr>
            <td>{{ $payout->id }}</td>
            <td>{{ $payout->user?->name }}<br><small style="color:var(--muted);">{{ $payout->user?->email }}</small></td>
            <td>{{ number_format($payout->amount, 2) }} {{ $currency }}</td>
            <td>{{ $payout->payment_method ?: '—' }}</td>
            <td>{{ $payout->statusLabel() }}</td>
            <td>{{ $payout->created_at->format('M j, Y H:i') }}</td>
            <td>
                <a href="{{ route('admin.affiliate.payouts.edit', $payout) }}" class="btn btn-outline btn-sm">Review</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7">No payout requests yet.</td>
        </tr>
        @endforelse
    </tbody>
</table>
{{ $payouts->links() }}
@endsection
