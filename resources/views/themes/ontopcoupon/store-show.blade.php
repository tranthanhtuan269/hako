@extends('layouts.app')

@php
    $codeCount = $codeCount ?? $coupons->total();
    $dealCount = $dealCount ?? 0;
    $bestOffer = $bestOffer ?? $store->bestOfferLabel();
    $offerType = $offerType ?? null;
    $offerTotal = $codeCount + $dealCount;
    $heroDesc = filled($store->description)
        ? \Illuminate\Support\Str::limit(strip_tags($store->description), 140)
        : 'Browse verified '.$store->name.' coupon codes and discount deals.';
    $storeCrumbs = [
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Stores', 'url' => route('stores.index')],
    ];
    if ($store->category) {
        $storeCrumbs[] = ['name' => $store->category->name, 'url' => route('categories.show', $store->category->slug)];
    }
    $storeCrumbs[] = ['name' => $store->name, 'url' => route('stores.show', $store->slug)];
@endphp

@section('title', $store->seoTitle())
@section('meta_description', $store->seoDescription())
@section('og_title', $store->ogShareTitle())
@section('og_description', $store->ogShareDescription())
@section('canonical', route('stores.show', $store->slug))
@section('og_url', route('stores.show', $store->slug))
@if($store->ogImageUrl())
@section('og_image', $store->ogImageUrl())
@endif
@section('og_image_alt', $store->name)

@push('og_meta')
@if($store->ogImageUrl())
<meta property="og:image:secure_url" content="{{ \App\Support\Seo::absoluteUrl($store->ogImageUrl()) }}">
@endif
<meta property="og:updated_time" content="{{ $store->updated_at?->toIso8601String() }}">
@endpush

@push('structured_data')
@include('partials.breadcrumb-schema', ['breadcrumbs' => $storeCrumbs])
<script type="application/ld+json">
@json($store->structuredData(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
</script>
@endpush

@push('head_links')
    @include('partials.pagination-seo', ['paginator' => $coupons])
@endpush

@section('content')
<div class="otc-store-page">
    <section class="otc-store-hero">
        <div class="otc-store-wrap">
            <nav class="otc-store-crumbs" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span>/</span>
                <a href="{{ route('stores.index') }}">Stores</a>
                @if($store->category)
                    <span>/</span>
                    <a href="{{ route('categories.show', $store->category->slug) }}">{{ $store->category->name }}</a>
                @endif
                <span>/</span>
                <span class="otc-store-crumbs-current">{{ $store->name }}</span>
            </nav>

            <div class="otc-store-hero-brand">
                <div class="otc-store-hero-logo">
                    @include('partials.store-logo', ['store' => $store, 'size' => 'xl', 'showVerified' => false, 'linked' => false])
                </div>
                <div>
                    <h1>{{ $store->name }} Coupons</h1>
                    <p>{{ $heroDesc }}</p>
                </div>
            </div>

            <div class="otc-store-hero-stats">
                <div>
                    <strong>{{ number_format($codeCount) }}</strong>
                    <span>Coupon codes</span>
                </div>
                <div>
                    <strong>{{ number_format($dealCount) }}</strong>
                    <span>Deals</span>
                </div>
                @if($bestOffer)
                    <div>
                        <strong>{{ $bestOffer }}</strong>
                        <span>Best offer</span>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="otc-store-body">
        <div class="otc-store-wrap otc-store-layout">
            <aside class="otc-store-sidebar">
                <div class="otc-store-card otc-store-card--profile">
                    <div class="otc-store-card-logo">
                        @include('partials.store-logo', ['store' => $store, 'size' => 'lg', 'showVerified' => false, 'linked' => false])
                    </div>
                    <p class="otc-store-card-name">{{ $store->name }}</p>
                    @if($store->category)
                        <p class="otc-store-card-cat">{{ $store->category->name }}</p>
                    @endif
                    @if($bestOffer)
                        <div class="otc-store-card-offer">{{ $bestOffer }}</div>
                    @endif
                    @if($store->shopUrl())
                        <a href="{{ $store->shopUrl() }}" class="otc-store-visit" target="_blank" rel="noopener sponsored">
                            <span class="otc-store-visit-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                                Visit Store
                            </span>
                        </a>
                    @endif
                </div>

                <div class="otc-store-card otc-store-card--stats">
                    <div class="otc-store-stat">
                        <span class="otc-store-stat-label">Coupon Codes</span>
                        <span class="otc-store-stat-value">{{ number_format($codeCount) }}</span>
                    </div>
                    <div class="otc-store-stat">
                        <span class="otc-store-stat-label">Deals</span>
                        <span class="otc-store-stat-value">{{ number_format($dealCount) }}</span>
                    </div>
                    @if($bestOffer)
                        <div class="otc-store-stat">
                            <span class="otc-store-stat-label">Best Offer</span>
                            <span class="otc-store-stat-value">{{ $bestOffer }}</span>
                        </div>
                    @endif
                </div>

                @if($store->category)
                    <a href="{{ route('categories.show', $store->category->slug) }}" class="otc-store-card otc-store-card--category">
                        <div class="otc-store-card-category-text">
                            <p>Category</p>
                            <strong>{{ $store->category->name }}</strong>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                @endif
            </aside>

            <div class="otc-store-main">
                <div class="otc-store-filters" role="tablist" aria-label="Filter offers">
                    <a href="{{ route('stores.show', $store->slug) }}" class="otc-store-filter{{ $offerType === null ? ' is-active' : '' }}">All ({{ number_format($offerTotal) }})</a>
                    <a href="{{ route('stores.show', ['slug' => $store->slug, 'type' => 'coupon']) }}" class="otc-store-filter{{ $offerType === 'coupon' ? ' is-active' : '' }}">Codes ({{ number_format($codeCount) }})</a>
                    <a href="{{ route('stores.show', ['slug' => $store->slug, 'type' => 'discount']) }}" class="otc-store-filter{{ $offerType === 'discount' ? ' is-active' : '' }}">Deals ({{ number_format($dealCount) }})</a>
                </div>

                <div class="otc-coupon-list">
                    @forelse($coupons as $coupon)
                        @include('themes.ontopcoupon.coupon-row', ['coupon' => $coupon, 'openAffiliateOnCopy' => true])
                    @empty
                        <p class="otc-empty">No {{ $offerType === 'discount' ? 'deals' : ($offerType === 'coupon' ? 'coupon codes' : 'offers') }} available for this store yet.</p>
                    @endforelse
                </div>
                <div class="pagination">{{ $coupons->links() }}</div>

                <div class="otc-store-panel">
                    <h2>About {{ $store->name }}</h2>
                    @if($store->description)
                        <div class="otc-store-about store-description-content">{!! $store->renderedDescription() !!}</div>
                    @else
                        <p>{{ $store->name }} offers coupon codes and exclusive promotions. Find the latest verified deals listed here.</p>
                    @endif
                </div>

                <div class="otc-store-panel">
                    <h2>How to use {{ $store->name }} coupons</h2>
                    <ol class="otc-store-howto">
                        <li>
                            <span>01</span>
                            <p>Find the {{ $store->name }} coupon above and click “GET CODE”.</p>
                        </li>
                        <li>
                            <span>02</span>
                            <p>You’ll be redirected to {{ $store->name }}. Add items to your cart.</p>
                        </li>
                        <li>
                            <span>03</span>
                            <p>Paste the code at checkout in the discount field and enjoy savings.</p>
                        </li>
                    </ol>
                </div>

                <div class="otc-store-panel">
                    <h2>{{ $store->name }} Q&amp;A</h2>
                    <div class="otc-store-faq">
                        <details>
                            <summary>Why should I visit here for {{ $store->name }} coupons?</summary>
                            <p>We list active {{ $store->name }} promo codes and deals in one place so you can copy a code and check the offer before you shop.</p>
                        </details>
                        <details>
                            <summary>Where to find {{ $store->name }} promo codes?</summary>
                            <p>Every code on this page is collected for {{ $store->name }}. Use the Codes filter above if you only want promo codes, not automatic deals.</p>
                        </details>
                        <details>
                            <summary>How to use {{ $store->name }} coupon code?</summary>
                            <p>Click GET CODE, copy the code, then paste it in the discount field at {{ $store->name }} checkout. Confirm the discount still applies in your cart.</p>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($similarStores->isNotEmpty())
        <section class="otc-store-related">
            <div class="otc-store-wrap">
                <h2>More stores{{ $store->category ? ' in '.$store->category->name : '' }}</h2>
                <div class="otc-related-grid">
                    @foreach($similarStores as $similar)
                        @php
                            $similarOffer = $similar->bestOfferLabel();
                            $similarCoupon = $similar->relationLoaded('coupons') ? $similar->coupons->first() : null;
                        @endphp
                        <a href="{{ route('stores.show', $similar->slug) }}" class="otc-related-card">
                            <div class="otc-related-card-top">
                                @include('partials.store-logo', ['store' => $similar, 'size' => 'md', 'showVerified' => false, 'linked' => false])
                                <div>
                                    <p class="otc-related-name">{{ $similar->name }}</p>
                                    @if($similarOffer)
                                        <p class="otc-related-offer">{{ $similarOffer }}</p>
                                    @endif
                                </div>
                            </div>
                            @if($similarCoupon)
                                <div class="otc-related-snippet">
                                    <p>{{ $similarCoupon->title }}</p>
                                    <span>{{ $similarCoupon->discountLabel() }}</span>
                                </div>
                            @endif
                            <span class="otc-related-link">View coupons <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>

@include('partials.scroll-coupon-popup')
@endsection
