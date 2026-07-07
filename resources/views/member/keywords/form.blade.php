@extends('layouts.member')

@section('title', 'Keyword Generator')

@section('content')
<h1 style="margin-bottom:.5rem;">Keyword Generator</h1>
<p style="color:var(--muted);margin-bottom:1.5rem;">
    Part of <strong>Google Ads Builder</strong> — generate keywords per store and export CSV for Google Ads Editor.
    Global sitelinks and callouts are managed in
    @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.ads-settings.index') }}">Ads Settings</a>.
    @else
        Ads Settings (admin).
    @endif
</p>

<form method="POST" action="{{ route('member.keywords.generate') }}" id="keyword-form">
    @csrf

    <div class="import-card">
        <h2>Store</h2>
        <div class="form-group">
            <label for="store_search">Store *</label>
            <div class="store-combobox" data-store-combobox>
                <input type="hidden" name="store_id" id="store_id" value="{{ old('store_id', $selectedStoreId ?? '') }}" required>
                <div class="admin-search-field">
                    <input
                        type="search"
                        id="store_search"
                        class="admin-search-input store-combobox-input"
                        value="{{ $selectedStore?->name ?? '' }}"
                        placeholder="Type to search stores…"
                        autocomplete="off"
                        spellcheck="false"
                        data-store-combobox-input
                    >
                    <button type="button" class="admin-search-clear" data-store-combobox-clear aria-label="Clear store" @if(! filled($selectedStoreId ?? null)) hidden @endif>×</button>
                </div>
                <ul class="store-combobox-list" data-store-combobox-list hidden role="listbox" aria-label="Stores"></ul>
            </div>
            <p id="store-load-status" class="form-hint" style="margin-top:.5rem;"></p>
            @error('store_id')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="import-card">
        <div class="import-card-header">
            <h2>Bestselling Products</h2>
            <button type="button" class="btn btn-outline btn-sm" id="add-product-btn">+ Add product</button>
        </div>
        <p class="form-hint" style="margin-bottom:1rem;">Optional. Each product adds {{ count($engine->productTemplates()) }} keyword phrases.</p>
        <div id="product-list">
            @php
                $productRows = $products ?? old('products', ['']);
                if ($productRows === []) {
                    $productRows = [''];
                }
            @endphp
            @foreach($productRows as $index => $productName)
                <div class="product-row" data-index="{{ $index }}">
                    <div class="form-group" style="margin-bottom:.75rem;">
                        <label>Product {{ $index + 1 }}</label>
                        <div class="import-detect-row">
                            <input type="text" name="products[]" value="{{ $productName }}" maxlength="120" placeholder="e.g. SSD, power bank, USB hub">
                            @if($index > 0)
                                <button type="button" class="btn btn-outline btn-sm remove-product-btn">Remove</button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @error('products')<p class="form-error">{{ $message }}</p>@enderror
        @error('products.*')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    @include('member.keywords.partials.ads-settings', [
        'adsExport' => $adsExport,
        'adsSettings' => $adsSettings ?? null,
        'selectedStore' => $selectedStore ?? null,
    ])

    @include('member.keywords.partials.target-settings', [
        'adsExport' => $adsExport,
        'adsSettings' => $adsSettings ?? null,
        'selectedStore' => $selectedStore ?? null,
    ])

    <div class="import-card">
        <h2>Templates Used</h2>
        <div class="keyword-templates-grid">
            <div>
                <h3 style="font-size:1rem;margin:0 0 .5rem;">Brand ({{ count($engine->brandTemplates()) }})</h3>
                <ul class="keyword-template-list">
                    @foreach($engine->brandTemplates() as $template)
                        <li><code>{{ $template }}</code></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h3 style="font-size:1rem;margin:0 0 .5rem;">Product ({{ count($engine->productTemplates()) }})</h3>
                <ul class="keyword-template-list">
                    @foreach($engine->productTemplates() as $template)
                        <li><code>{{ $template }}</code></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Generate keywords</button>
</form>

<div id="keyword-results-wrap">
    @if(!empty($result))
        @include('member.keywords.partials.results', [
            'result' => $result,
            'brandLabel' => $brandLabel,
            'engine' => $engine,
            'savedAt' => $savedAt ?? null,
            'fromSaved' => $fromSaved ?? false,
            'adsSettings' => $adsSettings ?? null,
            'exportCsvUrl' => !empty($selectedStoreId) ? route('member.keywords.export-csv', ['store_id' => $selectedStoreId]) : null,
            'exportAssetsCsvUrl' => !empty($selectedStoreId) ? route('member.keywords.export-assets-csv', ['store_id' => $selectedStoreId]) : null,
            'exportTargetingCsvUrl' => !empty($selectedStoreId) ? route('member.keywords.export-targeting-csv', ['store_id' => $selectedStoreId]) : null,
        ])
    @endif
</div>

<template id="product-row-template">
    <div class="product-row">
        <div class="form-group" style="margin-bottom:.75rem;">
            <label>Product</label>
            <div class="import-detect-row">
                <input type="text" name="products[]" value="" maxlength="120" placeholder="e.g. SSD, power bank">
                <button type="button" class="btn btn-outline btn-sm remove-product-btn">Remove</button>
            </div>
        </div>
    </div>
</template>
@endsection

@push('styles')
<style>
.import-card {
    background: var(--card, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.25rem;
}
.import-card h2 { margin: 0 0 1rem; font-size: 1.1rem; }
.import-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}
.import-card-header h2 { margin: 0; }
.import-detect-row {
    display: flex;
    gap: .5rem;
    align-items: center;
}
.import-detect-row input { flex: 1; }
.btn-sm { padding: .35rem .75rem; font-size: .875rem; }
.keyword-templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
}
.keyword-template-list {
    margin: 0;
    padding-left: 1.1rem;
    font-size: .9rem;
    color: var(--muted);
}
.keyword-template-list code {
    font-size: .85rem;
    background: #f3f4f6;
    padding: .1rem .35rem;
    border-radius: 4px;
}
.keyword-result-list {
    margin: 0;
    padding: 0;
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
}
.keyword-result-list li {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    padding: .35rem .65rem;
    border-radius: 6px;
    font-size: .9rem;
}
.keyword-export-area {
    width: 100%;
    font-family: ui-monospace, monospace;
    font-size: .875rem;
    padding: .75rem;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 6px;
    resize: vertical;
}
.keyword-ads-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem 1.25rem;
}
.keyword-ads-check {
    display: flex;
    align-items: flex-start;
    gap: .55rem;
    margin-top: 1rem;
    font-size: .92rem;
}
.keyword-multi-select {
    width: 100%;
    min-height: 10rem;
    padding: .5rem;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 6px;
    background: #fff;
}
.keyword-network-fieldset {
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
    padding: .85rem 1rem;
    margin-top: 1rem;
}
.keyword-network-fieldset legend {
    padding: 0 .35rem;
    font-size: .92rem;
    font-weight: 600;
}
.keyword-network-fieldset .keyword-ads-check {
    margin-top: .35rem;
}
.keyword-multi-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    flex-wrap: wrap;
    margin-bottom: .15rem;
}
.keyword-multi-actions {
    display: flex;
    gap: .65rem;
    flex-wrap: wrap;
}
.keyword-multi-action {
    border: 0;
    background: none;
    padding: 0;
    color: var(--primary, #2563eb);
    font-size: .84rem;
    cursor: pointer;
    text-decoration: underline;
}
.keyword-multi-action:hover {
    color: var(--primary-dark, #1d4ed8);
}
.keyword-multi-filter {
    width: 100%;
    margin: .35rem 0 .5rem;
    padding: .45rem .55rem;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 6px;
    font-size: .9rem;
}
.keyword-ads-preview {
    margin-top: 1rem;
    overflow-x: auto;
}
.keyword-ads-preview table {
    width: 100%;
    border-collapse: collapse;
    font-size: .82rem;
}
.keyword-ads-preview th,
.keyword-ads-preview td {
    border: 1px solid var(--border, #e5e7eb);
    padding: .45rem .55rem;
    text-align: left;
    white-space: nowrap;
}
.keyword-ads-preview th {
    background: #f8fafc;
}
.keyword-cpc-field {
    display: flex;
    align-items: stretch;
    width: 100%;
    gap: 0;
}
.keyword-cpc-symbol {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 2.5rem;
    padding: 0 .65rem;
    border: 1px solid var(--border, #e5e7eb);
    border-right: 0;
    border-radius: 8px 0 0 8px;
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    font-size: .95rem;
}
.keyword-cpc-field input {
    flex: 1;
    min-width: 0;
    border-radius: 0;
    border-left: 0;
    border-right: 0;
}
.keyword-cpc-currency {
    min-width: 7.5rem;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 0 8px 8px 0;
    padding: .45rem .55rem;
    font: inherit;
    background: #fff;
}
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
.store-combobox { position: relative; width: 100%; }
.store-combobox-list {
    position: absolute;
    z-index: 20;
    left: 0;
    right: 0;
    top: calc(100% + .25rem);
    margin: 0;
    padding: .35rem 0;
    list-style: none;
    max-height: 14rem;
    overflow-y: auto;
    background: #fff;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .12);
}
.store-combobox-option {
    padding: .5rem .75rem;
    cursor: pointer;
    font-size: .95rem;
}
.store-combobox-option:hover,
.store-combobox-option.is-active {
    background: #eff6ff;
    color: #1d4ed8;
}
.store-combobox-empty {
    padding: .65rem .75rem;
    color: var(--muted);
    font-size: .9rem;
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const list = document.getElementById('product-list');
    const tpl = document.getElementById('product-row-template');
    const addBtn = document.getElementById('add-product-btn');
    const storeIdInput = document.getElementById('store_id');
    const storeSearchInput = document.getElementById('store_search');
    const storeCombobox = document.querySelector('[data-store-combobox]');
    const storeComboboxList = document.querySelector('[data-store-combobox-list]');
    const storeComboboxClear = document.querySelector('[data-store-combobox-clear]');
    const stores = @json($stores->map(fn ($store) => ['id' => $store->id, 'name' => $store->name])->values());
    const loadStatus = document.getElementById('store-load-status');
    const resultsWrap = document.getElementById('keyword-results-wrap');
    const loadUrl = @json(route('member.keywords.load'));
    const exportCsvBaseUrl = @json(route('member.keywords.export-csv'));
    const exportAssetsCsvBaseUrl = @json(route('member.keywords.export-assets-csv'));
    const exportTargetingCsvBaseUrl = @json(route('member.keywords.export-targeting-csv'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const allLanguagesValue = @json('All languages');

    function toggleAdGroupFields() {
        const mode = document.getElementById('ad_group_mode')?.value;
        const singleWrap = document.getElementById('single-ad-group-wrap');
        const brandWrap = document.getElementById('brand-ad-group-wrap');
        const isSingle = mode === 'single';
        if (singleWrap) singleWrap.hidden = !isSingle;
        if (brandWrap) brandWrap.hidden = isSingle;
    }

    function setMultiSelect(id, values) {
        const el = document.getElementById(id);
        if (!el || !Array.isArray(values)) return;
        const selected = new Set(values);
        Array.from(el.options).forEach((opt) => {
            opt.selected = selected.has(opt.value);
        });
    }

    function selectedTargetLocations() {
        const target = document.getElementById('target_locations');
        if (!target) return new Set();

        return new Set(Array.from(target.selectedOptions).map((opt) => opt.value));
    }

    function applyExcludedLocationVisibility() {
        const excluded = document.getElementById('excluded_locations');
        if (!excluded) return;

        const targeted = selectedTargetLocations();
        const filterInput = document.querySelector('.keyword-multi-filter[data-target="excluded_locations"]');
        const q = filterInput?.value.trim().toLowerCase() || '';

        Array.from(excluded.options).forEach((opt) => {
            if (targeted.has(opt.value)) {
                opt.selected = false;
                opt.hidden = true;

                return;
            }

            opt.hidden = q !== '' && !opt.text.toLowerCase().includes(q);
        });
    }

    function bindMultiSelectTools() {
        document.querySelectorAll('.keyword-multi-filter').forEach((input) => {
            input.addEventListener('input', () => {
                if (input.dataset.target === 'excluded_locations') {
                    applyExcludedLocationVisibility();

                    return;
                }

                const sel = document.getElementById(input.dataset.target);
                if (!sel) return;
                const q = input.value.trim().toLowerCase();
                Array.from(sel.options).forEach((opt) => {
                    opt.hidden = q !== '' && !opt.text.toLowerCase().includes(q);
                });
            });
        });

        document.querySelectorAll('.keyword-multi-action').forEach((btn) => {
            btn.addEventListener('click', () => {
                const sel = document.getElementById(btn.dataset.target);
                if (!sel) return;
                const action = btn.dataset.action;

                Array.from(sel.options).forEach((opt) => {
                    if (action === 'all') {
                        if (sel.id === 'excluded_locations') {
                            opt.selected = !opt.hidden;
                        } else {
                            opt.selected = true;
                        }
                    } else if (action === 'clear') {
                        opt.selected = false;
                    } else if (action === 'all-languages') {
                        opt.selected = opt.value === allLanguagesValue;
                    } else if (action === 'each-language') {
                        opt.selected = opt.value !== allLanguagesValue;
                    }
                });

                if (sel.id === 'target_locations') {
                    applyExcludedLocationVisibility();
                }
            });
        });
    }

    function bindTargetExcludedSync() {
        document.getElementById('target_locations')?.addEventListener('change', applyExcludedLocationVisibility);
    }

    function applyAdsSettings(settings) {
        if (!settings) return;
        const map = {
            campaign_name: 'campaign_name',
            ad_group_mode: 'ad_group_mode',
            ad_group_name: 'ad_group_name',
            brand_ad_group_suffix: 'brand_ad_group_suffix',
            match_type: 'match_type',
            keyword_status: 'keyword_status',
            max_cpc: 'max_cpc',
            max_cpc_currency: 'max_cpc_currency',
            final_url: 'final_url',
            targeting_status: 'targeting_status',
        };
        Object.entries(map).forEach(([key, id]) => {
            const el = document.getElementById(id);
            if (el && settings[key] !== undefined && settings[key] !== null) {
                el.value = settings[key];
            }
        });
        setMultiSelect('target_locations', settings.target_locations);
        setMultiSelect('excluded_locations', settings.excluded_locations);
        setMultiSelect('languages', settings.languages);
        applyExcludedLocationVisibility();
        ['network_search', 'network_search_partners', 'network_display'].forEach((name) => {
            const el = document.querySelector(`input[name="${name}"][type="checkbox"]`);
            if (el) el.checked = !!settings[name];
        });
        const allMatch = document.querySelector('input[name="all_match_types"]');
        if (allMatch) allMatch.checked = !!settings.all_match_types;
        toggleAdGroupFields();
        syncMaxCpcCurrencyUi();
    }

    function syncMaxCpcCurrencyUi() {
        const currency = document.getElementById('max_cpc_currency');
        const symbol = document.getElementById('max_cpc_symbol');
        const input = document.getElementById('max_cpc');
        if (!currency || !symbol || !input) return;

        const isVnd = currency.value === 'VND';
        symbol.textContent = isVnd ? '₫' : '$';
        input.placeholder = isVnd ? '25000' : '1.50';
        input.inputMode = isVnd ? 'numeric' : 'decimal';
    }

    function bindMaxCpcCurrencyUi() {
        document.getElementById('max_cpc_currency')?.addEventListener('change', syncMaxCpcCurrencyUi);
    }

    function updateExportCsvLink(storeId) {
        document.querySelectorAll('.download-csv-btn').forEach((btn) => {
            if (!storeId) {
                btn.setAttribute('hidden', 'hidden');
                return;
            }
            btn.removeAttribute('hidden');
            btn.href = `${exportCsvBaseUrl}?store_id=${encodeURIComponent(storeId)}`;
        });
        document.querySelectorAll('.download-assets-csv-btn').forEach((btn) => {
            if (!storeId) {
                btn.setAttribute('hidden', 'hidden');
                return;
            }
            btn.removeAttribute('hidden');
            btn.href = `${exportAssetsCsvBaseUrl}?store_id=${encodeURIComponent(storeId)}`;
        });
        document.querySelectorAll('.download-targeting-csv-btn').forEach((btn) => {
            if (!storeId) {
                btn.setAttribute('hidden', 'hidden');
                return;
            }
            btn.removeAttribute('hidden');
            btn.href = `${exportTargetingCsvBaseUrl}?store_id=${encodeURIComponent(storeId)}`;
        });
    }

    function renumberLabels() {
        list.querySelectorAll('.product-row').forEach((row, i) => {
            const label = row.querySelector('label');
            if (label) label.textContent = 'Product ' + (i + 1);
        });
    }

    function bindResultActions(scope) {
        const root = scope || document;
        const copyBtn = root.querySelector('.copy-all-btn');
        const downloadBtn = root.querySelector('.download-txt-btn');
        const exportArea = root.querySelector('.keywords-export');

        copyBtn?.addEventListener('click', async () => {
            if (!exportArea) return;
            try {
                await navigator.clipboard.writeText(exportArea.value);
                copyBtn.textContent = 'Copied!';
                setTimeout(() => { copyBtn.textContent = 'Copy all'; }, 2000);
            } catch (_) {
                exportArea.select();
                document.execCommand('copy');
            }
        });

        downloadBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            if (!exportArea) return;
            const blob = new Blob([exportArea.value], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'keywords.txt';
            a.click();
            URL.revokeObjectURL(url);
        });
    }

    function renderProducts(products) {
        list.innerHTML = '';
        const rows = (products && products.length) ? products : [''];

        rows.forEach((name, index) => {
            const row = document.createElement('div');
            row.className = 'product-row';
            row.innerHTML = `
                <div class="form-group" style="margin-bottom:.75rem;">
                    <label>Product ${index + 1}</label>
                    <div class="import-detect-row">
                        <input type="text" name="products[]" value="${String(name).replace(/"/g, '&quot;')}" maxlength="120" placeholder="e.g. SSD, power bank, USB hub">
                        ${index > 0 ? '<button type="button" class="btn btn-outline btn-sm remove-product-btn">Remove</button>' : ''}
                    </div>
                </div>`;
            list.appendChild(row);
        });

        renumberLabels();
    }

    async function loadSavedKeywords(storeId) {
        if (!storeId) {
            loadStatus.textContent = '';
            resultsWrap.innerHTML = '';
            renderProducts(['']);
            updateExportCsvLink('');
            return;
        }

        loadStatus.textContent = 'Loading saved keywords…';

        try {
            const res = await fetch(`${loadUrl}?store_id=${encodeURIComponent(storeId)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();

            if (!data.ok) {
                loadStatus.textContent = 'Could not load saved keywords.';
                return;
            }

            if (data.found) {
                renderProducts(data.products);
                applyAdsSettings(data.ads_settings);
                resultsWrap.innerHTML = data.results_html;
                bindResultActions(resultsWrap);
                updateExportCsvLink(storeId);
                loadStatus.textContent = `Saved set loaded (updated ${data.saved_at}).`;
            } else {
                renderProducts(['']);
                applyAdsSettings(data.ads_settings);
                resultsWrap.innerHTML = '';
                updateExportCsvLink('');
                loadStatus.textContent = 'No saved keywords for this store yet.';
            }
        } catch (_) {
            loadStatus.textContent = 'Could not load saved keywords.';
        }
    }

    function syncStoreClearButton() {
        if (!storeComboboxClear) return;
        storeComboboxClear.hidden = storeSearchInput.value.trim() === '' && !storeIdInput.value;
    }

    function storeById(id) {
        return stores.find((store) => String(store.id) === String(id)) || null;
    }

    function renderStoreOptions(matches) {
        if (!storeComboboxList) return;

        storeComboboxList.innerHTML = '';

        if (matches.length === 0) {
            const empty = document.createElement('li');
            empty.className = 'store-combobox-empty';
            empty.textContent = 'No stores found.';
            storeComboboxList.appendChild(empty);
            storeComboboxList.hidden = false;
            return;
        }

        matches.forEach((store) => {
            const item = document.createElement('li');
            item.className = 'store-combobox-option';
            item.setAttribute('role', 'option');
            item.dataset.storeId = String(store.id);
            item.textContent = store.name;
            storeComboboxList.appendChild(item);
        });

        storeComboboxList.hidden = false;
    }

    function filterStores(query) {
        const q = query.trim().toLowerCase();

        if (q === '') {
            return stores.slice(0, 50);
        }

        return stores.filter((store) => store.name.toLowerCase().includes(q));
    }

    function selectStore(store) {
        if (!store) return;

        storeIdInput.value = String(store.id);
        storeSearchInput.value = store.name;
        storeComboboxList.hidden = true;
        syncStoreClearButton();
        loadSavedKeywords(store.id);
    }

    function clearStoreSelection() {
        storeIdInput.value = '';
        storeSearchInput.value = '';
        storeComboboxList.hidden = true;
        syncStoreClearButton();
        loadSavedKeywords('');
        storeSearchInput.focus();
    }

    function bindStoreCombobox() {
        if (!storeSearchInput || !storeIdInput) return;

        storeSearchInput.addEventListener('input', () => {
            const selected = storeById(storeIdInput.value);

            if (!selected || selected.name !== storeSearchInput.value.trim()) {
                storeIdInput.value = '';
            }

            renderStoreOptions(filterStores(storeSearchInput.value));
            syncStoreClearButton();
        });

        storeSearchInput.addEventListener('focus', () => {
            renderStoreOptions(filterStores(storeSearchInput.value));
        });

        storeSearchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                storeComboboxList.hidden = true;
                return;
            }

            if (event.key === 'Enter') {
                event.preventDefault();
                const first = storeComboboxList.querySelector('.store-combobox-option');
                if (first) {
                    selectStore(storeById(first.dataset.storeId));
                }
            }
        });

        storeComboboxList?.addEventListener('mousedown', (event) => {
            const option = event.target.closest('.store-combobox-option');
            if (!option) return;
            event.preventDefault();
            selectStore(storeById(option.dataset.storeId));
        });

        storeComboboxClear?.addEventListener('click', () => {
            clearStoreSelection();
        });

        document.addEventListener('click', (event) => {
            if (!storeCombobox?.contains(event.target)) {
                storeComboboxList.hidden = true;
            }
        });
    }

    addBtn?.addEventListener('click', () => {
        const node = tpl.content.cloneNode(true);
        list.appendChild(node);
        renumberLabels();
    });

    list?.addEventListener('click', (e) => {
        if (!e.target.classList.contains('remove-product-btn')) return;
        e.target.closest('.product-row')?.remove();
        if (!list.querySelector('.product-row')) {
            renderProducts(['']);
        }
        renumberLabels();
    });

    bindStoreCombobox();
    bindTargetExcludedSync();
    bindMaxCpcCurrencyUi();
    document.getElementById('ad_group_mode')?.addEventListener('change', toggleAdGroupFields);
    toggleAdGroupFields();
    bindMultiSelectTools();
    applyExcludedLocationVisibility();
    syncMaxCpcCurrencyUi();
    bindResultActions(document);
    syncStoreClearButton();
    if (storeIdInput?.value) {
        updateExportCsvLink(storeIdInput.value);
    }
})();
</script>
@endpush
