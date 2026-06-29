@extends('layouts.app')

@section('title', $coupon->seoTitle())
@section('meta_description', $coupon->seoDescription())
@section('og_title', $coupon->store->name.' — '.$coupon->title)
@section('og_description', $coupon->seoDescription())
@section('canonical', route('coupons.show', $coupon->slug))
@section('og_url', route('coupons.show', $coupon->slug))
@section('og_type', 'article')
@if($coupon->ogImageUrl())
@section('og_image', $coupon->ogImageUrl())
@endif
@section('og_image_alt', $coupon->store->name)

@push('og_meta')
@if($coupon->ogImageUrl())
<meta property="og:image:secure_url" content="{{ \App\Support\Seo::absoluteUrl($coupon->ogImageUrl()) }}">
@endif
<meta property="article:section" content="{{ $coupon->store->category?->name ?? 'Coupons' }}">
@endpush

@push('structured_data')
@include('partials.breadcrumb-schema', ['breadcrumbs' => [
    ['name' => 'Home', 'url' => route('home')],
    ['name' => 'Coupons', 'url' => route('coupons.index')],
    ['name' => $coupon->title, 'url' => route('coupons.show', $coupon->slug)],
]])
<script type="application/ld+json">
@json($coupon->structuredData(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
</script>
@endpush

@section('content')
<div class="container">
    <article class="coupon-detail" @if($coupon->code) data-code-reveal @endif>
        <div class="coupon-detail-brand">
            @include('partials.store-logo', ['store' => $coupon->store, 'size' => 'lg', 'showName' => true])
        </div>
        <span class="coupon-type">{{ $coupon->typeLabel() }}</span>
        @if($coupon->code)
            <div class="code-box-wrap coupon-detail-code">
                <div class="code-box" id="coupon-code">
                    @include('partials.coupon-code-masked', ['coupon' => $coupon])
                </div>
            </div>
        @endif
        <div class="badge-lg">{{ $coupon->discountLabel() }}</div>
        <h1>{{ $coupon->title }}</h1>
        <p class="coupon-detail-meta">
            @if($coupon->store?->category)
                <span class="category-inline">@include('partials.category-icon', ['category' => $coupon->store->category, 'size' => 'sm']) {{ $coupon->store->category->name }}</span>
            @endif
        </p>
        @if($coupon->description)
            <p style="margin:1rem 0;color:var(--muted);">{{ $coupon->description }}</p>
        @endif
        @if($coupon->expires_at)
            <p><small>Expires: {{ $coupon->expires_at->format('m/d/Y g:i A') }}</small></p>
        @endif

        @include('partials.social-share', [
            'url' => route('coupons.show', $coupon->slug),
            'title' => $coupon->seoTitle(),
            'description' => $coupon->seoDescription(),
            'store' => $coupon->store,
            'label' => 'Share this deal',
        ])

        @if($coupon->code)
            <button type="button" class="btn btn-copy btn-primary" data-reveal-url="{{ route('coupons.reveal', $coupon->slug) }}" data-affiliate-url="{{ $coupon->affiliateClickUrl() }}" data-shop-url="{{ route('coupons.go', $coupon->slug) }}" data-coupon-title="{{ $coupon->title }}" data-coupon-discount="{{ $coupon->discountLabel() }}" data-coupon-store="{{ $coupon->store->name }}" data-coupon-expires="{{ $coupon->expiresLabel() }}" style="width:100%;margin-bottom:.5rem;">
                Show &amp; Copy Code
            </button>
        @endif

        <a href="{{ route('coupons.go', $coupon->slug) }}" class="btn btn-primary" style="width:100%;padding:.75rem;" target="_blank" rel="noopener">
            Shop Now at {{ $coupon->store->name }}
        </a>
        <p class="coupon-trust-note">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
            Offer listed for {{ $coupon->store->name }} on {{ config('site.name') }}. Not affiliated with the merchant. Terms apply on their site.
        </p>
    </article>

    @if($related->isNotEmpty())
    <section class="section">
        <h2 class="section-title">More from {{ $coupon->store->name }}</h2>
        <div class="coupon-grid">
            @foreach($related as $item)
                @include('partials.coupon-card', ['coupon' => $item])
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
