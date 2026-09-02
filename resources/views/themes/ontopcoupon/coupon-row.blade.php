@php
    $otcDiscount = match ($coupon->discount_type) {
        'percent' => [
            'label' => 'PERCENTAGE',
            'amount' => (int) $coupon->discount_value.'%',
            'suffix' => 'OFF',
            'watermark' => (int) $coupon->discount_value.'%',
        ],
        'fixed' => [
            'label' => 'FLAT',
            'amount' => '$'.number_format((float) $coupon->discount_value, 0, '.', ','),
            'suffix' => 'OFF',
            'watermark' => '$'.number_format((float) $coupon->discount_value, 0, '.', ','),
        ],
        'free_shipping' => [
            'label' => 'FREE SHIP',
            'amount' => 'FREE',
            'suffix' => 'SHIP',
            'watermark' => 'FREE',
        ],
        default => [
            'label' => $coupon->type === 'coupon' ? 'CODE' : 'DEAL',
            'amount' => $coupon->discountLabel(),
            'suffix' => '',
            'watermark' => $coupon->type === 'coupon' ? 'CODE' : 'DEAL',
        ],
    };
@endphp

<article class="otc-coupon-row {{ $coupon->is_featured ? 'otc-coupon-row--featured' : '' }}" @if($coupon->code) data-code-reveal @endif>
    <div class="otc-coupon-row-badge">
        <span class="otc-coupon-kind">{{ $otcDiscount['label'] }}</span>
        <div class="otc-coupon-amount">
            <span class="otc-coupon-amount-value">{{ $otcDiscount['amount'] }}</span>
            @if($otcDiscount['suffix'] !== '')
                <span class="otc-coupon-amount-suffix">{{ $otcDiscount['suffix'] }}</span>
            @endif
        </div>
        @if($coupon->code)
            <div class="otc-coupon-row-mask">
                @include('partials.coupon-code-masked', ['coupon' => $coupon])
            </div>
        @endif
    </div>
    <div class="otc-coupon-row-body">
        <h3 class="otc-coupon-row-title">
            <a href="{{ route('coupons.show', $coupon->slug) }}">{{ $coupon->title }}</a>
        </h3>
        @if(($showDescription ?? true) && filled($coupon->description))
            <p class="otc-coupon-row-desc">{{ \Illuminate\Support\Str::limit(strip_tags($coupon->description), 140) }}</p>
        @endif
        @if($coupon->expires_at)
            <p class="otc-coupon-row-expire">{{ $coupon->expiresLabel() }}</p>
        @endif
    </div>
    <div class="otc-coupon-row-action">
        @if($coupon->code)
            <button type="button"
                class="btn-copy otc-code-btn"
                data-reveal-url="{{ route('coupons.reveal', $coupon->slug) }}"
                data-affiliate-url="{{ $coupon->affiliateClickUrl() }}"
                data-shop-url="{{ route('coupons.go', $coupon->slug) }}"
                data-coupon-title="{{ $coupon->title }}"
                data-coupon-discount="{{ $coupon->discountLabel() }}"
                data-coupon-store="{{ $coupon->store?->name }}"
                data-coupon-expires="{{ $coupon->expiresLabel() }}"
            >
                <span class="otc-code-btn-label">Get promocode</span>
                <span class="otc-code-btn-code">Copy</span>
            </button>
        @else
            <a href="{{ route('coupons.go', $coupon->slug) }}" class="otc-code-btn otc-code-btn--deal" target="_blank" rel="noopener sponsored">
                <span class="otc-code-btn-label">Get Deal</span>
                <span class="otc-code-btn-code">Shop →</span>
            </a>
        @endif
    </div>
</article>
