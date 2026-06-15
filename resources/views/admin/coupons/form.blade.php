@extends('layouts.admin')

@section('title', $coupon->exists ? 'Edit Coupon' : 'Add Coupon')

@section('content')
<h1 style="margin-bottom:1.5rem;">{{ $coupon->exists ? 'Edit' : 'Add' }} Coupon</h1>
<form method="POST" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}">
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
    <div class="form-check">
        <input type="checkbox" name="is_featured" value="1" id="featured" @checked(old('is_featured', $coupon->is_featured))>
        <label for="featured">Featured</label>
    </div>
    <div class="form-check">
        <input type="checkbox" name="is_active" value="1" id="active" @checked(old('is_active', $coupon->is_active ?? true))>
        <label for="active">Active</label>
    </div>
    <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Save</button>
    <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline">Cancel</a>
</form>
@endsection
