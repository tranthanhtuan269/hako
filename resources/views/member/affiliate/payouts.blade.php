@extends('layouts.member')

@section('title', 'Payout Requests')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
    <h1>Payout Requests</h1>
    <a href="{{ route('member.affiliate.index') }}" class="btn btn-outline">← Back to Referral Program</a>
</div>

<p style="margin-bottom:1rem;">Available balance: <strong>{{ number_format($balance, 2) }} {{ $currency }}</strong> (minimum request: {{ number_format($minPayout, 2) }} {{ $currency }})</p>

@if($balance >= $minPayout)
<div class="form-card" id="request-payout" style="margin-bottom:1.5rem;">
    <h2>Request Payout</h2>
    <form method="POST" action="{{ route('member.affiliate.payouts.store') }}">
        @csrf
        <div class="form-group">
            <label for="amount">Amount ({{ $currency }}) *</label>
            <input type="number" step="0.01" min="{{ $minPayout }}" max="{{ $balance }}" id="amount" name="amount" value="{{ old('amount', $balance) }}" required>
            @error('amount')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label for="payment_method">Payment Method *</label>
            <input type="text" id="payment_method" name="payment_method" value="{{ old('payment_method') }}" placeholder="Bank transfer, PayPal, etc." required>
            @error('payment_method')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label for="payment_details">Payment Details *</label>
            <textarea id="payment_details" name="payment_details" rows="4" required placeholder="Account number, PayPal email, etc.">{{ old('payment_details') }}</textarea>
            @error('payment_details')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label for="member_note">Note (optional)</label>
            <textarea id="member_note" name="member_note" rows="2">{{ old('member_note') }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit Payout Request</button>
    </form>
</div>
@endif

<table class="admin-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Status</th>
            <th>Requested</th>
            <th>Processed</th>
        </tr>
    </thead>
    <tbody>
        @forelse($payouts as $payout)
        <tr>
            <td>{{ $payout->id }}</td>
            <td>{{ number_format($payout->amount, 2) }} {{ $currency }}</td>
            <td>{{ $payout->payment_method ?: '—' }}</td>
            <td>{{ $payout->statusLabel() }}</td>
            <td>{{ $payout->created_at->format('M j, Y H:i') }}</td>
            <td>{{ $payout->processed_at?->format('M j, Y H:i') ?: '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6">No payout requests yet.</td>
        </tr>
        @endforelse
    </tbody>
</table>
{{ $payouts->links() }}
@endsection
