@extends('layouts.admin')

@section('title', $order->exists ? 'Edit Affiliate Order' : 'Log Affiliate Order')

@section('content')
<h1 style="margin-bottom:1.5rem;">{{ $order->exists ? 'Edit Affiliate Order' : 'Log Affiliate Order' }}</h1>

<form method="POST" action="{{ $order->exists ? route('admin.affiliate.orders.update', $order) : route('admin.affiliate.orders.store') }}">
    @csrf
    @if($order->exists)
        @method('PUT')
    @endif

    @if($order->exists)
    <div class="form-group">
        <label>Order Number</label>
        <input type="text" value="{{ $order->order_number }}" readonly>
    </div>
    @endif

    <div class="form-group">
        <label for="referrer_user_id">Referrer (affiliate member) *</label>
        <select id="referrer_user_id" name="referrer_user_id" required>
            <option value="">— Select —</option>
            @foreach($referrers as $referrer)
                <option value="{{ $referrer->id }}" @selected(old('referrer_user_id', $order->referrer_user_id) == $referrer->id)>
                    {{ $referrer->name }} ({{ $referrer->referral_code }}) — {{ $referrer->email }}
                </option>
            @endforeach
        </select>
        @error('referrer_user_id')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-group">
        <label for="referred_user_id">Registered User (optional)</label>
        <select id="referred_user_id" name="referred_user_id">
            <option value="">— None —</option>
            @foreach($referrers as $referrer)
                <option value="{{ $referrer->id }}" @selected(old('referred_user_id', $order->referred_user_id) == $referrer->id)>
                    {{ $referrer->name }} — {{ $referrer->email }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="customer_name">Customer Name</label>
            <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name', $order->customer_name) }}">
        </div>
        <div class="form-group">
            <label for="customer_email">Customer Email</label>
            <input type="email" id="customer_email" name="customer_email" value="{{ old('customer_email', $order->customer_email) }}">
        </div>
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <input type="text" id="description" name="description" value="{{ old('description', $order->description) }}" placeholder="e.g. Annual membership">
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="amount">Order Amount ({{ $currency }}) *</label>
            <input type="number" step="0.01" min="0" id="amount" name="amount" value="{{ old('amount', $order->amount) }}" required>
            @error('amount')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label for="commission_rate">Commission Rate (%) *</label>
            <input type="number" step="0.01" min="0" max="100" id="commission_rate" name="commission_rate" value="{{ old('commission_rate', $order->commission_rate) }}" required>
        </div>
    </div>

    <div class="form-group">
        <label for="status">Status *</label>
        <select id="status" name="status" required>
            @foreach(\App\Models\AffiliateOrder::STATUSES as $status)
                <option value="{{ $status }}" @selected(old('status', $order->status) === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <p class="form-hint">Set to <strong>Paid</strong> after payment is confirmed to credit commission to the referrer's balance.</p>
        @error('status')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-group">
        <label for="notes">Admin Notes</label>
        <textarea id="notes" name="notes" rows="3">{{ old('notes', $order->notes) }}</textarea>
    </div>

    @if($order->exists && $order->commission_credited)
        <p class="form-hint" style="margin-bottom:1rem;">Commission credited: <strong>{{ number_format($order->commission_amount, 2) }} {{ $currency }}</strong></p>
    @endif

    <div style="display:flex;gap:.75rem;">
        <button type="submit" class="btn btn-primary">{{ $order->exists ? 'Update Order' : 'Log Order' }}</button>
        <a href="{{ route('admin.affiliate.orders.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>
@endsection
