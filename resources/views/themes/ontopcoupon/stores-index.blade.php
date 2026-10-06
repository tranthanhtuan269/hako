@extends('layouts.app')

@section('title', 'Coupon Stores — Shop by Retailer')
@section('meta_description', 'Find coupon codes by store — Amazon, Walmart, Target, and hundreds of U.S. retailers on ' . config('site.name') . '.')
@section('canonical', $stores->currentPage() > 1 ? $stores->url($stores->currentPage()) : route('stores.index'))

@push('head_links')
    @include('partials.pagination-seo', ['paginator' => $stores])
@endpush

@section('content')
@php
    use App\Support\OntopcouponTheme as Otc;

    $stats = Otc::stats();
    $q = $q ?? '';
@endphp

@include('themes.ontopcoupon.partials.page-hero', [
    'kicker' => 'BROWSE ALL',
    'title' => Otc::accent('Top Stores'),
    'subtitle' => number_format($stats['stores']).' stores with active deals',
    'stats' => [
        [$stats['stores'], 'Partner Stores'],
        [$stats['coupons'], 'Active Coupons'],
        [$stats['codes'], 'Promo Codes'],
        [$stats['categories'], 'Categories'],
    ],
])

<section class="ot-section ot-section--alt ot-section--tight">
    <div class="ot-container">
        <form action="{{ route('stores.index') }}" method="GET" class="ot-pill-search" role="search">
            {!! Otc::icon('search', 18) !!}
            <input type="search" name="q" value="{{ $q }}" placeholder="Search stores..." aria-label="Search stores">
        </form>

        <div class="ot-store-rows">
            @forelse($stores as $store)
                @php
                    $palette = Otc::palette($store->slug);
                    $offer = $store->bestOfferLabel();
                    $count = (int) $store->coupons_count;
                @endphp
                <article class="ot-store-row" style="--ot-ink: {{ $palette['ink'] }}; --ot-solid: {{ $palette['solid'] }}; --ot-tint: {{ $palette['tint'] }}; --ot-line: {{ $palette['line'] }}">
                    <a href="{{ route('stores.show', $store->slug) }}" class="ot-store-row-logo" tabindex="-1" aria-hidden="true">
                        @include('themes.ontopcoupon.partials.logo', ['store' => $store, 'class' => 'ot-logo--fill'])
                    </a>
                    <div class="ot-store-row-body">
                        <div class="ot-store-row-title">
                            <a href="{{ route('stores.show', $store->slug) }}">{{ $store->name }}</a>
                            <span class="ot-store-row-count">{{ $count }} {{ \Illuminate\Support\Str::plural('deal', $count) }}</span>
                        </div>
                        <p>{{ \App\Support\OntopcouponTheme::storeSummary($store) }}</p>
                    </div>
                    <div class="ot-store-row-actions">
                        @if($offer)
                            <span class="ot-store-row-offer"><span>{!! Otc::icon('percent', 12, '', 2.6) !!}</span>{{ $offer }}</span>
                        @endif
                        <button type="button" class="ot-share-btn" data-ot-share data-share-url="{{ route('stores.show', $store->slug) }}" data-share-title="{{ $store->name }} coupons" aria-label="Share {{ $store->name }}">
                            {!! Otc::icon('share', 15) !!}
                        </button>
                        <a href="{{ route('stores.show', $store->slug) }}" class="ot-btn-rainbow btn-rainbow" style="animation-delay: {{ Otc::rainbowDelay($store->id) }}">View Coupons {!! Otc::icon('arrow-right', 16) !!}</a>
                    </div>
                </article>
            @empty
                <div class="ot-empty">
                    {!! Otc::icon('store', 28) !!}
                    <p>No stores found{{ $q !== '' ? ' for “'.$q.'”' : '' }}.</p>
                    @if($q !== '')
                        <a href="{{ route('stores.index') }}" class="ot-btn-solid ot-btn-sm">Show all stores</a>
                    @endif
                </div>
            @endforelse
        </div>
        {{ $stores->links('themes.ontopcoupon.pagination', ['label' => 'Store pages']) }}
    </div>
</section>
@endsection
