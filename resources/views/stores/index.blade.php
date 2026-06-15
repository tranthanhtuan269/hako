@extends('layouts.app')

@section('title', 'Coupon Stores — Shop by Retailer')
@section('meta_description', 'Find coupon codes by store — Amazon, Walmart, Target, and hundreds of U.S. retailers on ' . config('site.name') . '.')
@section('canonical', $stores->currentPage() > 1 ? $stores->url($stores->currentPage()) : route('stores.index'))

@push('head_links')
    @include('partials.pagination-seo', ['paginator' => $stores])
@endpush

@section('content')
<div class="container">
    <div class="page-header"><h1>Stores</h1></div>
    <div class="store-grid store-grid--logos">
        @foreach($stores as $store)
            <a href="{{ route('stores.show', $store->slug) }}" class="store-chip store-chip--logo">
                @include('partials.store-logo', ['store' => $store, 'size' => 'lg', 'showVerified' => false, 'linked' => false])
                <strong>{{ $store->name }}</strong>
                <small>{{ $store->coupons_count }} {{ Str::plural('offer', $store->coupons_count) }}</small>
                <span class="store-chip-verified">Coupons listed on {{ config('site.name') }}</span>
            </a>
        @endforeach
    </div>
    <div class="pagination">{{ $stores->links() }}</div>
</div>
@endsection
