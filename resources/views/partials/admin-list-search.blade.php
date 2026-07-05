@php
    $searchValue = $value ?? '';
    $clearUrl = $clearUrl ?? url()->current();
    $hiddenFields = $hiddenFields ?? [];
@endphp
<form action="{{ $action }}" method="GET" class="admin-list-search" data-live-search>
    @foreach($hiddenFields as $name => $fieldValue)
        @if(filled($fieldValue))
            <input type="hidden" name="{{ $name }}" value="{{ $fieldValue }}">
        @endif
    @endforeach
    <div class="admin-search-field">
        <input
            type="search"
            name="q"
            value="{{ $searchValue }}"
            placeholder="{{ $placeholder ?? 'Search…' }}"
            maxlength="120"
            autocomplete="off"
            class="admin-search-input"
            data-live-search-input
        >
        <button
            type="button"
            class="admin-search-clear"
            data-live-search-clear
            data-clear-url="{{ $clearUrl }}"
            aria-label="Clear search"
            @if(! filled($searchValue)) hidden @endif
        >×</button>
    </div>
</form>

@once
@push('styles')
<style>
.admin-list-search { margin-bottom: 1rem; width: 100%; }
.admin-search-field { position: relative; width: 100%; }
.admin-search-input {
    width: 100%;
    box-sizing: border-box;
    padding: .55rem 2.25rem .55rem .75rem;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
    font: inherit;
}
.admin-search-clear {
    position: absolute;
    top: 50%;
    right: .45rem;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: #64748b;
    font-size: 1.35rem;
    line-height: 1;
    padding: .15rem .35rem;
    cursor: pointer;
    border-radius: 4px;
}
.admin-search-clear:hover { color: #0f172a; background: #f1f5f9; }
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/admin-list-search.js') }}?v={{ filemtime(public_path('js/admin-list-search.js')) }}" defer></script>
@endpush
@endonce
