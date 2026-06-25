@extends('layouts.admin')

@section('title', $category->exists ? 'Edit Category' : 'Add Category')

@section('content')
<div class="admin-page-header">
    <h1>{{ $category->exists ? 'Edit Category' : 'Add Category' }}</h1>
    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline">← Back to list</a>
</div>

<form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="import-card category-form" enctype="multipart/form-data">
    @csrf
    @if($category->exists)
        @method('PUT')
    @endif

    <div class="form-group">
        <label for="name">Name *</label>
        <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}" required maxlength="255">
        @error('name')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-group">
        <label for="slug">Slug</label>
        <input type="text" id="slug" name="slug" value="{{ old('slug', $category->slug) }}" maxlength="255" placeholder="auto-from-name">
        <p class="form-hint">Leave blank to generate from name. Used in URLs: /categories/your-slug</p>
        @error('slug')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    @include('admin.categories.partials.icon-picker', ['category' => $category, 'iconOptions' => $iconOptions])

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4" maxlength="5000">{{ old('description', $category->description) }}</textarea>
        <p class="form-hint">Shown on the public category page (SEO).</p>
        @error('description')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-group">
        <label for="sort_order">Sort order</label>
        <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0">
        <p class="form-hint">Lower numbers appear first on the homepage and category list.</p>
        @error('sort_order')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-check">
        <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))>
        <label for="is_active">Active (visible on site)</label>
    </div>

    <div style="margin-top:1.25rem;display:flex;gap:.75rem;">
        <button type="submit" class="btn btn-primary">Save category</button>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

@if($category->exists && $category->is_active)
    <p class="form-hint" style="margin-top:1rem;">
        Public page: <a href="{{ route('categories.show', $category->slug) }}" target="_blank" rel="noopener">{{ route('categories.show', $category->slug) }}</a>
    </p>
@endif
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
.category-form.import-card { max-width: 820px; }
.import-card {
    background: var(--card, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
    padding: 1.25rem 1.5rem;
}
</style>
@endpush
