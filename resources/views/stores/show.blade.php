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
    <!-- chia 2 cột ở đây -->
    <div class="store-page-layout">
        <div class="store-page-column store-page-column--posts">
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
            @include('partials.social-share', [
                'url' => route('stores.show', $store->slug),
                'title' => $store->seoTitle(),
                'label' => 'Share this store',
            ])
            @include('partials.site-affiliate-notice')

            @if($store->description)
                <div class="store-description-content">{!! $store->description !!}</div>
            @endif
        </div>
        <div class="store-page-column store-page-column--coupons">
            <h2 class="store-page-column-title">{{ $store->name }} Coupons &amp; Deals</h2>
            <div class="coupon-grid coupon-grid--stack">
                @forelse($coupons as $coupon)
                    @include('partials.coupon-card', ['coupon' => $coupon, 'showDescription' => true, 'openAffiliateOnCopy' => true])
                @empty
                    <p class="store-page-empty">No coupons available for this store yet.</p>
                @endforelse
            </div>
            <div class="pagination">{{ $coupons->links() }}</div>
        </div>
    </div>
</div>

@include('partials.scroll-coupon-popup')
@endsection
