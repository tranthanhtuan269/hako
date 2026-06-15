@extends('layouts.admin')

@section('title', 'Review Payout Request')

@section('content')
<h1 style="margin-bottom:1.5rem;">Payout Request #{{ $payout->id }}</h1>

<div class="form-card" style="margin-bottom:1.5rem;">
    <p><strong>Member:</strong> {{ $payout->user?->name }} ({{ $payout->user?->email }})</p>
    <p><strong>Amount:</strong> {{ number_format($payout->amount, 2) }} {{ $currency }}</p>
    <p><strong>Payment Method:</strong> {{ $payout->payment_method ?: '—' }}</p>
    <p><strong>Payment Details:</strong></p>
    <pre style="white-space:pre-wrap;background:var(--bg-alt);padding:.75rem;border-radius:6px;">{{ $payout->payment_details }}</pre>
    @if($payout->member_note)
        <p><strong>Member Note:</strong> {{ $payout->member_note }}</p>
    @endif
    <p><strong>Requested:</strong> {{ $payout->created_at->format('M j, Y H:i') }}</p>
    @if($payout->processed_at)
        <p><strong>Processed:</strong> {{ $payout->processed_at->format('M j, Y H:i') }} by {{ $payout->processedBy?->name }}</p>
    @endif
</div>

<form method="POST" action="{{ route('admin.affiliate.payouts.update', $payout) }}">
    @csrf
    @method('PUT')

    <div class="form-group">
        <label for="status">Status *</label>
        <select id="status" name="status" required>
            @foreach(\App\Models\AffiliatePayoutRequest::STATUSES as $status)
                <option value="{{ $status }}" @selected(old('status', $payout->status) === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <p class="form-hint">
            <strong>Pending</strong> — waiting for your payment.<br>
            <strong>Approved</strong> — approved, payment in progress.<br>
            <strong>Paid</strong> — you have sent the money (final).<br>
            <strong>Rejected</strong> — declined; amount returns to member balance.
        </p>
        @error('status')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-group">
        <label for="admin_note">Admin Note</label>
        <textarea id="admin_note" name="admin_note" rows="3" placeholder="Transaction ID, payment date, reason for rejection, etc.">{{ old('admin_note', $payout->admin_note) }}</textarea>
    </div>

    <div style="display:flex;gap:.75rem;">
        <button type="submit" class="btn btn-primary">Update Status</button>
        <a href="{{ route('admin.affiliate.payouts.index') }}" class="btn btn-outline">Back</a>
    </div>
</form>
@endsection
