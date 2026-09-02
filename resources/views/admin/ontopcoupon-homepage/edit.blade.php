@extends('layouts.admin')

@section('title', 'OnTopCoupon Homepage')

@section('content')
<h1 style="margin-bottom:.5rem;">OnTopCoupon Homepage</h1>
<p style="color:#64748b;margin-bottom:2rem;">
    Control the blocks and copy on the OnTopCoupon theme homepage.
    Hero H1 / subtitle stay in
    <a href="{{ route('admin.branding.index') }}">Logo &amp; Social</a>.
    Coupons, stores, categories, and blog posts still come from Content.
</p>
<p class="form-hint" style="margin-bottom:1.5rem;">
    In body text you can use <code>{coupons}</code>, <code>{stores}</code>, and <code>{categories}</code> — they are replaced with live counts.
</p>

<form action="{{ route('admin.ontopcoupon-homepage.update') }}" method="POST">
    @csrf
    @method('PUT')

    <div class="branding-section">
        <h2>Visible sections</h2>
        <p class="form-hint" style="margin-bottom:1rem;">Uncheck a block to hide it on the public homepage.</p>
        <div class="otc-admin-toggles">
            @foreach($sectionLabels as $key => $label)
                <label class="form-check">
                    <input type="checkbox" name="sections[{{ $key }}]" value="1" @checked(old("sections.$key", $homepage['sections'][$key] ?? true))>
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="branding-section">
        <h2>Hero search &amp; trust strip</h2>
        <div class="form-group">
            <label for="search_placeholder">Search placeholder</label>
            <input type="text" id="search_placeholder" name="search_placeholder" maxlength="160"
                value="{{ old('search_placeholder', $homepage['search_placeholder']) }}">
        </div>
        <div class="form-group">
            <label for="trust_label">Trusted stores label</label>
            <input type="text" id="trust_label" name="trust_label" maxlength="120"
                value="{{ old('trust_label', $homepage['trust_label']) }}">
        </div>
    </div>

    <div class="branding-section">
        <h2>Featured coupons</h2>
        <div class="otc-admin-grid">
            <div class="form-group">
                <label for="featured_coupons_kicker">Kicker</label>
                <input type="text" id="featured_coupons_kicker" name="featured_coupons[kicker]" maxlength="80"
                    value="{{ old('featured_coupons.kicker', $homepage['featured_coupons']['kicker']) }}">
            </div>
            <div class="form-group">
                <label for="featured_coupons_title">Title</label>
                <input type="text" id="featured_coupons_title" name="featured_coupons[title]" maxlength="120"
                    value="{{ old('featured_coupons.title', $homepage['featured_coupons']['title']) }}">
            </div>
            <div class="form-group">
                <label for="featured_coupons_link">Link label</label>
                <input type="text" id="featured_coupons_link" name="featured_coupons[link]" maxlength="80"
                    value="{{ old('featured_coupons.link', $homepage['featured_coupons']['link']) }}">
            </div>
        </div>
    </div>

    <div class="branding-section">
        <h2>Categories</h2>
        <div class="otc-admin-grid">
            <div class="form-group">
                <label for="categories_kicker">Kicker</label>
                <input type="text" id="categories_kicker" name="categories[kicker]" maxlength="80"
                    value="{{ old('categories.kicker', $homepage['categories']['kicker']) }}">
            </div>
            <div class="form-group">
                <label for="categories_title">Title</label>
                <input type="text" id="categories_title" name="categories[title]" maxlength="120"
                    value="{{ old('categories.title', $homepage['categories']['title']) }}">
            </div>
            <div class="form-group">
                <label for="categories_link">Link label</label>
                <input type="text" id="categories_link" name="categories[link]" maxlength="80"
                    value="{{ old('categories.link', $homepage['categories']['link']) }}">
            </div>
            <div class="form-group">
                <label for="categories_explore">Card label</label>
                <input type="text" id="categories_explore" name="categories[explore]" maxlength="40"
                    value="{{ old('categories.explore', $homepage['categories']['explore']) }}">
            </div>
        </div>
    </div>

    <div class="branding-section">
        <h2>Top stores</h2>
        <div class="otc-admin-grid">
            <div class="form-group">
                <label for="stores_kicker">Kicker</label>
                <input type="text" id="stores_kicker" name="stores[kicker]" maxlength="80"
                    value="{{ old('stores.kicker', $homepage['stores']['kicker']) }}">
            </div>
            <div class="form-group">
                <label for="stores_title">Title</label>
                <input type="text" id="stores_title" name="stores[title]" maxlength="120"
                    value="{{ old('stores.title', $homepage['stores']['title']) }}">
            </div>
            <div class="form-group">
                <label for="stores_link">Link label</label>
                <input type="text" id="stores_link" name="stores[link]" maxlength="80"
                    value="{{ old('stores.link', $homepage['stores']['link']) }}">
            </div>
        </div>
    </div>

    <div class="branding-section">
        <h2>How it works</h2>
        <div class="otc-admin-grid">
            <div class="form-group">
                <label for="how_it_works_kicker">Kicker</label>
                <input type="text" id="how_it_works_kicker" name="how_it_works[kicker]" maxlength="80"
                    value="{{ old('how_it_works.kicker', $homepage['how_it_works']['kicker']) }}">
            </div>
            <div class="form-group">
                <label for="how_it_works_cta">Button</label>
                <input type="text" id="how_it_works_cta" name="how_it_works[cta]" maxlength="80"
                    value="{{ old('how_it_works.cta', $homepage['how_it_works']['cta']) }}">
            </div>
        </div>
        <div class="form-group">
            <label for="how_it_works_title">Title</label>
            <textarea id="how_it_works_title" name="how_it_works[title]" rows="2" maxlength="160">{{ old('how_it_works.title', $homepage['how_it_works']['title']) }}</textarea>
            <p class="form-hint">Line breaks are kept on the homepage.</p>
        </div>
        @foreach($homepage['how_it_works']['steps'] as $index => $step)
            <h3 style="font-size:.95rem;margin:1rem 0 .5rem;">Step {{ $index + 1 }}</h3>
            <div class="form-group">
                <label for="how_it_works_step_{{ $index }}_title">Step title</label>
                <input type="text" id="how_it_works_step_{{ $index }}_title" name="how_it_works[steps][{{ $index }}][title]" maxlength="120"
                    value="{{ old("how_it_works.steps.$index.title", $step['title']) }}">
            </div>
            <div class="form-group">
                <label for="how_it_works_step_{{ $index }}_body">Step text</label>
                <textarea id="how_it_works_step_{{ $index }}_body" name="how_it_works[steps][{{ $index }}][body]" rows="2" maxlength="400">{{ old("how_it_works.steps.$index.body", $step['body']) }}</textarea>
            </div>
        @endforeach
    </div>

    <div class="branding-section">
        <h2>Featured deal sidebar</h2>
        <div class="otc-admin-grid">
            <div class="form-group">
                <label for="featured_deal_kicker">Kicker</label>
                <input type="text" id="featured_deal_kicker" name="featured_deal[kicker]" maxlength="80"
                    value="{{ old('featured_deal.kicker', $homepage['featured_deal']['kicker']) }}">
            </div>
            <div class="form-group">
                <label for="featured_deal_offers_label">Offers label</label>
                <input type="text" id="featured_deal_offers_label" name="featured_deal[offers_label]" maxlength="80"
                    value="{{ old('featured_deal.offers_label', $homepage['featured_deal']['offers_label']) }}">
            </div>
            <div class="form-group">
                <label for="featured_deal_stores_label">Stores label</label>
                <input type="text" id="featured_deal_stores_label" name="featured_deal[stores_label]" maxlength="80"
                    value="{{ old('featured_deal.stores_label', $homepage['featured_deal']['stores_label']) }}">
            </div>
        </div>
    </div>

    <div class="branding-section">
        <h2>Blog</h2>
        <div class="otc-admin-grid">
            <div class="form-group">
                <label for="blog_kicker">Kicker</label>
                <input type="text" id="blog_kicker" name="blog[kicker]" maxlength="80"
                    value="{{ old('blog.kicker', $homepage['blog']['kicker']) }}">
            </div>
            <div class="form-group">
                <label for="blog_title">Title</label>
                <input type="text" id="blog_title" name="blog[title]" maxlength="120"
                    value="{{ old('blog.title', $homepage['blog']['title']) }}">
            </div>
            <div class="form-group">
                <label for="blog_link">Link label</label>
                <input type="text" id="blog_link" name="blog[link]" maxlength="80"
                    value="{{ old('blog.link', $homepage['blog']['link']) }}">
            </div>
        </div>
    </div>

    <div class="branding-section">
        <h2>For shoppers</h2>
        <div class="otc-admin-grid">
            <div class="form-group">
                <label for="cta_shoppers_kicker">Kicker</label>
                <input type="text" id="cta_shoppers_kicker" name="cta_shoppers[kicker]" maxlength="80"
                    value="{{ old('cta_shoppers.kicker', $homepage['cta_shoppers']['kicker']) }}">
            </div>
            <div class="form-group">
                <label for="cta_shoppers_button">Button</label>
                <input type="text" id="cta_shoppers_button" name="cta_shoppers[button]" maxlength="80"
                    value="{{ old('cta_shoppers.button', $homepage['cta_shoppers']['button']) }}">
            </div>
            <div class="form-group">
                <label for="cta_shoppers_stat_listed">Third stat label</label>
                <input type="text" id="cta_shoppers_stat_listed" name="cta_shoppers[stat_listed]" maxlength="40"
                    value="{{ old('cta_shoppers.stat_listed', $homepage['cta_shoppers']['stat_listed']) }}">
            </div>
        </div>
        <div class="form-group">
            <label for="cta_shoppers_title">Title</label>
            <input type="text" id="cta_shoppers_title" name="cta_shoppers[title]" maxlength="160"
                value="{{ old('cta_shoppers.title', $homepage['cta_shoppers']['title']) }}">
        </div>
        <div class="form-group">
            <label for="cta_shoppers_body">Body</label>
            <textarea id="cta_shoppers_body" name="cta_shoppers[body]" rows="2" maxlength="320">{{ old('cta_shoppers.body', $homepage['cta_shoppers']['body']) }}</textarea>
        </div>
    </div>

    <div class="branding-section">
        <h2>For store owners</h2>
        <div class="otc-admin-grid">
            <div class="form-group">
                <label for="cta_owners_kicker">Kicker</label>
                <input type="text" id="cta_owners_kicker" name="cta_owners[kicker]" maxlength="80"
                    value="{{ old('cta_owners.kicker', $homepage['cta_owners']['kicker']) }}">
            </div>
            <div class="form-group">
                <label for="cta_owners_button">Button</label>
                <input type="text" id="cta_owners_button" name="cta_owners[button]" maxlength="80"
                    value="{{ old('cta_owners.button', $homepage['cta_owners']['button']) }}">
            </div>
        </div>
        <div class="form-group">
            <label for="cta_owners_title">Title</label>
            <input type="text" id="cta_owners_title" name="cta_owners[title]" maxlength="160"
                value="{{ old('cta_owners.title', $homepage['cta_owners']['title']) }}">
        </div>
        <div class="form-group">
            <label for="cta_owners_body">Body</label>
            <textarea id="cta_owners_body" name="cta_owners[body]" rows="2" maxlength="320">{{ old('cta_owners.body', $homepage['cta_owners']['body']) }}</textarea>
        </div>
        @foreach($homepage['cta_owners']['checks'] as $index => $check)
            <div class="form-group">
                <label for="cta_owners_check_{{ $index }}">Bullet {{ $index + 1 }}</label>
                <input type="text" id="cta_owners_check_{{ $index }}" name="cta_owners[checks][{{ $index }}]" maxlength="160"
                    value="{{ old("cta_owners.checks.$index", $check) }}">
            </div>
        @endforeach
    </div>

    <div class="branding-section">
        <h2>Newsletter</h2>
        <div class="otc-admin-grid">
            <div class="form-group">
                <label for="newsletter_kicker">Kicker</label>
                <input type="text" id="newsletter_kicker" name="newsletter[kicker]" maxlength="80"
                    value="{{ old('newsletter.kicker', $homepage['newsletter']['kicker']) }}">
            </div>
            <div class="form-group">
                <label for="newsletter_placeholder">Email placeholder</label>
                <input type="text" id="newsletter_placeholder" name="newsletter[placeholder]" maxlength="80"
                    value="{{ old('newsletter.placeholder', $homepage['newsletter']['placeholder']) }}">
            </div>
            <div class="form-group">
                <label for="newsletter_button">Button</label>
                <input type="text" id="newsletter_button" name="newsletter[button]" maxlength="40"
                    value="{{ old('newsletter.button', $homepage['newsletter']['button']) }}">
            </div>
        </div>
        <div class="form-group">
            <label for="newsletter_title">Title</label>
            <input type="text" id="newsletter_title" name="newsletter[title]" maxlength="120"
                value="{{ old('newsletter.title', $homepage['newsletter']['title']) }}">
        </div>
        <div class="form-group">
            <label for="newsletter_body">Body</label>
            <textarea id="newsletter_body" name="newsletter[body]" rows="2" maxlength="320">{{ old('newsletter.body', $homepage['newsletter']['body']) }}</textarea>
        </div>
    </div>

    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="{{ route('home') }}" class="btn btn-outline" target="_blank" rel="noopener">Preview homepage →</a>
    </div>
</form>
@endsection

@push('styles')
<style>
.branding-section {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem 1.35rem;
    margin-bottom: 1.25rem;
}
.branding-section h2 {
    margin: 0 0 .35rem;
    font-size: 1.05rem;
}
.otc-admin-toggles {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: .25rem 1rem;
}
.otc-admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 0 1rem;
}
</style>
@endpush
