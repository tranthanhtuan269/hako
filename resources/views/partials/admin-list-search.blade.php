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
@push('scripts')
<script src="{{ asset('js/admin-list-search.js') }}?v={{ filemtime(public_path('js/admin-list-search.js')) }}" defer></script>
@endpush
@endonce
