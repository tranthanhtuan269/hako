@extends('layouts.app')

@php
    $codeCount = $codeCount ?? $coupons->total();
    $dealCount = $dealCount ?? 0;
    $bestOffer = $bestOffer ?? $store->bestOfferLabel();
    $offerType = $offerType ?? null;
    $offerTotal = $codeCount + $dealCount;
    $cleanDesc = preg_replace('/<figure\b[^>]*>.*?<\/figure>|<figcaption\b[^>]*>.*?<\/figcaption>/is', '', $store->description ?? '');
    $cleanDesc = trim(preg_replace('/\s+/', ' ', strip_tags($cleanDesc)));
    $heroDesc = $cleanDesc !== ''
        ? \Illuminate\Support\Str::limit($cleanDesc, 160)
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
@php
    use App\Support\OntopcouponTheme as Otc;

    $filters = [
        [null, 'All', $offerTotal],
        ['coupon', 'Codes', $codeCount],
        ['discount', 'Deals', $dealCount],
    ];
    $faqs = [
        ['Why should I visit here for '.$store->name.' coupons?', 'We list active '.$store->name.' promo codes and deals in one place so you can copy a code and check the offer before you shop.'],
        ['Where to find '.$store->name.' promo codes?', 'Every code on this page is collected for '.$store->name.'. Use the Codes filter above if you only want promo codes, not automatic deals.'],
        ['How to use '.$store->name.' coupon code?', 'Click Show Coupon, copy the code, then paste it in the discount field at '.$store->name.' checkout. Confirm the discount still applies in your cart.'],
    ];
@endphp

<section class="ot-detail-hero">
    <div class="ot-container ot-container--narrow">
        <nav class="ot-crumbs" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><span>/</span>
            <a href="{{ route('stores.index') }}">Stores</a><span>/</span>
            @if($store->category)
                <a href="{{ route('categories.show', $store->category->slug) }}">{{ $store->category->name }}</a><span>/</span>
            @endif
            <span class="is-current">{{ $store->name }}</span>
        </nav>
        <div class="ot-detail-hero-brand">
            @include('themes.ontopcoupon.partials.logo', ['store' => $store, 'class' => 'ot-logo--hero'])
            <div>
                <h1>{{ $store->name }} Coupons</h1>
                <p>{{ $heroDesc }}</p>
            </div>
        </div>
        <div class="ot-detail-stats ot-detail-stats--divided">
            <div><strong>{{ number_format($codeCount) }}</strong><span>COUPON CODES</span></div>
            <div><strong>{{ number_format($dealCount) }}</strong><span>DEALS</span></div>
            @if($bestOffer)
                <div><strong>{{ $bestOffer }}</strong><span>BEST OFFER</span></div>
            @endif
        </div>
    </div>
</section>

<section class="ot-detail-body">
    <div class="ot-container ot-container--narrow ot-detail-layout">
        <aside class="ot-store-aside">
            <div class="ot-panel ot-store-profile">
                @include('themes.ontopcoupon.partials.logo', ['store' => $store, 'class' => 'ot-logo--md'])
                <p class="ot-store-profile-name">{{ $store->name }}</p>
                @if($store->category)
                    <a href="{{ route('categories.show', $store->category->slug) }}" class="ot-store-profile-cat">{{ $store->category->name }}</a>
                @endif
                @if($bestOffer)
                    <div class="ot-store-profile-offer">{{ $bestOffer }}</div>
                @endif
                @if($store->shopUrl())
                    <a href="{{ $store->shopUrl() }}" class="ot-btn-solid ot-btn-block" target="_blank" rel="noopener sponsored">{!! Otc::icon('external-link', 13) !!} Visit Store</a>
                @endif
            </div>
            <div class="ot-panel ot-store-facts">
                <div><span>Coupon Codes</span><strong>{{ number_format($codeCount) }}</strong></div>
                <div><span>Deals</span><strong>{{ number_format($dealCount) }}</strong></div>
                @if($bestOffer)
                    <div><span>Best Offer</span><strong>{{ $bestOffer }}</strong></div>
                @endif
            </div>
        </aside>

        <div class="ot-detail-main">
            <div class="ot-filter-pills ot-filter-pills--lg" role="tablist" aria-label="Filter offers">
                @foreach($filters as [$value, $label, $count])
                    <a href="{{ route('stores.show', array_filter(['slug' => $store->slug, 'type' => $value])) }}" @class(['ot-filter-pill', 'is-active' => $offerType === $value])>{{ $label }} ({{ number_format($count) }})</a>
                @endforeach
            </div>

            <div class="ot-coupon-list ot-coupon-list--compact">
                @forelse($coupons as $coupon)
                    @include('themes.ontopcoupon.coupon-row', ['coupon' => $coupon, 'variant' => 'store'])
                @empty
                    <div class="ot-empty ot-empty--dashed">
                        <p>No {{ $offerType === 'discount' ? 'deals' : ($offerType === 'coupon' ? 'coupon codes' : 'offers') }} available for this store yet.</p>
                    </div>
                @endforelse
            </div>
            {{ $coupons->links('themes.ontopcoupon.pagination', ['label' => 'Offer pages']) }}

            <div class="ot-panel ot-content-panel">
                <h2>About {{ $store->name }}</h2>
                @if($store->description)
                    <div class="ot-prose store-description-content">{!! $store->renderedDescription() !!}</div>
                @else
                    <p class="ot-prose">{{ $store->name }} offers coupon codes and exclusive promotions. Find the latest verified deals listed here.</p>
                @endif
            </div>

            <div class="ot-panel ot-content-panel">
                <h2>How to use {{ $store->name }} coupons</h2>
                <ol class="ot-howto">
                    <li><span>01</span><p>Find the {{ $store->name }} coupon above and click “Show Coupon”.</p></li>
                    <li><span>02</span><p>You’ll be redirected to {{ $store->name }}. Add items to your cart.</p></li>
                    <li><span>03</span><p>Paste the code at checkout in the discount field and enjoy savings.</p></li>
                </ol>
            </div>

            <div class="ot-panel ot-content-panel">
                <h2>{{ $store->name }} Q&amp;A</h2>
                <div class="ot-faq">
                    @foreach($faqs as [$question, $answer])
                        <details>
                            <summary>{{ $question }} {!! Otc::icon('chevron-down', 16) !!}</summary>
                            <p>{{ $answer }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

@if($similarStores->isNotEmpty())
<section class="ot-section ot-section--alt">
    <div class="ot-container ot-container--narrow">
        @include('themes.ontopcoupon.partials.section-head', [
            'kicker' => 'You may also like',
            'title' => 'More Stores'.($store->category ? ' in '.$store->category->name : ''),
            'accentWords' => $store->category ? max(1, str_word_count($store->category->name)) : 1,
            'link' => 'All stores',
            'url' => $store->category ? route('categories.show', $store->category->slug) : route('stores.index'),
            'caps' => true,
        ])
        <div class="ot-store-grid">
            @foreach($similarStores as $similar)
                @include('themes.ontopcoupon.partials.store-card', ['store' => $similar])
            @endforeach
        </div>
    </div>
</section>
@endif

@include('partials.scroll-coupon-popup')
@endsection
