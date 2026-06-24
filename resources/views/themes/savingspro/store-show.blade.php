@extends('layouts.app')

@section('title', $store->seoTitle())
@section('meta_description', $store->seoDescription())
@section('canonical', route('stores.show', $store->slug))
@if($store->ogImageUrl())
@section('og_image', $store->ogImageUrl())
@endif

@push('structured_data')
@include('partials.breadcrumb-schema', ['breadcrumbs' => [
    ['name' => 'Home', 'url' => route('home')],
    ['name' => 'Stores', 'url' => route('stores.index')],
    ['name' => $store->name, 'url' => route('stores.show', $store->slug)],
]])
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    "name": @json($store->seoTitle()),
    "url": @json(route('stores.show', $store->slug)),
    "description": @json($store->seoDescription())
}
</script>
@endpush

@push('head_links')
    @include('partials.pagination-seo', ['paginator' => $coupons])
@endpush

@section('content')
<div class="sp-store-page">
    <div class="container">
        <div class="sp-store-hero">
            @include('partials.store-logo', ['store' => $store, 'size' => 'xl', 'showVerified' => false, 'linked' => false])
            <div class="sp-store-hero-body">
                <h1>{{ $store->name }} Promo Codes &amp; Coupons</h1>
                <span class="sp-verified-badge sp-verified-badge--lg">Verified</span>
                @if($store->description)
                    <p class="sp-store-hero-desc">{{ \Illuminate\Support\Str::limit(strip_tags($store->description), 220) }}</p>
                @else
                    <p class="sp-store-hero-desc">Browse verified {{ $store->name }} coupon codes and discount deals updated on {{ config('site.domain') }}.</p>
                @endif
                @if($store->publicWebsiteLabel() && $store->shopUrl())
                    <a href="{{ $store->shopUrl() }}" class="btn btn-outline sp-store-visit" target="_blank" rel="noopener sponsored">Visit {{ $store->name }}</a>
                @endif
            </div>
        </div>

        <div class="sp-store-layout">
            <aside class="sp-store-sidebar">
                @if($store->description)
                    <div class="sp-sidebar-box">
                        <h3>About {{ $store->name }}</h3>
                        <div class="sp-sidebar-about">{!! \Illuminate\Support\Str::limit(strip_tags($store->description), 300) !!}</div>
                        @if(strlen(strip_tags($store->description)) > 300)
                            <a href="#store-full-desc" class="sp-read-more">Read More</a>
                        @endif
                    </div>
                @endif
                @if($similarStores->isNotEmpty())
                    <div class="sp-sidebar-box">
                        <h3>Similar Stores</h3>
                        <ul class="sp-similar-stores">
                            @foreach($similarStores as $similar)
                                <li>
                                    <a href="{{ route('stores.show', $similar->slug) }}">
                                        @include('partials.store-logo', ['store' => $similar, 'size' => 'md', 'showVerified' => false, 'linked' => false])
                                        <span>{{ $similar->name }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if($topCategories->isNotEmpty())
                    <div class="sp-sidebar-box">
                        <h3>Top Categories</h3>
                        <div class="sp-category-tags">
                            @foreach($topCategories as $category)
                                <a href="{{ route('categories.show', $category->slug) }}" class="sp-category-tag">{{ $category->name }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>

            <div class="sp-store-main">
                <div class="sp-store-offers-head">
                    <h2>Active {{ $store->name }} Offers ({{ $coupons->total() }})</h2>
                </div>
                @include('partials.site-affiliate-notice')
                @if($store->description)
                    <div class="store-description-content" id="store-full-desc">{!! $store->description !!}</div>
                @endif
                <div class="sp-coupon-list">
                    @forelse($coupons as $coupon)
                        @include('themes.savingspro.coupon-row', ['coupon' => $coupon])
                    @empty
                        <p class="sp-empty">No coupons available for this store yet.</p>
                    @endforelse
                </div>
                <div class="pagination">{{ $coupons->links() }}</div>
            </div>
        </div>
    </div>
</div>

@include('partials.scroll-coupon-popup')
@endsection
