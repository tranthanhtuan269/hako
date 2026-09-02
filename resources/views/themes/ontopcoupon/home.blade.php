@extends('layouts.app')

@section('title', config('site.name') . ' — Top Hub of US Online Coupons and Promo Codes')
@section('meta_description', config('site.default_description'))
@section('og_title', config('site.name') . ' — ' . config('site.tagline'))
@section('og_description', config('site.default_description'))
@section('canonical', route('home'))
@section('og_url', route('home'))

@push('structured_data')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": @json(config('site.name')),
    "url": @json(config('site.url')),
    "description": @json(config('site.default_description')),
    "potentialAction": {
        "@type": "SearchAction",
        "target": {
            "@type": "EntryPoint",
            "urlTemplate": @json(route('search') . '?q={search_term_string}')
        },
        "query-input": "required name=search_term_string"
    }
}
</script>
@endpush

@section('content')
@php
    $otc = \App\Support\OntopcouponHomepage::resolved();
    $featuredCoupons = $featuredCoupons ?? collect();
    $featuredDeal = $featuredCoupons->first();
    $showHowItWorks = !empty($otc['sections']['how_it_works']);
    $showFeaturedDeal = !empty($otc['sections']['featured_deal']) && $featuredDeal;
    $otcCategoryPalette = [
        ['#e8505b', 'rgba(232,80,91,.12)'],
        ['#3b82f6', 'rgba(59,130,246,.12)'],
        ['#f59e0b', 'rgba(245,158,11,.12)'],
        ['#10b981', 'rgba(16,185,129,.12)'],
        ['#a855f7', 'rgba(168,85,247,.12)'],
        ['#ef4444', 'rgba(239,68,68,.12)'],
        ['#14b8a6', 'rgba(20,184,166,.12)'],
        ['#6366f1', 'rgba(99,102,241,.12)'],
    ];
@endphp

@if(!empty($otc['sections']['hero']))
<section class="otc-hero">
    <div class="container otc-hero-inner">
        <h1>{{ $siteHomeH1 }}</h1>
        <p class="otc-hero-lead">{{ $siteHomeSubH1 }}</p>
        <p class="otc-hero-sub">{{ $siteHeroSubtitle }}</p>
    </div>
</section>

<div class="otc-hero-search-wrap">
    <form action="{{ route('search') }}" method="GET" class="otc-hero-search">
        <svg class="otc-hero-search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.34-4.34"/></svg>
        <input type="search" name="q" placeholder="{{ $otc['search_placeholder'] }}" aria-label="Search">
    </form>
</div>
@endif

@if(!empty($otc['sections']['trust']) && $stores->isNotEmpty())
<section class="otc-trust">
    <p class="otc-trust-label">{{ $otc['trust_label'] }}</p>
    <div class="otc-trust-row">
        @foreach($stores->take(10) as $store)
            <a href="{{ route('stores.show', $store->slug) }}" class="otc-trust-logo" title="{{ $store->name }}">
                @include('partials.store-logo', ['store' => $store, 'size' => 'md', 'showVerified' => false, 'linked' => false])
            </a>
        @endforeach
    </div>
</section>
@endif

@if(!empty($otc['sections']['featured_coupons']) && $featuredCoupons->isNotEmpty())
<section class="otc-section">
    <div class="container">
        <div class="otc-section-head">
            <div>
                <p class="otc-kicker">{{ $otc['featured_coupons']['kicker'] }}</p>
                <h2>{{ $otc['featured_coupons']['title'] }}</h2>
            </div>
            <a href="{{ route('coupons.index') }}" class="otc-link-all">{{ $otc['featured_coupons']['link'] }} <span aria-hidden="true">→</span></a>
        </div>
        <div class="otc-coupon-grid">
            @foreach($featuredCoupons as $coupon)
                @include('themes.ontopcoupon.coupon-card', ['coupon' => $coupon, 'showDescription' => true])
            @endforeach
        </div>
    </div>
</section>
@endif

@if(!empty($otc['sections']['categories']) && $categories->isNotEmpty())
<section class="otc-section otc-section--alt">
    <div class="container">
        <div class="otc-section-head">
            <div>
                <p class="otc-kicker">{{ $otc['categories']['kicker'] }}</p>
                <h2>{{ $otc['categories']['title'] }}</h2>
            </div>
            <a href="{{ route('categories.index') }}" class="otc-link-all">{{ $otc['categories']['link'] }} <span aria-hidden="true">→</span></a>
        </div>
        <div class="otc-category-grid">
            @foreach($categories->take(8) as $index => $category)
                @php
                    [$accent, $tint] = $otcCategoryPalette[$index % count($otcCategoryPalette)];
                @endphp
                <a href="{{ route('categories.show', $category->slug) }}" class="otc-category-card" style="--otc-cat: {{ $accent }}; --otc-cat-bg: {{ $tint }}">
                    <span class="otc-category-icon">@include('partials.category-icon', ['category' => $category, 'size' => 'md'])</span>
                    <strong>{{ $category->name }}</strong>
                    <span class="otc-category-explore">{{ $otc['categories']['explore'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@if(!empty($otc['sections']['stores']) && $stores->isNotEmpty())
<section class="otc-section">
    <div class="container">
        <div class="otc-section-head">
            <div>
                <p class="otc-kicker">{{ $otc['stores']['kicker'] }}</p>
                <h2>{{ $otc['stores']['title'] }}</h2>
            </div>
            <a href="{{ route('stores.index') }}" class="otc-link-all">{{ $otc['stores']['link'] }} <span aria-hidden="true">→</span></a>
        </div>
        <div class="otc-store-grid">
            @foreach($stores as $store)
                <a href="{{ route('stores.show', $store->slug) }}" class="otc-store-card">
                    @include('partials.store-logo', ['store' => $store, 'size' => 'lg', 'showVerified' => false, 'linked' => false])
                    <strong>{{ $store->name }}</strong>
                    <small>{{ number_format((int) ($store->active_coupons_count ?? $store->activeCouponsCount())) }} offers</small>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($showHowItWorks || $showFeaturedDeal)
<section class="otc-section otc-section--alt">
    <div class="container otc-split{{ $showHowItWorks ? '' : ' otc-split--deal-only' }}">
        @if($showHowItWorks)
        <div class="otc-steps">
            <p class="otc-kicker">{{ $otc['how_it_works']['kicker'] }}</p>
            <h2>{!! nl2br(e($otc['how_it_works']['title'])) !!}</h2>
            <ol class="otc-steps-list">
                @foreach($otc['how_it_works']['steps'] as $index => $step)
                <li>
                    <span class="otc-step-icon" aria-hidden="true">{{ $index + 1 }}</span>
                    <div>
                        <h3>{{ $step['title'] }}</h3>
                        <p>{{ \App\Support\OntopcouponHomepage::interpolate($step['body'], $stats) }}</p>
                    </div>
                </li>
                @endforeach
            </ol>
            <a href="{{ route('coupons.index') }}" class="otc-btn-dark">{{ $otc['how_it_works']['cta'] }}</a>
        </div>
        @endif

        @if($showFeaturedDeal)
            <aside class="otc-featured-deal">
                <p class="otc-kicker">{{ $otc['featured_deal']['kicker'] }}</p>
                @include('themes.ontopcoupon.coupon-card', ['coupon' => $featuredDeal, 'showDescription' => true])
                <div class="otc-featured-stats">
                    <div>
                        <strong>{{ number_format($stats['coupons']) }}+</strong>
                        <span>{{ $otc['featured_deal']['offers_label'] }}</span>
                    </div>
                    <div>
                        <strong>{{ number_format($stats['stores']) }}+</strong>
                        <span>{{ $otc['featured_deal']['stores_label'] }}</span>
                    </div>
                </div>
            </aside>
        @endif
    </div>
</section>
@endif

@if(!empty($otc['sections']['blog']) && $latestPosts->isNotEmpty())
<section class="otc-section">
    <div class="container">
        <div class="otc-section-head">
            <div>
                <p class="otc-kicker">{{ $otc['blog']['kicker'] }}</p>
                <h2>{{ $otc['blog']['title'] }}</h2>
            </div>
            <a href="{{ route('blog.index') }}" class="otc-link-all">{{ $otc['blog']['link'] }} <span aria-hidden="true">→</span></a>
        </div>
        <div class="otc-blog-grid">
            @foreach($latestPosts->take(3) as $post)
                @include('blog.partials.card', ['post' => $post])
            @endforeach
        </div>
    </div>
</section>
@endif

@if(!empty($otc['sections']['cta']))
<section class="otc-cta-band">
    <div class="container otc-cta-grid">
        <div class="otc-cta-card">
            <p class="otc-kicker">{{ $otc['cta_shoppers']['kicker'] }}</p>
            <h2>{{ $otc['cta_shoppers']['title'] }}</h2>
            <p>{{ \App\Support\OntopcouponHomepage::interpolate($otc['cta_shoppers']['body'], $stats) }}</p>
            <ul class="otc-cta-stats">
                <li><strong>{{ number_format($stats['coupons']) }}+</strong> Coupons</li>
                <li><strong>{{ number_format($stats['stores']) }}+</strong> Stores</li>
                <li><strong>100%</strong> {{ $otc['cta_shoppers']['stat_listed'] }}</li>
            </ul>
            <a href="{{ route('coupons.index') }}" class="otc-btn-lime">{{ $otc['cta_shoppers']['button'] }}</a>
        </div>
        <div class="otc-cta-card otc-cta-card--dark">
            <p class="otc-kicker otc-kicker--lime">{{ $otc['cta_owners']['kicker'] }}</p>
            <h2>{{ $otc['cta_owners']['title'] }}</h2>
            <p>{{ $otc['cta_owners']['body'] }}</p>
            <ul class="otc-cta-checks">
                @foreach($otc['cta_owners']['checks'] as $check)
                    @if(filled($check))
                        <li>{{ $check }}</li>
                    @endif
                @endforeach
            </ul>
            <a href="{{ route('register') }}" class="otc-btn-outline-lime">{{ $otc['cta_owners']['button'] }}</a>
        </div>
    </div>
</section>
@endif

@if(!empty($otc['sections']['newsletter']))
<section class="otc-newsletter">
    <div class="container otc-newsletter-inner">
        <p class="otc-kicker">{{ $otc['newsletter']['kicker'] }}</p>
        <h2>{{ $otc['newsletter']['title'] }}</h2>
        <p>{{ $otc['newsletter']['body'] }}</p>
        <form action="{{ route('pages.contact') }}" method="GET" class="otc-newsletter-form">
            <input type="email" name="email" placeholder="{{ $otc['newsletter']['placeholder'] }}" required aria-label="Email address">
            <button type="submit">{{ $otc['newsletter']['button'] }}</button>
        </form>
    </div>
</section>
@endif
@endsection
