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
@if($activeTheme === 'googlycodes')
    @include('themes.googlycodes.home-hero', ['heroSlides' => $heroSlides ?? collect()])
    @include('themes.googlycodes.trending-stores', [
        'trendingStores' => $trendingStores ?? $stores ?? collect(),
    ])
    @include('themes.googlycodes.exclusive-promos', [
        'exclusiveCoupons' => $exclusiveCoupons ?? collect(),
    ])
@elseif($activeTheme === 'savingspro')
    @include('themes.savingspro.home-hero', compact('stats'))
@else
<section class="hero">
    <div class="container">
        <h1>{{ $siteHomeH1 }}</h1>
        <p>{{ $siteHomeSubH1 }}.</p>
        <p class="hero-subtitle">{{ $siteHeroSubtitle }}</p>
        <form action="{{ route('search') }}" method="GET" class="search-form" style="max-width:480px;margin:0 auto;">
            <input type="search" name="q" placeholder="Search coupons, stores, articles...">
            <button type="submit">Search</button>
        </form>
        <div class="hero-stats">
            <div><strong>{{ $stats['coupons'] }}</strong> Active Offers</div>
            <div><strong>{{ $stats['stores'] }}</strong> Stores</div>
            <div><strong>{{ $stats['categories'] }}</strong> Categories</div>
        </div>
    </div>
</section>
@endif

@if($activeTheme !== 'googlycodes' && $latestPosts->isNotEmpty())
<section class="section">
    <div class="container">
        <h2 class="section-title">Review <a href="{{ route('blog.index') }}">All articles →</a></h2>
        <div class="blog-grid">
            @foreach($latestPosts as $post)
                @include('blog.partials.card', ['post' => $post])
            @endforeach
        </div>
    </div>
</section>
@endif

@if($activeTheme !== 'googlycodes')
<section class="section">
    <div class="container">
        <h2 class="section-title">
            @if($activeTheme === 'savingspro')
                Savings from the World's Best Stores
            @else
                Popular Stores
            @endif
        </h2>
        <div class="store-slider" data-autoplay="3000">
            <div class="store-slider-viewport store-scroll--autoplay" tabindex="0" aria-label="Popular stores">
                <div class="store-scroll-track">
                @foreach($stores as $store)
                    <a href="{{ route('stores.show', $store->slug) }}" class="store-chip store-chip--logo">
                        @include('partials.store-logo', ['store' => $store, 'size' => 'md', 'showVerified' => false, 'linked' => false])
                        <strong>{{ $store->name }}</strong>
                        <small>{{ $store->activeCouponsCount() }} offers</small>
                    </a>
                @endforeach
                </div>
            </div>
            <div class="store-slider-dots" role="tablist" aria-label="Popular stores slides">
                @for ($i = 0; $i < max(1, $stores->count() - 1); $i++)
                    <button
                        type="button"
                        class="store-slider-dot{{ $i === 0 ? ' is-active' : '' }}"
                        data-slide-index="{{ $i }}"
                        role="tab"
                        aria-label="Slide {{ $i + 1 }}"
                        aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                    ></button>
                @endfor
            </div>
        </div>
    </div>
</section>
@endif

@if($activeTheme === 'googlycodes')
    @include('themes.googlycodes.popular-categories', [
        'categories' => $categories ?? collect(),
    ])
    @include('themes.googlycodes.popular-stores', [
        'stores' => $stores ?? collect(),
    ])
    {{-- SEO copy + special events — last content before footer --}}
    @include('themes.googlycodes.promo-copy')
    @include('themes.googlycodes.special-events')
@else
<section class="section">
    <div class="container">
        <h2 class="section-title">Categories</h2>
        <div class="category-grid home-category-grid">
            @foreach($categories as $category)
                <a href="{{ route('categories.show', $category->slug) }}" class="category-chip">
                    <span class="icon">@include('partials.category-icon', ['category' => $category, 'size' => 'md'])</span>
                    <strong>{{ $category->name }}</strong>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($activeTheme === 'savingspro')
    @include('themes.savingspro.newsletter-cta')
@endif
@endsection
