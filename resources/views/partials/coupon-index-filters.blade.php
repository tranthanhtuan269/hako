@php
    $hasFilters = filled(request('title')) || filled(request('store_id'));
    $selectedStoreId = request('store_id');
    $selectedStoreName = $stores->firstWhere('id', (int) $selectedStoreId)?->name ?? '';
    $storeOptions = $stores->map(fn ($store) => [
        'id' => $store->id,
        'name' => $store->name,
    ])->values();
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
        <div class="coupon-store-autocomplete" data-store-autocomplete data-stores='@json($storeOptions)'>
            <input type="hidden" name="store_id" id="coupon-filter-store-id" value="{{ $selectedStoreId }}">
            <span class="coupon-index-filters__input-wrap">
                <input
                    type="search"
                    id="coupon-filter-store"
                    value="{{ $selectedStoreName }}"
                    placeholder="Type store name..."
                    maxlength="255"
                    autocomplete="off"
                    role="combobox"
                    aria-expanded="false"
                    aria-controls="coupon-filter-store-suggest"
                    aria-autocomplete="list"
                >
                <button
                    type="button"
                    class="coupon-index-filters__clear"
                    id="coupon-filter-store-clear"
                    aria-label="Clear shop"
                    title="Clear shop"
                    @if(!filled($selectedStoreName)) hidden @endif
                >&times;</button>
            </span>
            <ul
                class="coupon-store-autocomplete__list"
                id="coupon-filter-store-suggest"
                role="listbox"
                hidden
            ></ul>
        </div>
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
    .coupon-store-autocomplete {
        position: relative;
    }
    .coupon-store-autocomplete__list {
        position: absolute;
        z-index: 40;
        top: calc(100% + .25rem);
        left: 0;
        right: 0;
        max-height: 14rem;
        overflow-y: auto;
        margin: 0;
        padding: .35rem 0;
        list-style: none;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: .35rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
    }
    .coupon-store-autocomplete__list[hidden] {
        display: none !important;
    }
    .coupon-store-autocomplete__option {
        padding: .5rem .65rem;
        font-size: .9rem;
        cursor: pointer;
        color: #0f172a;
    }
    .coupon-store-autocomplete__option:hover,
    .coupon-store-autocomplete__option.is-active {
        background: #eff6ff;
        color: #1d4ed8;
    }
    .coupon-store-autocomplete__empty {
        padding: .5rem .65rem;
        font-size: .85rem;
        color: #64748b;
    }
    </style>
    @endpush

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-store-autocomplete]').forEach((storeAutocomplete) => {
        const form = storeAutocomplete.closest('.coupon-index-filters');
        const titleInput = form?.querySelector('#coupon-filter-title');
        const titleClearBtn = form?.querySelector('#coupon-filter-title-clear');
        const storeInput = storeAutocomplete.querySelector('#coupon-filter-store');
        const storeIdInput = storeAutocomplete.querySelector('#coupon-filter-store-id');
        const storeClearBtn = storeAutocomplete.querySelector('#coupon-filter-store-clear');
        const storeList = storeAutocomplete.querySelector('#coupon-filter-store-suggest');

        if (!form || !titleInput || !titleClearBtn || !storeInput || !storeIdInput || !storeList) {
            return;
        }

        let stores = [];
        try {
            stores = JSON.parse(storeAutocomplete.dataset.stores || '[]');
        } catch (error) {
            stores = [];
        }
        let activeIndex = -1;

        const syncTitleClear = () => {
            titleClearBtn.hidden = titleInput.value.trim() === '';
        };

        const syncStoreClear = () => {
            storeClearBtn.hidden = storeInput.value.trim() === '' && storeIdInput.value === '';
        };

        const normalize = (value) => value.trim().toLowerCase();

        const findExactStore = (query) => {
            const needle = normalize(query);
            if (!needle) {
                return null;
            }

            return stores.find((store) => normalize(store.name) === needle) || null;
        };

        const filterStores = (query) => {
            const needle = normalize(query);
            if (!needle) {
                return stores.slice(0, 12);
            }

            return stores.filter((store) => normalize(store.name).includes(needle)).slice(0, 12);
        };

        const setStoreSelection = (store) => {
            if (store) {
                storeInput.value = store.name;
                storeIdInput.value = String(store.id);
            } else {
                storeInput.value = '';
                storeIdInput.value = '';
            }
            syncStoreClear();
            hideSuggestions();
        };

        const renderSuggestions = (matches) => {
            storeList.innerHTML = '';
            activeIndex = -1;

            if (matches.length === 0) {
                const empty = document.createElement('li');
                empty.className = 'coupon-store-autocomplete__empty';
                empty.textContent = 'No matching stores';
                storeList.appendChild(empty);
                showSuggestions();
                return;
            }

            matches.forEach((store, index) => {
                const item = document.createElement('li');
                item.className = 'coupon-store-autocomplete__option';
                item.textContent = store.name;
                item.setAttribute('role', 'option');
                item.dataset.storeId = String(store.id);
                item.dataset.index = String(index);
                item.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    setStoreSelection(store);
                });
                storeList.appendChild(item);
            });

            showSuggestions();
        };

        const showSuggestions = () => {
            storeList.hidden = false;
            storeInput.setAttribute('aria-expanded', 'true');
        };

        const hideSuggestions = () => {
            storeList.hidden = true;
            storeInput.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
            storeList.querySelectorAll('.coupon-store-autocomplete__option').forEach((option) => {
                option.classList.remove('is-active');
            });
        };

        const highlightOption = (index) => {
            const options = storeList.querySelectorAll('.coupon-store-autocomplete__option');
            if (!options.length) {
                return;
            }

            activeIndex = Math.max(0, Math.min(index, options.length - 1));
            options.forEach((option, optionIndex) => {
                option.classList.toggle('is-active', optionIndex === activeIndex);
            });
            options[activeIndex].scrollIntoView({ block: 'nearest' });
        };

        const selectActiveOption = () => {
            const options = storeList.querySelectorAll('.coupon-store-autocomplete__option');
            if (activeIndex < 0 || !options[activeIndex]) {
                return false;
            }

            const storeId = options[activeIndex].dataset.storeId;
            const store = stores.find((item) => String(item.id) === storeId);
            if (store) {
                setStoreSelection(store);
                return true;
            }

            return false;
        };

        titleInput.addEventListener('input', syncTitleClear);
        titleClearBtn.addEventListener('click', () => {
            titleInput.value = '';
            syncTitleClear();
            form.requestSubmit();
        });

        storeInput.addEventListener('input', () => {
            const query = storeInput.value;
            const exact = findExactStore(query);

            if (exact && normalize(query) === normalize(exact.name)) {
                storeIdInput.value = String(exact.id);
            } else {
                storeIdInput.value = '';
            }

            syncStoreClear();
            renderSuggestions(filterStores(query));
        });

        storeInput.addEventListener('focus', () => {
            renderSuggestions(filterStores(storeInput.value));
        });

        storeInput.addEventListener('keydown', (event) => {
            const options = storeList.querySelectorAll('.coupon-store-autocomplete__option');

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (storeList.hidden) {
                    renderSuggestions(filterStores(storeInput.value));
                }
                highlightOption(activeIndex + 1);
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                highlightOption(activeIndex - 1);
                return;
            }

            if (event.key === 'Enter') {
                if (!storeList.hidden && selectActiveOption()) {
                    event.preventDefault();
                }
                return;
            }

            if (event.key === 'Escape') {
                hideSuggestions();
            }
        });

        storeClearBtn.addEventListener('click', () => {
            setStoreSelection(null);
            form.requestSubmit();
        });

        document.addEventListener('click', (event) => {
            if (!storeAutocomplete.contains(event.target)) {
                hideSuggestions();
            }
        });

        form.addEventListener('submit', () => {
            const exact = findExactStore(storeInput.value);
            if (exact) {
                storeIdInput.value = String(exact.id);
            }
        });

        syncTitleClear();
        syncStoreClear();
        });
    });
    </script>
    @endpush
@endonce
