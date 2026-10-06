@php
    $btnClass = trim('ot-code-btn '.($class ?? ''));
    $delay = \App\Support\OntopcouponTheme::rainbowDelay($coupon->id);
@endphp
@if($coupon->code)
    <button type="button"
        class="btn-copy {{ $btnClass }}"
        data-reveal-url="{{ route('coupons.reveal', $coupon->slug) }}"
        data-affiliate-url="{{ $coupon->affiliateClickUrl() }}"
        data-shop-url="{{ route('coupons.go', $coupon->slug) }}"
        data-coupon-title="{{ $coupon->title }}"
        data-coupon-discount="{{ $coupon->discountLabel() }}"
        data-coupon-store="{{ $coupon->store?->name }}"
        data-coupon-expires="{{ $coupon->expiresLabel() }}"
    >
        <span class="ot-code-tail" aria-hidden="true">{{ \App\Support\OntopcouponTheme::codeTail($coupon) }}</span>
        <span class="ot-code-cover btn-rainbow" style="animation-delay: {{ $delay }}"><span class="otc-get-code-label">Show Coupon</span></span>
    </button>
@else
    <a href="{{ route('coupons.go', $coupon->slug) }}" class="{{ $btnClass }} ot-code-btn--deal" target="_blank" rel="noopener sponsored">
        <span class="ot-code-tail" aria-hidden="true">{!! \App\Support\OntopcouponTheme::icon('arrow-up-right', 16, '', 2.4) !!}</span>
        <span class="ot-code-cover btn-rainbow" style="animation-delay: {{ $delay }}">Get Deal</span>
    </a>
@endif
