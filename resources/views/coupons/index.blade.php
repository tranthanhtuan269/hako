@extends('layouts.app')

@section('title', 'All Coupons & Promo Codes')
@section('meta_description', 'Browse U.S. coupon codes and discount deals. Filter by promo codes or store offers — updated daily on ' . config('site.name') . '.')
@section('canonical', $coupons->currentPage() > 1 ? $coupons->url($coupons->currentPage()) : route('coupons.index', array_filter(['type' => $type ?? null])))

@push('head_links')
    @include('partials.pagination-seo', ['paginator' => $coupons])
@endpush

@push('structured_data')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    "name": @json('Coupons & Deals — ' . config('site.name')),
    "url": @json(route('coupons.index')),
    "description": @json('Coupon codes and discount deals for U.S. online shoppers.')
}
</script>
@endpush

@section('content')
<div class="container">
    <div class="page-header">
        <h1>Coupons & Discount Deals</h1>
    </div>
    <div class="filter-tabs">
        <a href="{{ route('coupons.index') }}" class="{{ !$type ? 'active' : '' }}">All</a>
        <a href="{{ route('coupons.index', ['type' => 'coupon']) }}" class="{{ $type === 'coupon' ? 'active' : '' }}">Coupon Codes</a>
        <a href="{{ route('coupons.index', ['type' => 'discount']) }}" class="{{ $type === 'discount' ? 'active' : '' }}">Discount Deals</a>
    </div>
    <div class="coupon-grid">
        @forelse($coupons as $coupon)
            @include('partials.coupon-card', ['coupon' => $coupon, 'showDescription' => true, 'linkCardToDetail' => true])
        @empty
            <p>No offers available yet.</p>
        @endforelse
    </div>
    <div class="pagination">{{ $coupons->links() }}</div>
</div>
@endsection
