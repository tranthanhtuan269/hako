@extends('layouts.admin')

@section('title', $store->exists ? 'Edit Store' : 'Add Store')

@section('content')
<h1 style="margin-bottom:1.5rem;">{{ $store->exists ? 'Edit' : 'Add' }} Store</h1>
<form method="POST" enctype="multipart/form-data" action="{{ $store->exists ? route('admin.stores.update', $store) : route('admin.stores.store') }}">
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
        @php $ownerId = $store->user_id ?? auth()->id() ?? 'admin'; @endphp
        <p class="form-hint">Saved under <code>stores/{{ $ownerId }}/</code> when uploaded. JPG, PNG or WebP (max 2MB).</p>
        <input name="logo" value="{{ old('logo', $store->hasStoredLogo() ? '' : $store->logo) }}" placeholder="https://example.com/logo.png" style="margin-top:.5rem;">
    </div>
    @include('partials.store-category-field', ['store' => $store, 'categories' => $categories])
    @include('partials.store-website-field', ['store' => $store])
    @include('partials.store-description-editor', ['value' => $store->description, 'editorId' => 'admin-store-description'])
    @include('partials.store-coupon-display-fields', ['store' => $store, 'storeCoupons' => $storeCoupons ?? collect()])
    <div class="form-group"><label>Sort order</label><input type="number" name="sort_order" value="{{ old('sort_order', $store->sort_order ?? 0) }}"></div>
    <div class="form-check">
        <input type="checkbox" name="show_on_stores" value="1" id="show_on_stores" @checked(old('show_on_stores', $store->show_on_stores ?? true))>
        <label for="show_on_stores">Show on public Stores page (/stores)</label>
    </div>
    <div class="form-group">
        <label for="stores_list_sort_order">Order on Stores page</label>
        <input type="number" id="stores_list_sort_order" name="stores_list_sort_order" min="0" max="9999" value="{{ old('stores_list_sort_order', $store->stores_list_sort_order ?? 0) }}" style="max-width:8rem;">
        <p class="form-hint">Higher numbers appear first on the stores listing page.</p>
    </div>
    <div class="form-check">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $store->is_active ?? true))>
        <label>Active</label>
    </div>
    <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Save</button>
    <a href="{{ route('admin.stores.index') }}" class="btn btn-outline">Cancel</a>
</form>
@endsection
