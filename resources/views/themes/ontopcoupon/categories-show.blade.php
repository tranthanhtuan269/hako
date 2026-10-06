@extends('layouts.app')

@section('title', $category->seoTitle())
@section('meta_description', $category->seoDescription())
@section('canonical', $coupons->currentPage() > 1 ? $coupons->url($coupons->currentPage()) : route('categories.show', $category->slug))

@push('head_links')
    @include('partials.pagination-seo', ['paginator' => $coupons])
@endpush

@push('structured_data')
@include('partials.breadcrumb-schema', ['breadcrumbs' => [
    ['name' => 'Home', 'url' => route('home')],
    ['name' => 'Categories', 'url' => route('categories.index')],
    ['name' => $category->name, 'url' => route('categories.show', $category->slug)],
]])
@endpush

@section('content')
@php
    use App\Support\OntopcouponTheme as Otc;

    $allCategories = \App\Models\Category::active()->orderBy('sort_order')->get();
    $style = Otc::categoryStyle($category, max(0, $allCategories->search(fn ($c) => $c->id === $category->id)));
    $type = $type ?? null;
    $total = $coupons->total();
    $filters = [
        [null, 'All'],
        ['coupon', 'Codes'],
        ['discount', 'Deals'],
    ];
@endphp

<section class="ot-detail-hero">
    <div class="ot-container ot-container--wide">
        <nav class="ot-crumbs" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><span>/</span>
            <a href="{{ route('categories.index') }}">Categories</a><span>/</span>
            <span class="is-current">{{ $category->name }}</span>
        </nav>
        <div class="ot-detail-hero-brand">
            <span class="ot-detail-hero-icon">
                @if($style['icon'])
                    {!! Otc::icon($style['icon'], 30) !!}
                @else
                    @include('partials.category-icon', ['category' => $category, 'size' => 'lg'])
                @endif
            </span>
            <div>
                <h1>{{ $category->name }}</h1>
                @if($category->description)
                    <p>{{ $category->description }}</p>
                @endif
            </div>
        </div>
        <div class="ot-detail-stats">
            <div><strong>{{ number_format($total) }}</strong><span>ACTIVE OFFERS</span></div>
            <div><strong>{{ Otc::categoryTopPercent($category) }}%</strong><span>TOP DISCOUNT</span></div>
        </div>
    </div>
</section>

<section class="ot-detail-body">
    <div class="ot-container ot-container--wide ot-detail-layout ot-detail-layout--category">
        <aside class="ot-side-nav">
            <div class="ot-panel">
                <p class="ot-side-nav-title">Categories</p>
                <nav>
                    @foreach($allCategories as $index => $item)
                        @php $itemStyle = Otc::categoryStyle($item, $index); @endphp
                        <a href="{{ route('categories.show', $item->slug) }}" @class(['is-active' => $item->id === $category->id])>
                            <span class="ot-side-nav-icon">
                                @if($itemStyle['icon'])
                                    {!! Otc::icon($itemStyle['icon'], 16) !!}
                                @else
                                    @include('partials.category-icon', ['category' => $item, 'size' => 'sm'])
                                @endif
                            </span>
                            <span>{{ $item->name }}</span>
                            @if($item->id === $category->id)
                                {!! Otc::icon('arrow-right', 14, 'ot-side-nav-arrow') !!}
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        <div class="ot-detail-main">
            <div class="ot-chip-scroll">
                @foreach($allCategories as $index => $item)
                    @php $itemStyle = Otc::categoryStyle($item, $index); @endphp
                    <a href="{{ route('categories.show', $item->slug) }}" @class(['ot-chip', 'is-active' => $item->id === $category->id])>
                        @if($itemStyle['icon'])
                            {!! Otc::icon($itemStyle['icon'], 14) !!}
                        @endif
                        {{ $item->name }}
                    </a>
                @endforeach
            </div>

            <div class="ot-filter-pills">
                @foreach($filters as [$value, $label])
                    <a href="{{ route('categories.show', array_filter(['slug' => $category->slug, 'type' => $value])) }}" @class(['ot-filter-pill', 'is-active' => $type === $value])>{{ $label }}</a>
                @endforeach
            </div>
            <p class="ot-result-count">{{ number_format($total) }} {{ \Illuminate\Support\Str::plural('offer', $total) }}</p>

            @if($coupons->isEmpty())
                <div class="ot-empty ot-empty--dashed">
                    @if($style['icon'])
                        {!! Otc::icon($style['icon'], 32) !!}
                    @endif
                    <p>No offers yet in this category</p>
                    <a href="{{ route('coupons.index') }}" class="ot-btn-solid ot-btn-sm">Browse All Coupons {!! Otc::icon('arrow-right', 14) !!}</a>
                </div>
            @else
                <div class="ot-coupon-list ot-coupon-list--compact">
                    @foreach($coupons as $coupon)
                        @include('themes.ontopcoupon.coupon-row', ['coupon' => $coupon, 'variant' => 'store'])
                    @endforeach
                </div>
                {{ $coupons->links('themes.ontopcoupon.pagination', ['label' => 'Offer pages']) }}
            @endif
        </div>
    </div>
</section>
@endsection
