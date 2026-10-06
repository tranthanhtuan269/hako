@php
    $offer = $store->bestOfferLabel();
    $description = \App\Support\OntopcouponTheme::storeSummary($store, 140);
@endphp
<article class="ot-store-card">
    <div class="ot-store-card-top">
        <a href="{{ route('stores.show', $store->slug) }}" class="ot-store-card-logo" tabindex="-1" aria-hidden="true">
            @include('themes.ontopcoupon.partials.logo', ['store' => $store, 'class' => 'ot-logo--md'])
        </a>
        <a href="{{ route('stores.show', $store->slug) }}" class="ot-store-card-name">{{ $store->name }}</a>
        <button type="button" class="ot-share-btn" data-ot-share data-share-url="{{ route('stores.show', $store->slug) }}" data-share-title="{{ $store->name }} coupons" aria-label="Share {{ $store->name }}">
            {!! \App\Support\OntopcouponTheme::icon('share', 15) !!}
        </button>
    </div>
    <p class="ot-store-card-desc">{{ $description }}</p>
    <a href="{{ route('stores.show', $store->slug) }}" class="ot-store-card-offer">
        <span class="ot-store-card-offer-icon">{!! \App\Support\OntopcouponTheme::icon('percent', 13, '', 2.6) !!}</span>
        <span>{{ $offer ?: 'View all deals' }}</span>
        {!! \App\Support\OntopcouponTheme::icon('arrow-right', 15, 'ot-store-card-offer-arrow') !!}
    </a>
</article>
