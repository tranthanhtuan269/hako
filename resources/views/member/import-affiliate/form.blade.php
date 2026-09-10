@extends('layouts.member')

@section('title', 'Import from Affiliate Link')

@section('content')
<h1 style="margin-bottom:.5rem;">Import from Affiliate Link</h1>
<p style="color:var(--muted);margin-bottom:1.5rem;">
    Paste your affiliate URL and add the offers you want to feature. We detect the store, crawl products, and use AI to write a long-form SEO article during detection — so import is faster.
</p>

@if(session('import_links'))
    <div class="alert alert-success">
        <strong>Quick links</strong>
        <div style="margin-top:.75rem;display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;">
            <a href="{{ session('import_links.store_public') }}" class="btn btn-primary" target="_blank" rel="noopener">View Store Page</a>
            <button
                type="button"
                class="btn btn-outline js-copy-link"
                data-copy-url="{{ session('import_links.store_public') }}"
                data-copy-title="Copy store link"
            >Copy Store Link</button>
            <a href="{{ session('import_links.store') }}" class="btn btn-outline">Edit Store</a>
            <a href="{{ session('import_links.post') }}" class="btn btn-outline">Edit Blog Post</a>
            <a href="{{ session('import_links.coupons') }}" class="btn btn-outline">View Coupons</a>
        </div>
        <p class="form-hint" style="margin:.65rem 0 0;">
            Store URL: <a href="{{ session('import_links.store_public') }}" target="_blank" rel="noopener">{{ session('import_links.store_public') }}</a>
        </p>
    </div>
@endif

@include('partials.table-actions-assets')

<form method="POST" action="{{ route('member.import-affiliate.store') }}" id="import-form">
    @csrf

    <div class="import-card">
        <h2>Affiliate Link</h2>
        <div class="form-group">
            <label for="affiliate_url">Affiliate URL *</label>
            <input type="hidden" name="product_focus" id="product_focus" value="{{ old('product_focus', '0') }}">
            <div class="import-detect-row">
                <input type="url" id="affiliate_url" name="affiliate_url" value="{{ old('affiliate_url') }}" required placeholder="https://example.com/?ref=your-id">
                <button type="button" class="btn btn-outline detect-store-btn" id="detect-store-btn" data-product-focus="0">
                    <span class="detect-btn-label">Detect Store</span>
                    <span class="detect-btn-busy">
                        <span class="detect-spinner detect-spinner--sm" aria-hidden="true"></span>
                        Detecting…
                    </span>
                </button>
                <button type="button" class="btn btn-outline detect-store-btn" id="detect-product-btn" data-product-focus="1">
                    <span class="detect-btn-label">Detect Only Product</span>
                    <span class="detect-btn-busy">
                        <span class="detect-spinner detect-spinner--sm" aria-hidden="true"></span>
                        Detecting…
                    </span>
                </button>
            </div>
            <p class="form-hint">
                <strong>Detect Store</strong> crawls the catalog for comparison posts.
                <strong>Detect Only Product</strong> is for marketplaces like Amazon — extracts only the linked product and writes a single-product review.
            </p>
            @error('affiliate_url')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div id="detect-loading" class="detect-loading" hidden role="status" aria-live="polite">
            <span class="detect-spinner detect-spinner--sm" aria-hidden="true"></span>
            <span id="detect-loading-text">Detecting store &amp; generating AI article…</span>
        </div>

        <div id="merchant-preview" class="merchant-preview" hidden>
            <img id="preview-logo" src="" alt="" class="merchant-preview-logo">
            <div>
                <div id="preview-existing-import" class="preview-existing-import" hidden>
                    <strong>Existing import found</strong>
                    <p id="preview-existing-import-text" class="form-hint" style="margin:.35rem 0 0;"></p>
                </div>
                <strong id="preview-name"></strong>
                <p id="preview-domain" class="form-hint" style="margin:.25rem 0 0;"></p>
                <p id="preview-meta" class="form-hint" style="margin:.35rem 0 0;"></p>
                <div id="preview-products" class="preview-products" hidden>
                    <strong>Products for comparison article</strong>
                    <ul id="preview-products-list"></ul>
                </div>
                <div id="preview-blog" class="preview-blog" hidden>
                    <strong>AI blog preview</strong>
                    <p id="preview-blog-title" class="preview-blog-title"></p>
                    <p id="preview-blog-excerpt" class="form-hint" style="margin:.35rem 0 0;"></p>
                    <p id="preview-blog-source" class="form-hint" style="margin:.25rem 0 0;"></p>
                </div>
            </div>
        </div>
        <p id="detect-status" class="form-hint detect-status" style="margin-top:.5rem;"></p>
    </div>

    <div class="import-card">
        <h2>Store Details</h2>
        <div class="form-group">
            <label for="store_name">Store name *</label>
            <input type="text" id="store_name" name="store_name" value="{{ old('store_name') }}" required placeholder="Detected after you click Detect Store">
            @error('store_name')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label for="website">Store website</label>
            <input type="url" id="website" name="website" value="{{ old('website') }}" placeholder="https://example.com">
            <p class="form-hint">Public store link shown on your store page. Enter the real merchant site — not the affiliate tracking URL. Also used to pull products, FAQs, and comparison content for the blog.</p>
            @error('website')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label for="logo_url">Logo URL</label>
            <div class="import-detect-row">
                <input type="url" id="logo_url" name="logo_url" value="{{ old('logo_url') }}" placeholder="Detected logo URL — edit if wrong">
                <img id="logo_url_preview" src="{{ old('logo_url') }}" alt="" class="merchant-preview-logo" @if(!old('logo_url')) hidden @endif>
            </div>
            <p class="form-hint">We pull the logo from the merchant site and save it on this server. Paste a different image URL if the detected one is wrong.</p>
            @error('logo_url')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">— Select category —</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="import-card">
        <div class="import-card-header">
            <h2>Offers</h2>
            <button type="button" class="btn btn-outline" id="add-offer-btn">+ Add Offer</button>
        </div>
        <p class="form-hint" style="margin-bottom:.75rem;">Each row becomes one coupon or discount on your site. Drag ⠿ to change display order.</p>

        <div id="offers-list" class="offers-list">
            @php($oldOffers = old('offers', [['code' => '', 'title' => '', 'description' => '', 'expires_at' => '']]))
            @foreach($oldOffers as $index => $offer)
                @include('member.import-affiliate.partials.offer-block', ['index' => $index, 'offer' => $offer])
            @endforeach
        </div>
        @error('offers')<p class="form-error">{{ $message }}</p>@enderror
        @error('offers.*.title')<p class="form-error">{{ $message }}</p>@enderror
        @error('offers.*.expires_at')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <input type="hidden" name="generated_blog" id="generated_blog" value="{{ old('generated_blog') }}">

    <button type="submit" class="btn btn-primary" id="import-submit-btn">Import &amp; Generate Content</button>
    <a href="{{ route('member.dashboard') }}" class="btn btn-outline">Cancel</a>
</form>

<template id="offer-block-template">
    @include('member.import-affiliate.partials.offer-block', ['index' => '__INDEX__', 'offer' => ['code' => '', 'title' => '', 'description' => '', 'expires_at' => '']])
</template>
@endsection

@push('styles')
<style>
.import-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem;
    margin-bottom: 1.25rem;
}
.import-card h2 {
    margin: 0 0 1rem;
    font-size: 1.1rem;
}
.import-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-bottom: .5rem;
}
.import-card-header h2 { margin: 0; }
.import-detect-row {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
}
.import-detect-row input { flex: 1; min-width: 240px; }
.detect-store-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    min-width: 8.5rem;
    white-space: nowrap;
}
.detect-store-btn.is-loading {
    pointer-events: none;
    opacity: .85;
}
.detect-store-btn.is-loading .detect-btn-label { display: none; }
.detect-store-btn.is-loading .detect-btn-busy { display: inline-flex; }
.detect-store-btn.is-disabled-peer {
    pointer-events: none;
    opacity: .55;
}
.detect-btn-busy {
    display: none;
    align-items: center;
    gap: .45rem;
}
.detect-loading {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    margin-top: .75rem;
    font-size: .875rem;
    color: var(--muted);
}
.detect-loading[hidden] {
    display: none !important;
}
.detect-spinner {
    width: 1rem;
    height: 1rem;
    border: 2px solid #e2e8f0;
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: detect-spin .75s linear infinite;
    flex-shrink: 0;
}
.detect-spinner--sm {
    width: .95rem;
    height: .95rem;
    border-width: 2px;
    margin-top: 0;
}
@keyframes detect-spin {
    to { transform: rotate(360deg); }
}
.detect-status.is-error { color: #dc2626; }
.detect-status.is-success { color: #047857; }
.merchant-preview {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border: 1px solid var(--border);
    border-radius: var(--radius);
}
.merchant-preview-logo {
    width: 56px;
    height: 56px;
    object-fit: contain;
    border-radius: 10px;
    background: #fff;
    border: 1px solid var(--border);
}
.preview-products {
    margin-top: .75rem;
    font-size: .88rem;
}
.preview-products ul {
    margin: .4rem 0 0;
    padding-left: 1.1rem;
    color: #334155;
}
.preview-products li { margin-bottom: .2rem; }
.preview-blog {
    margin-top: .85rem;
    padding-top: .75rem;
    border-top: 1px dashed var(--border);
    font-size: .88rem;
}
.preview-blog-title {
    margin: .35rem 0 0;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
    overflow: hidden;
}
.preview-existing-import {
    margin-bottom: .75rem;
    padding: .65rem .75rem;
    border-radius: 8px;
    background: #fffbeb;
    border: 1px solid #fcd34d;
    color: #92400e;
    font-size: .88rem;
}
.preview-existing-import strong {
    display: block;
    color: #78350f;
}
.preview-existing-import.is-blocked {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #991b1b;
}
.preview-existing-import.is-blocked strong {
    color: #7f1d1d;
}
#import-submit-btn:disabled {
    cursor: not-allowed;
    opacity: .65;
}
.offer-block {
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: .55rem .65rem;
    margin-bottom: .5rem;
    background: #fafafa;
    transition: box-shadow .15s ease, border-color .15s ease, opacity .15s ease;
}
.offer-block.is-dragging {
    opacity: .65;
    border-color: var(--primary);
    box-shadow: 0 8px 20px rgba(15, 23, 42, .12);
}
.offer-block.is-drop-target {
    border-color: var(--primary);
}
.offer-row {
    display: grid;
    grid-template-columns: 1.5rem 2rem 110px minmax(0, 1fr) 2rem;
    gap: .5rem;
    align-items: end;
}
.offer-sort-handle {
    appearance: none;
    border: 0;
    background: transparent;
    color: #94a3b8;
    cursor: grab;
    font-size: 1.05rem;
    line-height: 1;
    padding: 0 0 .45rem;
    text-align: center;
    user-select: none;
}
.offer-sort-handle:active {
    cursor: grabbing;
}
.offer-num {
    font-size: .8rem;
    font-weight: 700;
    color: var(--muted);
    padding-bottom: .45rem;
    text-align: center;
}
.offer-field label {
    display: block;
    font-size: .72rem;
    font-weight: 600;
    color: var(--muted);
    margin-bottom: .2rem;
    text-transform: uppercase;
    letter-spacing: .02em;
}
.offer-field input,
.offer-field textarea {
    width: 100%;
    padding: .4rem .55rem;
    font-size: .875rem;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: #fff;
}
.offer-field-desc {
    margin-top: .45rem;
    margin-left: 3.5rem;
}
.offer-meta-row {
    margin-top: .45rem;
    margin-left: 3.5rem;
}
.offer-field-expires input {
    max-width: 16rem;
}
.offer-field-desc textarea {
    resize: vertical;
    min-height: 2.5rem;
}
.offer-field-title input {
    font-weight: 600;
}
.remove-offer-btn {
    padding: 0 !important;
    width: 2rem;
    height: 2rem;
    line-height: 1;
    font-size: 1.15rem;
    align-self: end;
    margin-bottom: .05rem;
}
@media (max-width: 640px) {
    .offer-row {
        grid-template-columns: 1.25rem 1.5rem 1fr 2rem;
    }
    .offer-field-code {
        grid-column: 3 / 4;
    }
    .offer-field-title {
        grid-column: 2 / 5;
        grid-row: 2;
    }
    .remove-offer-btn {
        grid-column: 4;
        grid-row: 1;
    }
    .offer-field-desc {
        margin-left: 0;
    }
    .offer-meta-row {
        margin-left: 0;
    }
}
.form-error { color: #dc2626; font-size: .875rem; margin-top: .35rem; }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const previewUrl = @json(route('member.import-affiliate.preview'));
    const integrationsUrl = @json(auth()->user()->isAdmin() ? route('admin.integrations.index') : null);
    const allowReimportExistingStores = @json($allowReimportExistingStores);
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const offersList = document.getElementById('offers-list');
    const template = document.getElementById('offer-block-template');
    const importForm = document.getElementById('import-form');
    const importSubmitBtn = document.getElementById('import-submit-btn');
    let offerIndex = offersList.querySelectorAll('.offer-block').length;

    importForm.addEventListener('submit', (event) => {
        if (importSubmitBtn.disabled) {
            event.preventDefault();
            return;
        }

        importSubmitBtn.disabled = true;
        importSubmitBtn.textContent = 'Importing…';
    });

    function setImportSubmitEnabled(enabled) {
        importSubmitBtn.disabled = !enabled;
        importSubmitBtn.title = enabled
            ? ''
            : 'Re-importing existing stores is disabled in site settings.';
    }

    function updateExistingImportNotice(existing, importBlocked) {
        const previewExistingImport = document.getElementById('preview-existing-import');
        const previewExistingImportText = document.getElementById('preview-existing-import-text');

        if (!existing) {
            previewExistingImport.hidden = true;
            previewExistingImport.classList.remove('is-blocked');
            previewExistingImportText.textContent = '';
            setImportSubmitEnabled(true);
            return;
        }

        const postLabel = existing.post_title
            ? `post “${existing.post_title}”`
            : 'an existing post';

        if (importBlocked) {
            let message =
                `This merchant is already imported as “${existing.store_name}”. `
                + 'Re-importing existing stores is disabled, so import is blocked.';

            if (integrationsUrl) {
                message += ` Enable “Allow re-importing existing stores” in Integrations settings to update that store.`;
            } else {
                message += ' Ask your site admin to enable re-importing in Integrations settings.';
            }

            previewExistingImportText.textContent = message;
            previewExistingImport.classList.add('is-blocked');
            setImportSubmitEnabled(false);
        } else {
            previewExistingImportText.textContent =
                `This merchant is already imported as “${existing.store_name}”. `
                + `Submitting will update that store, replace its offers, and refresh ${postLabel} instead of creating duplicates.`;
            previewExistingImport.classList.remove('is-blocked');
            setImportSubmitEnabled(true);
        }

        previewExistingImport.hidden = false;
    }

    document.getElementById('add-offer-btn').addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__INDEX__', String(offerIndex++));
        offersList.insertAdjacentHTML('beforeend', html);
        renumberOffers();
    });

    offersList.addEventListener('click', (event) => {
        if (event.target.classList.contains('remove-offer-btn')) {
            const blocks = offersList.querySelectorAll('.offer-block');
            if (blocks.length <= 1) {
                alert('At least one offer is required.');
                return;
            }
            event.target.closest('.offer-block').remove();
            renumberOffers();
        }
    });

    function renumberOffers() {
        offersList.querySelectorAll('.offer-block').forEach((block, index) => {
            block.dataset.index = index;
            block.querySelector('.offer-block-number').textContent = index + 1;
            block.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace(/offers\[(?:\d+|__INDEX__)\]/, `offers[${index}]`);
            });
        });
    }

    let dragOffer = null;

    function clearOfferDropTargets() {
        offersList.querySelectorAll('.offer-block.is-drop-target').forEach((block) => {
            block.classList.remove('is-drop-target');
        });
    }

    offersList.addEventListener('mousedown', (event) => {
        const handle = event.target.closest('.offer-sort-handle');
        if (!handle) {
            return;
        }

        const block = handle.closest('.offer-block');
        if (block) {
            block.draggable = true;
        }
    });

    offersList.addEventListener('mouseup', (event) => {
        const block = event.target.closest('.offer-block');
        if (block) {
            block.draggable = false;
        }
    });

    offersList.addEventListener('dragstart', (event) => {
        const block = event.target.closest('.offer-block');
        if (!block || !block.draggable) {
            return;
        }

        dragOffer = block;
        block.classList.add('is-dragging');

        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', block.dataset.index || '');
        }
    });

    offersList.addEventListener('dragend', (event) => {
        const block = event.target.closest('.offer-block');
        if (block) {
            block.draggable = false;
            block.classList.remove('is-dragging');
        }
        clearOfferDropTargets();
        dragOffer = null;
        renumberOffers();
    });

    offersList.addEventListener('dragover', (event) => {
        const block = event.target.closest('.offer-block');
        if (!dragOffer || !block || dragOffer === block) {
            return;
        }

        event.preventDefault();

        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }

        clearOfferDropTargets();
        block.classList.add('is-drop-target');

        const rect = block.getBoundingClientRect();
        const after = event.clientY > rect.top + rect.height / 2;

        if (after) {
            block.after(dragOffer);
        } else {
            block.before(dragOffer);
        }
    });

    offersList.addEventListener('dragleave', (event) => {
        const block = event.target.closest('.offer-block');
        if (block) {
            block.classList.remove('is-drop-target');
        }
    });

    offersList.addEventListener('drop', (event) => {
        event.preventDefault();
        clearOfferDropTargets();
        renumberOffers();
    });

    function fillSuggestedOffers(offers) {
        offersList.innerHTML = '';
        offerIndex = 0;

        offers.forEach((offer) => {
            const html = template.innerHTML.replaceAll('__INDEX__', String(offerIndex++));
            offersList.insertAdjacentHTML('beforeend', html);

            const block = offersList.lastElementChild;
            block.querySelector('[name*="[code]"]').value = offer.code || '';
            block.querySelector('[name*="[title]"]').value = offer.title || '';
            block.querySelector('[name*="[description]"]').value = offer.description || '';
            const expiresInput = block.querySelector('[name*="[expires_at]"]');
            if (expiresInput) {
                expiresInput.value = offer.expires_at || '';
            }
        });

        renumberOffers();
    }

    document.getElementById('logo_url').addEventListener('input', (event) => {
        const logoPreview = document.getElementById('logo_url_preview');
        const previewLogo = document.getElementById('preview-logo');
        const value = event.target.value.trim();

        if (!value) {
            logoPreview.hidden = true;
            return;
        }

        logoPreview.src = value;
        logoPreview.hidden = false;
        previewLogo.src = value;
    });

    async function runDetect(productFocus) {
        const affiliateUrl = document.getElementById('affiliate_url').value.trim();
        const websiteUrl = document.getElementById('website').value.trim();
        const status = document.getElementById('detect-status');
        const preview = document.getElementById('merchant-preview');
        const previewProducts = document.getElementById('preview-products');
        const previewProductsList = document.getElementById('preview-products-list');
        const previewBlog = document.getElementById('preview-blog');
        const previewBlogTitle = document.getElementById('preview-blog-title');
        const previewBlogExcerpt = document.getElementById('preview-blog-excerpt');
        const previewBlogSource = document.getElementById('preview-blog-source');
        const previewExistingImport = document.getElementById('preview-existing-import');
        const generatedBlogInput = document.getElementById('generated_blog');
        const productFocusInput = document.getElementById('product_focus');
        const detectStoreBtn = document.getElementById('detect-store-btn');
        const detectProductBtn = document.getElementById('detect-product-btn');
        const activeBtn = productFocus ? detectProductBtn : detectStoreBtn;
        const peerBtn = productFocus ? detectStoreBtn : detectProductBtn;
        const affiliateInput = document.getElementById('affiliate_url');
        const loading = document.getElementById('detect-loading');

        if (!affiliateUrl) {
            status.textContent = 'Enter an affiliate URL first.';
            status.className = 'form-hint detect-status is-error';
            loading.hidden = true;
            return;
        }

        productFocusInput.value = productFocus ? '1' : '0';

        function setDetectLoading(active) {
            activeBtn.classList.toggle('is-loading', active);
            activeBtn.disabled = active;
            peerBtn.classList.toggle('is-disabled-peer', active);
            peerBtn.disabled = active;
            affiliateInput.readOnly = active;
            loading.hidden = !active;

            if (active) {
                preview.hidden = true;
                previewProducts.hidden = true;
                previewBlog.hidden = true;
                previewExistingImport.hidden = true;
                previewExistingImport.classList.remove('is-blocked');
                generatedBlogInput.value = '';
                status.textContent = '';
                status.className = 'form-hint detect-status';
                setImportSubmitEnabled(true);
            }
        }

        setDetectLoading(true);
        document.getElementById('detect-loading-text').textContent = productFocus
            ? 'Detecting linked product & writing focused AI article…'
            : 'Detecting store, crawling products & writing AI article…';

        try {
            const response = await fetch(previewUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    affiliate_url: affiliateUrl,
                    website: websiteUrl || null,
                    product_focus: productFocus ? 1 : 0,
                }),
            });

            let data = {};
            try {
                data = await response.json();
            } catch (_) {
                status.textContent = 'Detect failed with an unexpected server response. You can still fill the form manually.';
                status.className = 'form-hint detect-status is-error';
                return;
            }

            if (!response.ok || data.ok === false) {
                const message = data.message || data.errors?.affiliate_url?.[0] || 'Could not detect store.';
                status.textContent = message;
                status.className = 'form-hint detect-status is-error';
                return;
            }

            const merchant = data.merchant;
            document.getElementById('store_name').value = merchant.name || '';
            if (data.product_focus && merchant.final_url) {
                document.getElementById('website').value = merchant.final_url;
            } else if (merchant.domain) {
                document.getElementById('website').value = `https://${merchant.domain}`;
            }
            if (merchant.category_id) {
                document.getElementById('category_id').value = merchant.category_id;
            }

            if (Array.isArray(data.suggested_offers) && data.suggested_offers.length) {
                fillSuggestedOffers(data.suggested_offers);
            }

            if (merchant.logo) {
                document.getElementById('preview-logo').src = merchant.logo;
                document.getElementById('preview-logo').alt = merchant.name || 'Store logo';
                document.getElementById('logo_url').value = merchant.logo;
                const logoPreview = document.getElementById('logo_url_preview');
                logoPreview.src = merchant.logo;
                logoPreview.hidden = false;
            }
            document.getElementById('preview-name').textContent = merchant.name || 'Unknown store';
            document.getElementById('preview-domain').textContent = merchant.domain
                ? `Domain: ${merchant.domain}`
                : '';
            document.getElementById('preview-meta').textContent = merchant.meta_description || merchant.page_title || '';

            if (data.existing_import) {
                updateExistingImportNotice(
                    data.existing_import,
                    !!data.import_blocked,
                );
            } else {
                updateExistingImportNotice(null, false);
            }

            if (Array.isArray(merchant.products) && merchant.products.length) {
                previewProductsList.innerHTML = merchant.products
                    .map((product) => {
                        const price = product.price ? ` — ${product.price}` : '';
                        const image = product.image
                            ? `<img src="${product.image}" alt="" style="width:36px;height:36px;object-fit:contain;border-radius:6px;border:1px solid var(--border);vertical-align:middle;margin-right:.4rem;background:#fff;">`
                            : '';
                        return `<li style="display:flex;align-items:center;gap:.35rem;margin-bottom:.35rem;">${image}<span>${product.name}${price}</span></li>`;
                    })
                    .join('');
                previewProducts.querySelector('strong').textContent = data.product_focus
                    ? 'Product for focused article'
                    : 'Products for comparison article';
                previewProducts.hidden = false;
            } else {
                previewProductsList.innerHTML = '<li style="color:#64748b;">No catalog products found. The article will use brand details, FAQs, and offers instead.</li>';
                previewProducts.querySelector('strong').textContent = 'Products';
                previewProducts.hidden = false;
            }

            if (data.generated_blog && data.generated_blog.title) {
                generatedBlogInput.value = JSON.stringify(data.generated_blog);
                previewBlogTitle.textContent = data.generated_blog.title;
                previewBlogExcerpt.textContent = data.generated_blog.excerpt || '';
                previewBlogSource.textContent = data.generated_blog.source && data.generated_blog.source !== 'template'
                    ? 'Written by AI during detect — will be saved on import without regenerating.'
                    : 'Template fallback used (AI unavailable). You can still import.';
                previewBlog.hidden = false;
            } else {
                generatedBlogInput.value = '';
                previewBlog.hidden = true;
            }

            preview.hidden = false;
            let statusMessage = data.detect_source === 'scan_cache'
                ? 'Loaded store profile from scan cache (fast).'
                : (merchant.category_name
                ? `Detected store. Suggested category: ${merchant.category_name}.`
                : 'Detected store. Please choose a category if needed.');

            if (Array.isArray(data.suggested_offers) && data.suggested_offers.length) {
                const fromPricing = Number(data.pricing_offers_found || 0) > 0
                    && (!Array.isArray(merchant.products) || merchant.products.length === 0);
                statusMessage += fromPricing
                    ? ` Loaded ${data.suggested_offers.length} offer(s) from pricing page.`
                    : ` Loaded ${data.suggested_offers.length} offer(s) from coupon database.`;
            } else if (data.store_query) {
                statusMessage += ` No matching coupons found for ${data.store_query}.`;
            }

            if (data.generated_blog?.source && data.generated_blog.source !== 'template') {
                statusMessage += ' AI article ready.';
            } else if (data.generated_blog?.source === 'template') {
                statusMessage += ' Blog draft prepared (template fallback).';
            }

            if (data.product_focus) {
                statusMessage += ' Product-focus mode: writing about the linked product only.';
            }

            if (!Array.isArray(merchant.products) || merchant.products.length === 0) {
                statusMessage += ' No catalog products found — using brand/offers content.';
            }

            if (data.import_blocked) {
                statusMessage += ' Import blocked — this store already exists.';
                status.className = 'form-hint detect-status is-error';
            } else if (data.existing_import) {
                statusMessage += ' Existing store will be updated on import.';
                status.className = 'form-hint detect-status is-success';
            } else {
                status.className = 'form-hint detect-status is-success';
            }

            status.textContent = statusMessage;
        } catch (error) {
            status.textContent = 'Network error while detecting store. You can still fill the form manually.';
            status.className = 'form-hint detect-status is-error';
        } finally {
            setDetectLoading(false);
        }
    }

    document.getElementById('detect-store-btn').addEventListener('click', () => runDetect(false));
    document.getElementById('detect-product-btn').addEventListener('click', () => runDetect(true));
})();
</script>
@endpush
