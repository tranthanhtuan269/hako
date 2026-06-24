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
<div class="container">
    <div class="page-header store-page-header">
        @include('partials.store-logo', ['store' => $store, 'size' => 'xl', 'showVerified' => true, 'linked' => false])
        <div>
            <h1>{{ $store->name }}</h1>
            <span class="store-page-verified">Coupons listed on {{ config('site.name') }}</span>
            @if($store->publicWebsiteLabel() && $store->shopUrl())
                <p class="store-page-website">
                    <a href="{{ $store->shopUrl() }}" target="_blank" rel="noopener sponsored">{{ $store->publicWebsiteLabel() }}</a>
                </p>
            @endif
        </div>
    </div>
    @include('partials.site-affiliate-notice')
    @if($store->description)
        <div class="store-description-content">{!! $store->description !!}</div>
    @endif
    <div class="coupon-grid">
        @forelse($coupons as $coupon)
            @include('partials.coupon-card', ['coupon' => $coupon, 'showDescription' => true])
        @empty
            <p>No coupons available for this store yet.</p>
        @endforelse
    </div>
    <div class="pagination">{{ $coupons->links() }}</div>
</div>

@include('partials.scroll-coupon-popup')
@endsection
