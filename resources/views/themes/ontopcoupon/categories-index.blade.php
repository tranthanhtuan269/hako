@extends('layouts.app')

@section('title', 'Coupon Categories')
@section('meta_description', 'Browse coupon categories — fashion, electronics, travel, and more. Find deals by topic on ' . config('site.name') . '.')
@section('canonical', route('categories.index'))

@section('content')
@php
    use App\Support\OntopcouponTheme as Otc;

    $stats = Otc::stats();
@endphp

@include('themes.ontopcoupon.partials.page-hero', [
    'kicker' => 'BROWSE BY',
    'title' => Otc::accent('All Categories'),
    'subtitle' => number_format($categories->count()).' categories · browse by interest',
    'stats' => [
        [$stats['categories'], 'Categories'],
        [$stats['stores'], 'Partner Stores'],
        [$stats['coupons'], 'Active Coupons'],
        [$stats['codes'], 'Promo Codes'],
    ],
])

<section class="ot-section ot-section--alt ot-section--tight">
    <div class="ot-container">
        <div class="ot-category-grid">
            @foreach($categories as $index => $category)
                @php
                    $style = Otc::categoryStyle($category, $index);
                    $storeCount = (int) ($category->stores_count ?? 0);
                    $logos = Otc::categoryStores($category, 5);
                @endphp
                <article class="ot-category-card" style="--ot-cat: {{ $style['color'] }}; --ot-cat-bg: {{ Otc::rgba($style['color'], .1) }}; --ot-cat-line: {{ Otc::rgba($style['color'], .22) }}">
                    @if($style['icon'])
                        <span class="ot-category-card-watermark" aria-hidden="true">{!! Otc::icon($style['icon'], 24) !!}</span>
                    @endif
                    <div class="ot-category-card-head">
                        <span class="ot-category-card-icon">
                            @if($style['icon'])
                                {!! Otc::icon($style['icon'], 24) !!}
                            @else
                                @include('partials.category-icon', ['category' => $category, 'size' => 'md'])
                            @endif
                        </span>
                        <div>
                            <h2><a href="{{ route('categories.show', $category->slug) }}">{{ $category->name }}</a></h2>
                            <span class="ot-category-card-count">{{ number_format($storeCount) }} {{ \Illuminate\Support\Str::plural('store', $storeCount) }}</span>
                        </div>
                    </div>
                    <p class="ot-category-card-desc">{{ \Illuminate\Support\Str::limit($category->description ?: 'Top coupons, promo codes and deals in '.$category->name.'.', 110) }}</p>
                    <div class="ot-category-card-logos">
                        @forelse($logos as $store)
                            <a href="{{ route('stores.show', $store->slug) }}" title="{{ $store->name }}">
                                @include('themes.ontopcoupon.partials.logo', ['store' => $store, 'class' => 'ot-logo--xs'])
                            </a>
                        @empty
                            <span class="ot-category-card-soon">Stores coming soon</span>
                        @endforelse
                    </div>
                    <a href="{{ route('categories.show', $category->slug) }}" class="ot-category-card-btn">Explore {!! Otc::icon('arrow-right', 15) !!}</a>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endsection
