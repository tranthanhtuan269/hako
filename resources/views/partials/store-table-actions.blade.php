@php
    $publicUrl = route('stores.show', $store->slug);
    $editUrl = $editUrl ?? route('member.stores.edit', $store);
    $destroyUrl = $destroyUrl ?? route('member.stores.destroy', $store);
    $showCopy = $showCopy ?? true;
@endphp
<div class="table-actions">
    @if($store->is_active)
        <a href="{{ $publicUrl }}" class="table-action-btn" target="_blank" rel="noopener" title="View public page" aria-label="View public page">
            @include('partials.icons.eye')
        </a>
    @else
        <span class="table-action-btn is-disabled" title="Store is inactive" aria-label="Store is inactive">
            @include('partials.icons.eye')
        </span>
    @endif
    @if($showCopy)
        <button
            type="button"
            class="table-action-btn js-copy-link"
            data-copy-url="{{ $publicUrl }}"
            data-copy-title="Copy store link"
            title="Copy store link"
            aria-label="Copy store link"
        >
            @include('partials.icons.copy')
        </button>
    @endif
    <a href="{{ $editUrl }}" class="table-action-btn" title="Edit store" aria-label="Edit store">
        @include('partials.icons.edit')
    </a>
    <form action="{{ $destroyUrl }}" method="POST" class="table-action-form" onsubmit="return confirm('Delete this store and its coupons?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="table-action-btn table-action-btn-danger" title="Delete store" aria-label="Delete store">
            @include('partials.icons.trash')
        </button>
    </form>
</div>
