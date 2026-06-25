@extends('layouts.member')

@section('title', $store->exists ? 'Edit Store' : 'Add Store')

@section('content')
<h1 style="margin-bottom:1.5rem;">{{ $store->exists ? 'Edit' : 'Add' }} Store</h1>
<form method="POST" enctype="multipart/form-data" action="{{ $store->exists ? route('member.stores.update', $store) : route('member.stores.store') }}">
    @csrf
    @if($store->exists) @method('PUT') @endif
    @include('partials.store-slug-fields', ['store' => $store])
    <div class="form-group">
        <label>Logo</label>
        @if($store->logoUrl())
            <div class="admin-image-preview">
                <img src="{{ $store->logoUrl() }}" alt="{{ $store->name }}" class="admin-preview-img">
            </div>
        @endif
        <input type="file" name="logo_file" accept="image/*" style="margin-top:.5rem;">
        <p class="form-hint">Upload JPG, PNG or WebP (max 2MB). Files are saved in your account folder <code>stores/{{ auth()->id() }}/</code>. Or paste an external image URL below.</p>
        <input name="logo" value="{{ old('logo', $store->hasStoredLogo() ? '' : $store->logo) }}" placeholder="https://example.com/logo.png" style="margin-top:.5rem;">
    </div>
    @include('partials.store-category-field', ['store' => $store, 'categories' => $categories])
    @include('partials.store-website-field', ['store' => $store])
    @include('partials.store-description-editor', ['value' => $store->description, 'editorId' => 'store-description'])
    @include('partials.store-coupon-display-fields', ['store' => $store, 'storeCoupons' => $storeCoupons ?? collect()])
    <div class="form-check">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $store->is_active ?? true))>
        <label>Active (visible on public site)</label>
    </div>
    @if($store->exists)
        <p class="form-hint" style="margin-top:1rem;">Page views on your public store link: <strong>{{ number_format($store->view_count) }}</strong></p>
    @endif
    <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Save</button>
    <a href="{{ route('member.stores.index') }}" class="btn btn-outline">Cancel</a>
</form>
@endsection
