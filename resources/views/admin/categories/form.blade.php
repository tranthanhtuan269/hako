@extends('layouts.admin')

@section('title', $category->exists ? 'Edit Category' : 'Add Category')

@section('content')
<div class="admin-page-header">
    <h1>{{ $category->exists ? 'Edit Category' : 'Add Category' }}</h1>
    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline">← Back to list</a>
</div>

<form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="import-card category-form">
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
.category-form.import-card {
    max-width: 720px;
}
.import-card {
    background: var(--card, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
    padding: 1.25rem 1.5rem;
}
.category-icon-preview {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: .75rem;
    padding: .65rem .85rem;
    background: #f8fafc;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
}
.category-icon-preview-emoji {
    font-size: 2rem;
    line-height: 1;
}
.category-icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(7.5rem, 1fr));
    gap: .5rem;
    max-height: 320px;
    overflow-y: auto;
    padding: .5rem;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
    background: #fafafa;
}
.category-icon-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .25rem;
    padding: .5rem .35rem;
    border: 2px solid transparent;
    border-radius: 8px;
    background: #fff;
    cursor: pointer;
    transition: border-color .15s, background .15s, box-shadow .15s;
    font: inherit;
    color: inherit;
}
.category-icon-option:hover {
    border-color: #cbd5e1;
    background: #f1f5f9;
}
.category-icon-option.is-selected {
    border-color: var(--primary, #2563eb);
    background: #eff6ff;
    box-shadow: 0 0 0 1px var(--primary, #2563eb);
}
.category-icon-option-emoji {
    font-size: 1.75rem;
    line-height: 1;
}
.category-icon-option-label {
    font-size: .65rem;
    color: var(--muted, #64748b);
    text-align: center;
    line-height: 1.2;
    max-width: 100%;
}
.btn-sm { padding: .35rem .65rem; font-size: .8rem; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const input = document.getElementById('icon');
    const preview = document.getElementById('category-icon-preview');
    const previewEmoji = document.getElementById('category-icon-preview-emoji');
    const clearBtn = document.getElementById('category-icon-clear');
    const options = document.querySelectorAll('.category-icon-option');

    function setIcon(value) {
        if (!input) return;
        input.value = value || '';

        options.forEach(function (btn) {
            var selected = btn.dataset.icon === value && value !== '';
            btn.classList.toggle('is-selected', selected);
            btn.setAttribute('aria-selected', selected ? 'true' : 'false');
        });

        if (preview && previewEmoji) {
            if (value) {
                preview.hidden = false;
                previewEmoji.textContent = value;
            } else {
                preview.hidden = true;
                previewEmoji.textContent = '';
            }
        }
    }

    options.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setIcon(btn.dataset.icon || '');
        });
    });

    clearBtn?.addEventListener('click', function () {
        setIcon('');
    });
})();
</script>
@endpush
