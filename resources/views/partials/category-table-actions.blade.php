@php
    $publicUrl = route('categories.show', $category->slug);
    $editUrl = $editUrl ?? route('admin.categories.edit', $category);
    $destroyUrl = $destroyUrl ?? route('admin.categories.destroy', $category);
    $deleteConfirm = $deleteConfirm ?? 'Delete category "' . $category->name . '"?';
@endphp
<div class="table-actions">
    @if($category->is_active)
        <a href="{{ $publicUrl }}" class="table-action-btn" target="_blank" rel="noopener" title="View public page" aria-label="View public page">
            @include('partials.icons.eye')
        </a>
    @else
        <span class="table-action-btn is-disabled" title="Category is hidden" aria-label="Category is hidden">
            @include('partials.icons.eye')
        </span>
    @endif
    <button
        type="button"
        class="table-action-btn js-copy-link"
        data-copy-url="{{ $publicUrl }}"
        data-copy-title="Copy category link"
        title="Copy category link"
        aria-label="Copy category link"
    >
        @include('partials.icons.copy')
    </button>
    <a href="{{ $editUrl }}" class="table-action-btn" title="Edit category" aria-label="Edit category">
        @include('partials.icons.edit')
    </a>
    <form action="{{ $destroyUrl }}" method="POST" class="table-action-form" onsubmit="return confirm(@json($deleteConfirm))">
        @csrf
        @method('DELETE')
        <button type="submit" class="table-action-btn table-action-btn-danger" title="Delete category" aria-label="Delete category">
            @include('partials.icons.trash')
        </button>
    </form>
</div>
