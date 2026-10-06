@php
    $variant = $variant ?? 'catalog';
    $discount = \App\Support\OntopcouponTheme::discount($coupon);
    $ticket = $coupon->ticketDisplay();
    $store = $coupon->store;
    $title = trim((string) $coupon->title) ?: $coupon->discountLabel();
@endphp
<article @class(['ot-coupon-row', 'ot-coupon-row--store' => $variant === 'store', 'is-featured' => $coupon->is_featured])>
    @if($variant !== 'store' && $store)
        <a href="{{ route('stores.show', $store->slug) }}" class="ot-coupon-row-store" aria-label="{{ $store->name }} coupons">
            @include('themes.ontopcoupon.partials.logo', ['store' => $store, 'class' => 'ot-logo--fill'])
        </a>
    @endif

    <div @class(['ot-coupon-row-discount', 'is-deal' => $discount['kind'] === 'deal'])>
        <span class="ot-coupon-row-value">{{ $discount['value'] }}</span>
        @if($discount['suffix'] !== '')
            <span class="ot-coupon-row-suffix">{{ $discount['suffix'] }}</span>
        @endif
    </div>

    <div class="ot-coupon-row-body">
        @if($variant === 'store')
            <div class="ot-coupon-row-tags">
                <span class="ot-tag ot-tag--kind">{{ $ticket['label'] }}</span>
                <span class="ot-tag ot-tag--verified">{!! \App\Support\OntopcouponTheme::icon('circle-check', 11, '', 2.4) !!} VERIFIED</span>
                @if($discount['kind'] !== 'deal')
                    <span class="ot-tag">{{ $coupon->discountLabel() }}</span>
                @endif
                @if($coupon->expires_at)
                    <span class="ot-tag ot-tag--muted">{{ $coupon->expiresLabel() }}</span>
                @endif
            </div>
        @endif
        <h3 class="ot-coupon-row-title"><a href="{{ route('coupons.show', $coupon->slug) }}">{{ $title }}</a></h3>
        @if($store)
            <a href="{{ route('stores.show', $store->slug) }}" class="ot-coupon-row-storename">{{ $store->name }}</a>
        @endif
    </div>

    <div class="ot-coupon-row-action">
        @include('themes.ontopcoupon.partials.code-button', ['coupon' => $coupon, 'class' => $variant === 'store' ? 'ot-code-btn--green' : ''])
    </div>
</article>
