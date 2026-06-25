@extends('layouts.member')

@section('title', $coupon->exists ? 'Edit Coupon' : 'Add Coupon')

@section('content')
<h1 style="margin-bottom:1.5rem;">{{ $coupon->exists ? 'Edit' : 'Add' }} Coupon</h1>
@if($stores->isEmpty())
    <div class="alert alert-error">You need at least one store before adding coupons. <a href="{{ route('member.stores.create') }}">Add a store</a></div>
@else
<form method="POST" action="{{ $coupon->exists ? route('member.coupons.update', $coupon) : route('member.coupons.store') }}">
    @csrf
    @if($coupon->exists) @method('PUT') @endif

    <div class="form-group">
        <label>Store *</label>
        <select name="store_id" required>
            @foreach($stores as $s)
                <option value="{{ $s->id }}" @selected(old('store_id', $coupon->store_id) == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Title *</label>
        <input type="text" name="title" value="{{ old('title', $coupon->title) }}" required>
    </div>
    <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="3">{{ old('description', $coupon->description) }}</textarea>
    </div>
    <div class="form-group">
        <label>Promo code</label>
        <input type="text" name="code" value="{{ old('code', $coupon->code) }}" placeholder="Leave blank for discount deals">
        <p class="form-hint">If you enter a code, this becomes a Coupon. If left blank, it becomes a Discount.</p>
    </div>
    <div class="form-group">
        <label for="expires_at">Expiration date (optional)</label>
        <input type="datetime-local" id="expires_at" name="expires_at" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}">
        <p class="form-hint">Leave blank if this offer does not expire.</p>
        @error('expires_at')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div class="form-check">
        <input type="checkbox" name="is_featured" value="1" id="featured" @checked(old('is_featured', $coupon->is_featured))>
        <label for="featured">Featured</label>
    </div>
    <div class="form-check">
        <input type="checkbox" name="is_active" value="1" id="active" @checked(old('is_active', $coupon->is_active ?? true))>
        <label for="active">Active</label>
    </div>
    <div class="form-check">
        <input type="checkbox" name="show_on_coupons" value="1" id="show_on_coupons" @checked(old('show_on_coupons', $coupon->show_on_coupons ?? true))>
        <label for="show_on_coupons">Show on public Coupons page (/coupons)</label>
    </div>
    <div class="form-group">
        <label for="coupons_sort_order">Order on Coupons page</label>
        <input type="number" id="coupons_sort_order" name="coupons_sort_order" min="0" max="9999" value="{{ old('coupons_sort_order', $coupon->coupons_sort_order ?? 0) }}" style="max-width:8rem;">
        <p class="form-hint">Higher numbers appear first on the coupons listing page.</p>
    </div>
    @if($coupon->exists)
        <p class="form-hint" style="margin-top:1rem;">Total clicks on this coupon link: <strong>{{ number_format($coupon->click_count) }}</strong></p>
    @endif
    <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Save</button>
    <a href="{{ route('member.coupons.index') }}" class="btn btn-outline">Cancel</a>
</form>
@endif
@endsection
