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
@php
    use App\Support\OntopcouponTheme as Otc;

    $stats = Otc::stats();
    $q = $q ?? '';
    $total = $coupons->total();
@endphp

@include('themes.ontopcoupon.partials.page-hero', [
    'kicker' => 'BROWSE ALL',
    'title' => Otc::accent('Coupons & Deals'),
    'subtitle' => number_format($total).' active '.\Illuminate\Support\Str::plural('offer', $total).' available',
    'stats' => [
        [$stats['coupons'], 'Active Coupons'],
        [$stats['stores'], 'Partner Stores'],
        [$stats['codes'], 'Promo Codes'],
        [$stats['categories'], 'Categories'],
    ],
])

<section class="ot-section ot-section--tight">
    <div class="ot-container">
        <form action="{{ route('coupons.index') }}" method="GET" class="ot-filter-bar" data-ot-autosubmit>
            <label class="ot-filter-search">
                {!! Otc::icon('search', 15) !!}
                <input type="search" name="q" value="{{ $q }}" placeholder="Search coupons or stores..." aria-label="Search coupons">
            </label>
            <label class="ot-filter-select">
                <select name="type" aria-label="Offer type" data-ot-autosubmit-field>
                    <option value="" @selected(!$type)>All Offers</option>
                    <option value="coupon" @selected($type === 'coupon')>Coupon Codes</option>
                    <option value="discount" @selected($type === 'discount')>Deals</option>
                </select>
                {!! Otc::icon('chevron-down', 15) !!}
            </label>
        </form>

        <div class="ot-coupon-list">
            @forelse($coupons as $coupon)
                @include('themes.ontopcoupon.coupon-row', ['coupon' => $coupon])
            @empty
                <div class="ot-empty">
                    {!! Otc::icon('tag', 28) !!}
                    <p>No offers found{{ $q !== '' ? ' for “'.$q.'”' : '' }}.</p>
                    @if($q !== '' || $type)
                        <a href="{{ route('coupons.index') }}" class="ot-btn-solid ot-btn-sm">Clear filters</a>
                    @endif
                </div>
            @endforelse
        </div>
        {{ $coupons->links('themes.ontopcoupon.pagination', ['label' => 'Coupon pages']) }}
    </div>
</section>
@endsection
