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
    use App\Support\OntopcouponTheme as Otc;

    $otc = \App\Support\OntopcouponHomepage::resolved();
    $sections = $otc['sections'];
    $tickerCoupons = $featuredCoupons->getCollection()->filter(fn ($c) => $c->store)->take(4);
    $tabCategories = $categories->take(8)->values();
    $stepIcons = ['search', 'copy', 'shopping-bag'];
@endphp

@if(!empty($sections['hero']))
<div class="ot-hero-wrap">
    <section @class(['ot-hero', 'has-hero-bg' => filled($siteHeroBackgroundUrl ?? null)]) @if(filled($siteHeroBackgroundUrl ?? null)) style="--ot-hero-image: url({{ json_encode($siteHeroBackgroundUrl) }})" @endif>
        <div class="ot-hero-glow" aria-hidden="true"></div>
        <h1>
            <span class="ot-hero-gift" aria-hidden="true">{!! Otc::icon('gift', 40) !!}</span>
            <span>{!! Otc::accent($siteHomeH1, 3) !!}</span>
            <span class="ot-hero-gift" aria-hidden="true">{!! Otc::icon('gift', 40) !!}</span>
        </h1>
        <p class="ot-hero-lead">{{ $siteHomeSubH1 }}</p>
        <form action="{{ route('search') }}" method="GET" class="ot-hero-search" role="search">
            {!! Otc::icon('search', 20, 'ot-hero-search-icon') !!}
            <input type="search" name="q" placeholder="{{ $otc['search_placeholder'] }}" aria-label="Search coupons and stores">
        </form>
        @if($tickerCoupons->isNotEmpty())
            <div class="ot-hero-ticker">
                <span class="ot-hero-ticker-label" style="color:#B1F467;">🔥 TOP COUPONS:</span>
                @foreach($tickerCoupons as $coupon)
                    <a href="{{ route('stores.show', $coupon->store->slug) }}" class="ot-hero-chip">
                        <strong>{{ $coupon->store->name }}</strong>: {{ Otc::chipLabel($coupon) }}
                    </a>
                @endforeach
            </div>
        @endif
        @if(filled($siteHeroSubtitle))
            <p class="ot-hero-note">{{ $siteHeroSubtitle }}</p>
        @endif
    </section>
</div>
@endif

@if(!empty($sections['trust']) && $stores->isNotEmpty())
<section class="ot-trusted">
    <h2>{{ Otc::accent($otc['trust_label'], 2) }}</h2>
    <div class="ot-marquee">
        <div class="ot-marquee-track">
            @php $marqueeStores = Otc::marqueeItems($stores); @endphp
            @foreach([false, true] as $duplicate)
                @foreach($marqueeStores as $store)
                    @php $storeOffer = $store->bestOfferLabel(); @endphp
                    <a href="{{ route('stores.show', $store->slug) }}" class="ot-trusted-card" @if($duplicate) aria-hidden="true" tabindex="-1" @endif>
                        @include('themes.ontopcoupon.partials.logo', ['store' => $store, 'class' => 'ot-logo--lg'])
                        <span class="ot-trusted-card-text">
                            <strong>{{ $store->name }}</strong>
                            @if($storeOffer)
                                <span class="ot-trusted-badge">{!! Otc::icon('tag', 13, '', 2.4) !!} {{ $storeOffer }}</span>
                            @endif
                        </span>
                    </a>
                @endforeach
            @endforeach
        </div>
    </div>
</section>
@endif

@if(!empty($sections['featured_coupons']) && $featuredCoupons->isNotEmpty())
<section class="ot-section" id="featured-coupons">
    <div class="ot-container">
        @include('themes.ontopcoupon.partials.section-head', [
            'kicker' => $otc['featured_coupons']['kicker'],
            'title' => $otc['featured_coupons']['title'],
            'link' => $otc['featured_coupons']['link'],
            'url' => route('coupons.index'),
        ])
        <div class="ot-coupon-list">
            @foreach($featuredCoupons as $coupon)
                @include('themes.ontopcoupon.coupon-row', ['coupon' => $coupon])
            @endforeach
        </div>
        {{ $featuredCoupons->links('themes.ontopcoupon.pagination', ['label' => 'Coupon pages']) }}
    </div>
</section>
@endif

@if(!empty($sections['categories']) && $tabCategories->isNotEmpty())
<section class="ot-section ot-section--alt">
    <div class="ot-container">
        @include('themes.ontopcoupon.partials.section-head', [
            'kicker' => $otc['categories']['kicker'],
            'title' => $otc['categories']['title'],
            'link' => $otc['categories']['link'],
            'url' => route('categories.index'),
            'caps' => true,
        ])
        <div class="ot-cat-tabs" data-ot-tabs>
            <div class="ot-cat-tabs-list" role="tablist" aria-label="Categories">
                @foreach($tabCategories as $index => $category)
                    @php $style = Otc::categoryStyle($category, $index); @endphp
                    <button type="button" role="tab" id="ot-cat-tab-{{ $category->id }}" aria-controls="ot-cat-panel-{{ $category->id }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}" @class(['ot-cat-tab', 'is-active' => $index === 0]) data-ot-tab style="--ot-cat: {{ $style['color'] }}; --ot-cat-bg: {{ Otc::rgba($style['color'], .12) }}; --ot-cat-line: {{ Otc::rgba($style['color'], .15) }}">
                        <span class="ot-cat-icon">
                            @if($style['icon'])
                                {!! Otc::icon($style['icon'], 18) !!}
                            @else
                                @include('partials.category-icon', ['category' => $category, 'size' => 'sm'])
                            @endif
                        </span>
                        <span class="ot-cat-tab-label">{{ $category->name }}</span>
                        {!! Otc::icon('chevron-right', 16, 'ot-cat-tab-chevron') !!}
                    </button>
                @endforeach
            </div>
            @foreach($tabCategories as $index => $category)
                @php $categoryStores = Otc::categoryStores($category, 9); @endphp
                <div class="ot-cat-panel" role="tabpanel" id="ot-cat-panel-{{ $category->id }}" aria-labelledby="ot-cat-tab-{{ $category->id }}" @if($index !== 0) hidden @endif data-ot-panel>
                    <div class="ot-cat-panel-head">
                        <h3><span class="ot-accent">{{ $category->name }}</span> Stores</h3>
                        <a href="{{ route('categories.show', $category->slug) }}" class="ot-section-link ot-section-link--caps">View all <span aria-hidden="true">→</span></a>
                    </div>
                    @if($categoryStores->isEmpty())
                        <p class="ot-cat-empty">No stores in this category yet.</p>
                    @else
                        <div class="ot-cat-stores">
                            @foreach($categoryStores as $store)
                                @php $storeOffer = $store->bestOfferLabel(); @endphp
                                <a href="{{ route('stores.show', $store->slug) }}" class="ot-cat-store">
                                    @include('themes.ontopcoupon.partials.logo', ['store' => $store, 'class' => 'ot-logo--sm'])
                                    <span>
                                        <strong>{{ $store->name }}</strong>
                                        <small>{{ $storeOffer ?: 'View deals' }}</small>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if(!empty($sections['stores']) && $storeGrid->isNotEmpty())
<section class="ot-section" id="top-stores">
    <div class="ot-container">
        @include('themes.ontopcoupon.partials.section-head', [
            'kicker' => $otc['stores']['kicker'],
            'title' => $otc['stores']['title'],
            'link' => $otc['stores']['link'],
            'url' => route('stores.index'),
            'caps' => true,
        ])
        <div class="ot-store-grid">
            @foreach($storeGrid as $store)
                @include('themes.ontopcoupon.partials.store-card', ['store' => $store])
            @endforeach
        </div>
        {{ $storeGrid->links('themes.ontopcoupon.pagination', ['label' => 'Store pages']) }}
    </div>
</section>
@endif

@if(!empty($sections['how_it_works']))
<section class="ot-section ot-section--alt ot-how">
    <div class="ot-container ot-how-grid">
        <div class="ot-how-art" aria-hidden="true">
            <div class="ot-how-art-glow"></div>
            <div class="ot-how-coupon">
                <div class="ot-how-coupon-top">
                    <span class="ot-how-coupon-icon">{!! Otc::icon('store', 20) !!}</span>
                    <span class="ot-how-coupon-store">
                        <strong>Your Favorite Store</strong>
                        <small>{!! Otc::icon('circle-check', 12, '', 2.4) !!} Verified today</small>
                    </span>
                    <span class="ot-how-coupon-off">20% OFF</span>
                </div>
                <div class="ot-how-coupon-divider"></div>
                <div class="ot-how-coupon-code">
                    <span class="ot-how-code">SAVE20</span>
                    <span class="ot-how-copied">{!! Otc::icon('check', 14, '', 3) !!} Copied</span>
                </div>
            </div>
            <div class="ot-how-checkout">
                <p class="ot-how-checkout-label">CHECKOUT</p>
                <div class="ot-how-checkout-row"><span>Subtotal</span><span>$120.00</span></div>
                <div class="ot-how-checkout-row is-promo"><span>Promo SAVE20</span><span>−$24.00</span></div>
                <div class="ot-how-checkout-total"><span>Total</span><strong>$96.00</strong></div>
            </div>
            <div class="ot-how-pills">
                <span>{!! Otc::icon('shield-check', 13) !!} Verified daily</span>
                <span>{!! Otc::icon('heart', 13) !!} 100% free</span>
                <span>{!! Otc::icon('zap', 13) !!} No sign-up</span>
            </div>
        </div>
        <div class="ot-how-copy">
            <p class="ot-kicker">{{ $otc['how_it_works']['kicker'] }}</p>
            <h2>{{ Otc::accent($otc['how_it_works']['title'], 3, true) }}</h2>
            <ol class="ot-steps">
                @foreach($otc['how_it_works']['steps'] as $index => $step)
                    <li>
                        <span class="ot-step-icon">{!! Otc::icon($stepIcons[$index] ?? 'check', 18) !!}</span>
                        <div>
                            <h3>{{ $step['title'] }}</h3>
                            <p>{{ \App\Support\OntopcouponHomepage::interpolate($step['body'], $stats) }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
            <a href="{{ route('coupons.index') }}" class="ot-btn-solid">{!! Otc::icon('zap', 16) !!} {{ $otc['how_it_works']['cta'] }}</a>
        </div>
    </div>
</section>
@endif

@if(!empty($sections['blog']) && $latestPosts->isNotEmpty())
<section class="ot-section">
    <div class="ot-container">
        @include('themes.ontopcoupon.partials.section-head', [
            'kicker' => $otc['blog']['kicker'],
            'title' => $otc['blog']['title'],
            'link' => $otc['blog']['link'],
            'url' => route('blog.index'),
        ])
        <div class="ot-blog-grid">
            @foreach($latestPosts->take(3) as $post)
                @include('themes.ontopcoupon.partials.blog-card', ['post' => $post, 'index' => $loop->index])
            @endforeach
        </div>
    </div>
</section>
@endif

@if(!empty($sections['cta']))
<section class="ot-section ot-section--alt">
    <div class="ot-container ot-cta-grid">
        <div class="ot-cta ot-cta--dark">
            <div class="ot-cta-glow" aria-hidden="true"></div>
            <span class="ot-pill ot-pill--lime">{{ $otc['cta_shoppers']['kicker'] }}</span>
            <h3>{{ Otc::accent($otc['cta_shoppers']['title'], 3, true) }}</h3>
            <p>{{ \App\Support\OntopcouponHomepage::interpolate($otc['cta_shoppers']['body'], $stats) }}</p>
            <div class="ot-cta-stats">
                <div><strong>{{ number_format($stats['coupons']) }}</strong><span>Active coupons</span></div>
                <div><strong>{{ number_format($stats['stores']) }}</strong><span>Partner stores</span></div>
                <div><strong>100%</strong><span>{{ $otc['cta_shoppers']['stat_listed'] }}</span></div>
            </div>
            <a href="{{ route('coupons.index') }}" class="ot-btn-lime">{{ $otc['cta_shoppers']['button'] }} {!! Otc::icon('arrow-right', 16) !!}</a>
        </div>
        <div class="ot-cta ot-cta--light">
            <span class="ot-pill">{{ $otc['cta_owners']['kicker'] }}</span>
            <h3>{{ Otc::accent($otc['cta_owners']['title'], 3, true) }}</h3>
            <p>{{ $otc['cta_owners']['body'] }}</p>
            <ul class="ot-cta-checks">
                @foreach($otc['cta_owners']['checks'] as $check)
                    @if(filled($check))
                        <li><span>{!! Otc::icon('check', 13, '', 3) !!}</span> {{ $check }}</li>
                    @endif
                @endforeach
            </ul>
            <a href="{{ route('pages.contact') }}" class="ot-btn-navy">{{ $otc['cta_owners']['button'] }} {!! Otc::icon('arrow-right', 16) !!}</a>
        </div>
    </div>
</section>
@endif

@if(!empty($sections['newsletter']))
<section class="ot-section ot-newsletter-wrap">
    <div class="ot-container">
        <div class="ot-newsletter">
            <div class="ot-newsletter-glow" aria-hidden="true"></div>
            <div class="ot-newsletter-copy">
                <span class="ot-pill ot-pill--lime">{!! Otc::icon('mail', 13) !!} {{ $otc['newsletter']['kicker'] }}</span>
                <h2>{{ Otc::accent($otc['newsletter']['title']) }}</h2>
                <p>{{ $otc['newsletter']['body'] }}</p>
            </div>
            <div class="ot-newsletter-form-wrap">
                <form action="{{ route('pages.contact') }}" method="GET" class="ot-newsletter-form">
                    {!! Otc::icon('mail', 18, 'ot-newsletter-mail') !!}
                    <input type="email" name="email" placeholder="{{ $otc['newsletter']['placeholder'] }}" required aria-label="Email address">
                    <button type="submit">{{ $otc['newsletter']['button'] }} {!! Otc::icon('arrow-right', 15) !!}</button>
                </form>
                <div class="ot-newsletter-notes">
                    <span>{!! Otc::icon('circle-check', 14) !!} Weekly, never spam</span>
                    <span>{!! Otc::icon('circle-check', 14) !!} Unsubscribe anytime</span>
                </div>
            </div>
        </div>
    </div>
</section>
@endif
@endsection
