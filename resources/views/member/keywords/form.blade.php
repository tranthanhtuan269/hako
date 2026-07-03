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
            <label for="store_id">Store *</label>
            <select id="store_id" name="store_id" required>
                <option value="">— Select store —</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected((int) old('store_id', $selectedStoreId ?? 0) === $store->id)>
                        {{ $store->name }}
                    </option>
                @endforeach
            </select>
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
</style>
@endpush

@push('scripts')
<script>
(function () {
    const list = document.getElementById('product-list');
    const tpl = document.getElementById('product-row-template');
    const addBtn = document.getElementById('add-product-btn');
    const storeSelect = document.getElementById('store_id');
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

    function bindMultiSelectTools() {
        document.querySelectorAll('.keyword-multi-filter').forEach((input) => {
            input.addEventListener('input', () => {
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
                        opt.selected = true;
                    } else if (action === 'clear') {
                        opt.selected = false;
                    } else if (action === 'all-languages') {
                        opt.selected = opt.value === allLanguagesValue;
                    } else if (action === 'each-language') {
                        opt.selected = opt.value !== allLanguagesValue;
                    }
                });
            });
        });
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
        ['network_search', 'network_search_partners', 'network_display'].forEach((name) => {
            const el = document.querySelector(`input[name="${name}"][type="checkbox"]`);
            if (el) el.checked = !!settings[name];
        });
        const allMatch = document.querySelector('input[name="all_match_types"]');
        if (allMatch) allMatch.checked = !!settings.all_match_types;
        toggleAdGroupFields();
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

    storeSelect?.addEventListener('change', () => {
        loadSavedKeywords(storeSelect.value);
    });

    document.getElementById('ad_group_mode')?.addEventListener('change', toggleAdGroupFields);

    toggleAdGroupFields();
    bindMultiSelectTools();
    bindResultActions(document);
    if (storeSelect?.value) {
        updateExportCsvLink(storeSelect.value);
    }
})();
</script>
@endpush
