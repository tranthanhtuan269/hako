@php
    $discount = \App\Support\OntopcouponTheme::discount($coupon);
    $store = $coupon->store;
@endphp
<article class="ot-coupon-card coupon-card{{ $coupon->is_featured ? ' featured' : '' }}">
    <div class="ot-coupon-card-head">
        @if($store)
            <a href="{{ route('stores.show', $store->slug) }}" class="ot-coupon-card-store">
                @include('themes.ontopcoupon.partials.logo', ['store' => $store, 'class' => 'ot-logo--sm'])
                <span>{{ $store->name }}</span>
            </a>
        @endif
        <span @class(['ot-coupon-card-discount', 'is-deal' => $discount['kind'] === 'deal'])>{{ trim($discount['value'].' '.$discount['suffix']) }}</span>
    </div>
    <h3 class="ot-coupon-card-title coupon-title"><a href="{{ route('coupons.show', $coupon->slug) }}">{{ $coupon->title }}</a></h3>
    @if(($showDescription ?? false) && filled($coupon->description))
        <p class="ot-coupon-card-desc">{{ \Illuminate\Support\Str::limit(strip_tags($coupon->description), 110) }}</p>
    @endif
    <div class="ot-coupon-card-action coupon-actions">
        @include('themes.ontopcoupon.partials.code-button', ['coupon' => $coupon, 'class' => 'ot-code-btn--block'])
    </div>
</article>
