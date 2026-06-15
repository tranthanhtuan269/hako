@extends('layouts.member')

@section('title', 'Import from Affiliate Link')

@section('content')
<h1 style="margin-bottom:.5rem;">Import from Affiliate Link</h1>
<p style="color:var(--muted);margin-bottom:1.5rem;">
    Paste your affiliate URL and add the offers you want to feature. We detect the store, crawl products, and use AI (Gemini) to write a long-form SEO article during detection — so import is faster.
</p>

@if(session('import_links'))
    <div class="alert alert-success">
        Quick links:
        <div style="margin-top:.75rem;display:flex;flex-wrap:wrap;gap:.75rem;">
            <a href="{{ session('import_links.store') }}" class="btn btn-outline">Edit Store</a>
            <a href="{{ session('import_links.post') }}" class="btn btn-outline">Edit Blog Post</a>
            <a href="{{ session('import_links.coupons') }}" class="btn btn-outline">View Coupons</a>
        </div>
    </div>
@endif

<form method="POST" action="{{ route('member.import-affiliate.store') }}" id="import-form">
    @csrf

    <div class="import-card">
        <h2>Affiliate Link</h2>
        <div class="form-group">
            <label for="affiliate_url">Affiliate URL *</label>
            <div class="import-detect-row">
                <input type="url" id="affiliate_url" name="affiliate_url" value="{{ old('affiliate_url') }}" required placeholder="https://example.com/?ref=your-id">
                <button type="button" class="btn btn-outline detect-store-btn" id="detect-store-btn">
                    <span class="detect-btn-label">Detect Store</span>
                    <span class="detect-btn-busy">
                        <span class="detect-spinner detect-spinner--sm" aria-hidden="true"></span>
                        Detecting…
                    </span>
                </button>
            </div>
            <p class="form-hint">We follow redirects to suggest a logo and category. You enter the store website yourself below.</p>
            @error('affiliate_url')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div id="detect-loading" class="detect-loading" hidden role="status" aria-live="polite">
            <span class="detect-spinner detect-spinner--sm" aria-hidden="true"></span>
            <span id="detect-loading-text">Detecting store &amp; generating AI article…</span>
        </div>

        <div id="merchant-preview" class="merchant-preview" hidden>
            <img id="preview-logo" src="" alt="" class="merchant-preview-logo">
            <div>
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
        <p class="form-hint" style="margin-bottom:.75rem;">Each row becomes one coupon or discount on your site.</p>

        <div id="offers-list" class="offers-list">
            @php($oldOffers = old('offers', [['code' => '', 'title' => '', 'description' => '']]))
            @foreach($oldOffers as $index => $offer)
                @include('member.import-affiliate.partials.offer-block', ['index' => $index, 'offer' => $offer])
            @endforeach
        </div>
        @error('offers')<p class="form-error">{{ $message }}</p>@enderror
        @error('offers.*.title')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="import-card">
        <div class="form-check">
            <input type="checkbox" name="publish" value="1" id="publish" @checked(old('publish'))>
            <label for="publish">Publish store, offers, and blog post immediately</label>
        </div>
        <p class="form-hint">Leave unchecked to save everything as drafts for review first (recommended).</p>
    </div>

    <input type="hidden" name="generated_blog" id="generated_blog" value="{{ old('generated_blog') }}">

    <button type="submit" class="btn btn-primary">Import &amp; Generate Content</button>
    <a href="{{ route('member.dashboard') }}" class="btn btn-outline">Cancel</a>
</form>

<template id="offer-block-template">
    @include('member.import-affiliate.partials.offer-block', ['index' => '__INDEX__', 'offer' => ['code' => '', 'title' => '', 'description' => '']])
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
}
.detect-store-btn.is-loading {
    pointer-events: none;
    opacity: .85;
}
.detect-store-btn.is-loading .detect-btn-label { display: none; }
.detect-store-btn.is-loading .detect-btn-busy { display: inline-flex; }
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
.offer-block {
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: .55rem .65rem;
    margin-bottom: .5rem;
    background: #fafafa;
}
.offer-row {
    display: grid;
    grid-template-columns: 2rem 110px minmax(0, 1fr) 2rem;
    gap: .5rem;
    align-items: end;
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
    margin-left: 2.5rem;
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
        grid-template-columns: 1.75rem 1fr 2rem;
    }
    .offer-field-code {
        grid-column: 2 / 3;
    }
    .offer-field-title {
        grid-column: 2 / 4;
        grid-row: 2;
    }
    .remove-offer-btn {
        grid-column: 3;
        grid-row: 1;
    }
    .offer-field-desc {
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
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const offersList = document.getElementById('offers-list');
    const template = document.getElementById('offer-block-template');
    let offerIndex = offersList.querySelectorAll('.offer-block').length;

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
                input.name = input.name.replace(/offers\[\d+\]/, `offers[${index}]`);
            });
        });
    }

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

    document.getElementById('detect-store-btn').addEventListener('click', async () => {
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
        const generatedBlogInput = document.getElementById('generated_blog');
        const detectBtn = document.getElementById('detect-store-btn');
        const affiliateInput = document.getElementById('affiliate_url');
        const loading = document.getElementById('detect-loading');

        if (!affiliateUrl) {
            status.textContent = 'Enter an affiliate URL first.';
            status.className = 'form-hint detect-status is-error';
            loading.hidden = true;
            return;
        }

        function setDetectLoading(active) {
            detectBtn.classList.toggle('is-loading', active);
            detectBtn.disabled = active;
            affiliateInput.readOnly = active;
            loading.hidden = !active;

            if (active) {
                preview.hidden = true;
                previewProducts.hidden = true;
                previewBlog.hidden = true;
                generatedBlogInput.value = '';
                status.textContent = '';
                status.className = 'form-hint detect-status';
            }
        }

        setDetectLoading(true);
        document.getElementById('detect-loading-text').textContent = 'Detecting store, crawling products & writing AI article…';

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
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                const message = data.message || data.errors?.affiliate_url?.[0] || 'Could not detect store.';
                status.textContent = message;
                status.className = 'form-hint detect-status is-error';
                return;
            }

            const merchant = data.merchant;
            document.getElementById('store_name').value = merchant.name || '';
            if (merchant.domain) {
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

            if (Array.isArray(merchant.products) && merchant.products.length) {
                previewProductsList.innerHTML = merchant.products
                    .map((product) => {
                        const price = product.price ? ` — ${product.price}` : '';
                        return `<li>${product.name}${price}</li>`;
                    })
                    .join('');
                previewProducts.hidden = false;
            } else {
                previewProductsList.innerHTML = '';
                previewProducts.hidden = true;
            }

            if (data.generated_blog && data.generated_blog.title) {
                generatedBlogInput.value = JSON.stringify(data.generated_blog);
                previewBlogTitle.textContent = data.generated_blog.title;
                previewBlogExcerpt.textContent = data.generated_blog.excerpt || '';
                previewBlogSource.textContent = data.generated_blog.source === 'gemini'
                    ? 'Written by Gemini AI during detect — will be saved on import without regenerating.'
                    : 'Template fallback used (AI unavailable). You can still import.';
                previewBlog.hidden = false;
            } else {
                generatedBlogInput.value = '';
                previewBlog.hidden = true;
            }

            preview.hidden = false;
            let statusMessage = merchant.category_name
                ? `Detected store. Suggested category: ${merchant.category_name}.`
                : 'Detected store. Please choose a category if needed.';

            if (Array.isArray(data.suggested_offers) && data.suggested_offers.length) {
                statusMessage += ` Loaded ${data.suggested_offers.length} offer(s) from coupon database.`;
            } else if (data.store_query) {
                statusMessage += ` No matching coupons found for ${data.store_query}.`;
            }

            if (data.generated_blog?.source === 'gemini') {
                statusMessage += ' AI article ready.';
            } else if (data.generated_blog?.source === 'template') {
                statusMessage += ' Blog draft prepared (template fallback).';
            }

            status.textContent = statusMessage;
            status.className = 'form-hint detect-status is-success';
        } catch (error) {
            status.textContent = 'Network error while detecting store. You can still fill the form manually.';
            status.className = 'form-hint detect-status is-error';
        } finally {
            setDetectLoading(false);
        }
    });
})();
</script>
@endpush
