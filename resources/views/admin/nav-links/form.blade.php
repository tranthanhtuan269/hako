@extends('layouts.admin')

@section('title', $link->exists ? 'Edit Menu Item' : 'Add Menu Item')

@section('content')
<div class="admin-page-header">
    <h1>{{ $link->exists ? 'Edit Menu Item' : 'Add Menu Item' }}</h1>
    <a href="{{ route('admin.nav-links.index') }}" class="btn btn-outline">← Back to list</a>
</div>

<form method="POST" action="{{ $link->exists ? route('admin.nav-links.update', $link) : route('admin.nav-links.store') }}" class="import-card" style="max-width:640px;">
    @csrf
    @if($link->exists)
        @method('PUT')
    @endif

    <div class="form-group">
        <label for="label">Label *</label>
        <input type="text" id="label" name="label" value="{{ old('label', $link->label) }}" required maxlength="80">
        @error('label')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-group">
        <label for="url">URL *</label>
        <input type="text" id="url" name="url" value="{{ old('url', $link->url) }}" required maxlength="500" placeholder="/coupons">
        <p class="form-hint">Internal path (<code>/stores</code>, <code>/coupons?type=discount</code>) or full URL (<code>https://…</code>).</p>
        @error('url')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-group">
        <label for="sort_order">Sort order</label>
        <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $link->sort_order ?? 0) }}" min="0">
        <p class="form-hint">Lower numbers appear first in the header.</p>
        @error('sort_order')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-check">
        <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $link->is_active ?? true))>
        <label for="is_active">Active (visible in header)</label>
    </div>

    <div class="form-check">
        <input type="checkbox" id="open_in_new_tab" name="open_in_new_tab" value="1" @checked(old('open_in_new_tab', $link->open_in_new_tab ?? false))>
        <label for="open_in_new_tab">Open in a new tab</label>
    </div>

    <div style="margin-top:1.25rem;display:flex;gap:.75rem;">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="{{ route('admin.nav-links.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>
@endsection

@push('styles')
<style>
.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.admin-page-header h1 { margin: 0; }
.import-card {
    background: var(--card, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
    padding: 1.25rem 1.5rem;
}
</style>
@endpush
