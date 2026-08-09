@extends('layouts.member')

@section('title', 'Keyword Generator')

@section('content')
<h1 style="margin-bottom:.5rem;">Keyword Generator</h1>
<p style="color:var(--muted);margin-bottom:1.5rem;">
    Part of <strong>Google Ads Builder</strong> — generate a standard
    Coupons / Discounts / Promo campaign (with RSA) and export CSV for Google Ads Editor.
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
</form>

<form method="POST" action="{{ route('member.keywords.generate-standard') }}" id="standard-campaign-form" style="margin-top:1.5rem;">
    @csrf
    <input type="hidden" name="store_id" id="standard_store_id" value="{{ old('store_id', $selectedStoreId ?? '') }}">

    <div class="import-card">
        <h2>Standard campaign (Coupons / Discounts / Promo)</h2>
        <p class="form-hint" style="margin-bottom:1rem;">
            Builds Phrase keywords + Responsive Search Ads from the “Chạy ADS Chuẩn” template.
            Uses campaign name (store + today’s date), ad group structure, final URL, match type, CPC and targeting from the settings above.
        </p>
        <div class="keyword-ads-grid">
            <div class="form-group">
                <label for="discount_percent">Discount % for ad copy *</label>
                <input type="number" id="discount_percent" name="discount_percent" min="1" max="90"
                    value="{{ old('discount_percent', $suggestedDiscount ?? 40) }}" required>
                <p class="form-hint">Suggested from store coupons when available. Used in headlines like “Get 40% Off {Store}”.</p>
                @error('discount_percent')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>
        <p class="form-hint" style="margin-bottom:1rem;">
            Syncs campaign/final URL fields from the settings above when you generate. Select a store first.
        </p>
        <button type="submit" class="btn btn-primary" id="generate-standard-btn">Generate standard campaign + RSA</button>
    </div>
</form>

<div id="keyword-results-wrap" hidden></div>

<div id="standard-results-wrap">
    @include('member.keywords.partials.standard-results', [
        'standardCampaign' => $standardCampaign ?? null,
        'brandLabel' => $brandLabel ?? null,
        'exportCampaignUrl' => !empty($selectedStoreId) && !empty($standardCampaign)
            ? route('member.keywords.export-campaign-csv', ['store_id' => $selectedStoreId]) : null,
        'exportKeywordsUrl' => !empty($selectedStoreId) && !empty($standardCampaign)
            ? route('member.keywords.export-standard-keywords-csv', ['store_id' => $selectedStoreId]) : null,
        'exportAdsUrl' => !empty($selectedStoreId) && !empty($standardCampaign)
            ? route('member.keywords.export-standard-ads-csv', ['store_id' => $selectedStoreId]) : null,
        'exportAssetsCsvUrl' => !empty($selectedStoreId) && !empty($standardCampaign)
            ? route('member.keywords.export-assets-csv', ['store_id' => $selectedStoreId]) : null,
        'exportTargetingCsvUrl' => !empty($selectedStoreId) && !empty($standardCampaign)
            ? route('member.keywords.export-targeting-csv', ['store_id' => $selectedStoreId]) : null,
    ])
</div>
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
.keyword-multi-box {
    width: 100%;
    max-width: none;
    max-height: 16rem;
    overflow-y: auto;
    padding: .35rem .45rem;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
    background: #fff;
    display: flex;
    flex-direction: column;
    gap: .1rem;
}
.keyword-multi-option {
    display: flex;
    align-items: center;
    gap: .55rem;
    margin: 0;
    padding: .4rem .45rem;
    border-radius: 6px;
    font-weight: 400;
    cursor: pointer;
    user-select: none;
}
.keyword-multi-option:hover {
    background: #f1f5f9;
}
.keyword-multi-option input {
    width: 1.05rem;
    height: 1.05rem;
    max-width: none;
    margin: 0;
    flex-shrink: 0;
    accent-color: var(--primary, #2563eb);
}
.keyword-multi-option span {
    line-height: 1.3;
}
.keyword-multi-option[hidden] {
    display: none !important;
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
.keyword-cpc-group {
    grid-column: 1 / -1;
    max-width: 28rem;
}
.keyword-money-row {
    grid-column: 1 / -1;
}
.keyword-money-fields {
    display: grid;
    grid-template-columns: minmax(10rem, 1fr) minmax(10rem, 1fr) auto;
    gap: .85rem 1rem;
    align-items: end;
}
.keyword-money-field,
.keyword-money-currency {
    min-width: 0;
}
.keyword-money-currency .keyword-cpc-currency {
    min-width: 8.5rem;
    width: 100%;
    border-radius: 8px;
    border: 1px solid var(--border, #e5e7eb);
    padding: .7rem .75rem;
    font: inherit;
    font-size: 1rem;
    background: #fff;
}
@media (max-width: 720px) {
    .keyword-money-fields {
        grid-template-columns: 1fr;
    }
}
.keyword-cpc-field {
    display: flex;
    align-items: stretch;
    width: 100%;
    gap: 0;
    min-height: 2.75rem;
}
.keyword-cpc-symbol {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 3rem;
    padding: 0 .75rem;
    border: 1px solid var(--border, #e5e7eb);
    border-right: 0;
    border-radius: 8px 0 0 8px;
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    font-size: 1.05rem;
}
.keyword-cpc-field input {
    flex: 1;
    min-width: 8rem;
    padding: .7rem .9rem;
    font-size: 1.05rem;
    border-radius: 0 8px 8px 0;
    border-left: 0;
}
.keyword-money-field .keyword-cpc-field input {
    border-right: 1px solid var(--border, #e5e7eb);
}
.keyword-cpc-currency {
    min-width: 8.5rem;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 0 8px 8px 0;
    padding: .55rem .75rem;
    font: inherit;
    font-size: 1rem;
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
    const storeIdInput = document.getElementById('store_id');
    const storeSearchInput = document.getElementById('store_search');
    const storeCombobox = document.querySelector('[data-store-combobox]');
    const storeComboboxList = document.querySelector('[data-store-combobox-list]');
    const storeComboboxClear = document.querySelector('[data-store-combobox-clear]');
    const stores = @json($stores->map(fn ($store) => ['id' => $store->id, 'name' => $store->name])->values());
    const loadStatus = document.getElementById('store-load-status');
    const loadUrl = @json(route('member.keywords.load'));
    const exportCsvBaseUrl = @json(route('member.keywords.export-csv'));
    const exportAssetsCsvBaseUrl = @json(route('member.keywords.export-assets-csv'));
    const exportTargetingCsvBaseUrl = @json(route('member.keywords.export-targeting-csv'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const allLanguagesValue = @json('All languages');
    const standardResultsWrap = document.getElementById('standard-results-wrap');
    const standardStoreIdInput = document.getElementById('standard_store_id');
    const discountPercentInput = document.getElementById('discount_percent');
    const standardForm = document.getElementById('standard-campaign-form');

    function toggleAdGroupFields() {
        const mode = document.getElementById('ad_group_mode')?.value;
        const singleWrap = document.getElementById('single-ad-group-wrap');
        if (singleWrap) singleWrap.hidden = mode !== 'single';
    }

    function campaignNameForStore(storeName) {
        const today = @json(now()->format('Y-m-d'));
        const name = (storeName || '').trim();
        return name ? `${name} ${today}` : today;
    }

    function syncCampaignName(storeName) {
        const el = document.getElementById('campaign_name');
        if (el) el.value = campaignNameForStore(storeName);
    }

    function setMultiSelect(id, values) {
        const box = document.getElementById(id);
        if (!box || !Array.isArray(values)) return;

        const selectAllLanguages = id === 'languages' && values.includes(allLanguagesValue);
        const selected = new Set(values);

        box.querySelectorAll('input[type="checkbox"]').forEach((input) => {
            input.checked = selectAllLanguages || selected.has(input.value);
        });
    }

    function selectedTargetLocations() {
        const box = document.getElementById('target_locations');
        if (!box) return new Set();

        return new Set(
            Array.from(box.querySelectorAll('input[type="checkbox"]:checked')).map((input) => input.value)
        );
    }

    function applyExcludedLocationVisibility() {
        const box = document.getElementById('excluded_locations');
        if (!box) return;

        const targeted = selectedTargetLocations();
        const filterInput = document.querySelector('.keyword-multi-filter[data-target="excluded_locations"]');
        const q = filterInput?.value.trim().toLowerCase() || '';

        box.querySelectorAll('.keyword-multi-option').forEach((option) => {
            const input = option.querySelector('input[type="checkbox"]');
            if (!input) return;

            if (targeted.has(input.value)) {
                input.checked = false;
                option.hidden = true;
                return;
            }

            const label = option.dataset.label || '';
            option.hidden = q !== '' && !label.includes(q);
        });
    }

    function bindMultiSelectTools() {
        document.querySelectorAll('.keyword-multi-filter').forEach((input) => {
            input.addEventListener('input', () => {
                if (input.dataset.target === 'excluded_locations') {
                    applyExcludedLocationVisibility();
                    return;
                }

                const box = document.getElementById(input.dataset.target);
                if (!box) return;
                const q = input.value.trim().toLowerCase();
                box.querySelectorAll('.keyword-multi-option').forEach((option) => {
                    const label = option.dataset.label || '';
                    option.hidden = q !== '' && !label.includes(q);
                });
            });
        });

        document.querySelectorAll('.keyword-multi-action').forEach((btn) => {
            btn.addEventListener('click', () => {
                const box = document.getElementById(btn.dataset.target);
                if (!box) return;
                const action = btn.dataset.action;

                box.querySelectorAll('.keyword-multi-option').forEach((option) => {
                    const input = option.querySelector('input[type="checkbox"]');
                    if (!input) return;

                    if (action === 'all' || action === 'all-languages') {
                        input.checked = !option.hidden;
                    } else if (action === 'clear') {
                        input.checked = false;
                    }
                });

                if (box.id === 'target_locations') {
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
            ad_group_mode: 'ad_group_mode',
            ad_group_name: 'ad_group_name',
            keyword_status: 'keyword_status',
            budget: 'budget',
            max_cpc: 'max_cpc',
            max_cpc_currency: 'max_cpc_currency',
            final_url: 'final_url',
            targeting_status: 'targeting_status',
        };
        Object.entries(map).forEach(([key, id]) => {
            const el = document.getElementById(id);
            if (el && settings[key] !== undefined && settings[key] !== null) {
                let value = settings[key];
                if (key === 'ad_group_mode' && value === 'brand_and_products') {
                    value = 'standard';
                }
                el.value = value;
            }
        });
        // Always refresh campaign name to store + today's date.
        syncCampaignName(storeSearchInput?.value || '');
        const matchType = document.getElementById('match_type');
        if (matchType) matchType.value = 'Phrase';
        setMultiSelect('target_locations', settings.target_locations);
        setMultiSelect('excluded_locations', settings.excluded_locations);
        setMultiSelect('languages', settings.languages);
        applyExcludedLocationVisibility();
        ['network_search', 'network_search_partners', 'network_display'].forEach((name) => {
            const el = document.querySelector(`input[name="${name}"][type="checkbox"]`);
            if (el) el.checked = !!settings[name];
        });
        toggleAdGroupFields();
        syncMaxCpcCurrencyUi();
    }

    function syncMaxCpcCurrencyUi() {
        const currency = document.getElementById('max_cpc_currency');
        const symbol = document.getElementById('max_cpc_symbol');
        const budgetSymbol = document.getElementById('budget_symbol');
        const input = document.getElementById('max_cpc');
        const budget = document.getElementById('budget');
        if (!currency) return;

        const isVnd = currency.value === 'VND';
        if (symbol) symbol.textContent = isVnd ? '₫' : '$';
        if (budgetSymbol) budgetSymbol.textContent = isVnd ? '₫' : '$';
        if (input) {
            input.placeholder = isVnd ? '25000' : '1.50';
            input.inputMode = isVnd ? 'numeric' : 'decimal';
        }
        if (budget) {
            budget.placeholder = isVnd ? '250000' : '10.00';
            budget.inputMode = isVnd ? 'numeric' : 'decimal';
        }
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

    async function loadSavedKeywords(storeId) {
        if (standardStoreIdInput) {
            standardStoreIdInput.value = storeId || '';
        }

        if (!storeId) {
            loadStatus.textContent = '';
            if (standardResultsWrap) standardResultsWrap.innerHTML = '';
            updateExportCsvLink('');
            return;
        }

        loadStatus.textContent = 'Loading store settings…';

        try {
            const res = await fetch(`${loadUrl}?store_id=${encodeURIComponent(storeId)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();

            if (!data.ok) {
                loadStatus.textContent = 'Could not load store settings.';
                return;
            }

            if (discountPercentInput && data.suggested_discount) {
                discountPercentInput.value = data.suggested_discount;
            }

            if (standardResultsWrap) {
                standardResultsWrap.innerHTML = data.standard_html || '';
            }

            applyAdsSettings(data.ads_settings);
            updateExportCsvLink(data.found ? storeId : '');
            loadStatus.textContent = data.found
                ? `Saved campaign loaded (updated ${data.saved_at}).`
                : 'No saved campaign for this store yet.';
        } catch (_) {
            loadStatus.textContent = 'Could not load store settings.';
        }
    }

    function copyAdsSettingsIntoStandardForm() {
        if (!standardForm) return;

        const fieldIds = [
            'campaign_name', 'final_url', 'ad_group_mode', 'ad_group_name',
            'match_type', 'keyword_status', 'budget', 'max_cpc', 'max_cpc_currency', 'targeting_status',
        ];

        fieldIds.forEach((id) => {
            const source = document.getElementById(id);
            if (!source) return;
            let hidden = standardForm.querySelector(`input[name="${id}"]`);
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = id;
                standardForm.appendChild(hidden);
            }
            hidden.value = source.value;
        });

        ['network_search', 'network_search_partners', 'network_display'].forEach((name) => {
            const source = document.querySelector(`#keyword-form input[name="${name}"][type="checkbox"]`);
            let hidden = standardForm.querySelector(`input[name="${name}"]`);
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = name;
                standardForm.appendChild(hidden);
            }
            hidden.value = source && source.checked ? '1' : '0';
        });

        let allMatchHidden = standardForm.querySelector('input[name="all_match_types"]');
        if (!allMatchHidden) {
            allMatchHidden = document.createElement('input');
            allMatchHidden.type = 'hidden';
            allMatchHidden.name = 'all_match_types';
            standardForm.appendChild(allMatchHidden);
        }
        allMatchHidden.value = '0';

        ['target_locations', 'excluded_locations', 'languages'].forEach((name) => {
            standardForm.querySelectorAll(`input[name="${name}[]"]`).forEach((el) => el.remove());
            const box = document.getElementById(name);
            if (!box) return;
            box.querySelectorAll('input[type="checkbox"]:checked').forEach((input) => {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = `${name}[]`;
                hidden.value = input.value;
                standardForm.appendChild(hidden);
            });
        });

        if (standardStoreIdInput) {
            standardStoreIdInput.value = storeIdInput.value || '';
        }
    }

    standardForm?.addEventListener('submit', function (event) {
        if (!storeIdInput.value) {
            event.preventDefault();
            loadStatus.textContent = 'Select a store before generating the standard campaign.';
            storeSearchInput?.focus();
            return;
        }
        copyAdsSettingsIntoStandardForm();
    });

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
        syncCampaignName(store.name);
        loadSavedKeywords(store.id);
    }

    function clearStoreSelection() {
        storeIdInput.value = '';
        storeSearchInput.value = '';
        storeComboboxList.hidden = true;
        syncStoreClearButton();
        syncCampaignName('');
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

    bindStoreCombobox();
    bindTargetExcludedSync();
    bindMaxCpcCurrencyUi();
    document.getElementById('ad_group_mode')?.addEventListener('change', toggleAdGroupFields);
    toggleAdGroupFields();
    bindMultiSelectTools();
    applyExcludedLocationVisibility();
    syncMaxCpcCurrencyUi();
    syncStoreClearButton();
    if (storeIdInput?.value) {
        updateExportCsvLink(storeIdInput.value);
    }
})();
</script>
@endpush
