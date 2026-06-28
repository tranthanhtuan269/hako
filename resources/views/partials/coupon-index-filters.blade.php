@php
    $hasFilters = filled(request('title')) || filled(request('store_id'));
@endphp
<form method="GET" action="{{ $action }}" class="coupon-index-filters">
    <label class="coupon-index-filters__field">
        <span class="coupon-index-filters__label">Title</span>
        <span class="coupon-index-filters__input-wrap">
            <input
                type="search"
                name="title"
                id="coupon-filter-title"
                value="{{ request('title') }}"
                placeholder="Search by coupon title"
                maxlength="255"
                autocomplete="off"
            >
            <button
                type="button"
                class="coupon-index-filters__clear"
                id="coupon-filter-title-clear"
                aria-label="Clear title"
                title="Clear title"
                @if(!filled(request('title'))) hidden @endif
            >&times;</button>
        </span>
    </label>
    <label class="coupon-index-filters__field">
        <span class="coupon-index-filters__label">Shop</span>
        <select name="store_id">
            <option value="">All shops</option>
            @foreach($stores as $store)
                <option value="{{ $store->id }}" @selected((string) request('store_id') === (string) $store->id)>{{ $store->name }}</option>
            @endforeach
        </select>
    </label>
    <div class="coupon-index-filters__actions">
        <button type="submit" class="btn btn-outline">Search</button>
        @if($hasFilters)
            <a href="{{ $action }}" class="btn btn-outline">Clear</a>
        @endif
    </div>
</form>

@once
    @push('styles')
    <style>
    .coupon-index-filters {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem 1rem;
        align-items: flex-end;
        margin-bottom: 1.25rem;
        padding: 1rem 1.1rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: .5rem;
    }
    .coupon-index-filters__field {
        display: flex;
        flex-direction: column;
        gap: .25rem;
        min-width: 12rem;
        flex: 1 1 12rem;
    }
    .coupon-index-filters__label {
        font-size: .8rem;
        font-weight: 600;
        color: var(--muted, #64748b);
    }
    .coupon-index-filters__field input,
    .coupon-index-filters__field select {
        width: 100%;
        padding: .45rem .6rem;
        border: 1px solid #cbd5e1;
        border-radius: .35rem;
        background: #fff;
    }
    .coupon-index-filters__input-wrap {
        position: relative;
        display: block;
    }
    .coupon-index-filters__input-wrap input {
        padding-right: 2rem;
    }
    .coupon-index-filters__clear {
        position: absolute;
        top: 50%;
        right: .35rem;
        transform: translateY(-50%);
        border: 0;
        background: transparent;
        color: #94a3b8;
        font-size: 1.25rem;
        line-height: 1;
        width: 1.75rem;
        height: 1.75rem;
        padding: 0;
        cursor: pointer;
        border-radius: .25rem;
    }
    .coupon-index-filters__clear:hover {
        color: #475569;
        background: #f1f5f9;
    }
    .coupon-index-filters__actions {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
    }
    </style>
    @endpush

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('.coupon-index-filters');
        const input = document.getElementById('coupon-filter-title');
        const clearBtn = document.getElementById('coupon-filter-title-clear');

        if (!form || !input || !clearBtn) {
            return;
        }

        const syncClear = () => {
            clearBtn.hidden = input.value.trim() === '';
        };

        input.addEventListener('input', syncClear);

        clearBtn.addEventListener('click', () => {
            input.value = '';
            syncClear();
            form.requestSubmit();
        });
    });
    </script>
    @endpush
@endonce
