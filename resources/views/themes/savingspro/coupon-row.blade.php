<article class="sp-coupon-row {{ $coupon->is_featured ? 'sp-coupon-row--featured' : '' }}" @if($coupon->code) data-code-reveal @endif>
    @if($coupon->is_featured)
        <span class="sp-best-value-ribbon">Best Value</span>
    @endif
    <div class="sp-coupon-row-meta">
        @if($coupon->code)
            <div class="sp-coupon-row-code">
                @include('partials.coupon-code-masked', ['coupon' => $coupon])
            </div>
        @endif
        <div class="sp-coupon-row-discount">{{ $coupon->discountLabel() }}</div>
    </div>
    <div class="sp-coupon-row-body">
        <h3 class="sp-coupon-row-title">
            <a href="{{ route('coupons.show', $coupon->slug) }}">{{ $coupon->title }}</a>
        </h3>
        <p class="sp-coupon-row-desc">
            @if(($showDescription ?? true) && filled($coupon->description))
                {{ \Illuminate\Support\Str::limit(strip_tags($coupon->description), 140) }}
            @endif
        </p>
        @if($coupon->expires_at)
            <p class="sp-coupon-row-expire">{{ $coupon->expiresLabel() }}</p>
        @endif
    </div>
    <div class="sp-coupon-row-action">
        @if($coupon->code)
            <button type="button"
                class="sp-code-copy sp-code-copy--row"
                data-reveal-url="{{ route('coupons.reveal', $coupon->slug) }}"
                data-affiliate-url="{{ $coupon->affiliateClickUrl() }}"
                data-coupon-title="{{ $coupon->title }}"
                data-coupon-discount="{{ $coupon->discountLabel() }}"
                data-coupon-store="{{ $coupon->store?->name }}"
                data-coupon-expires="{{ $coupon->expiresLabel() }}"
                data-shop-url="{{ route('coupons.go', $coupon->slug) }}"
                aria-label="Show promo code">
                Show Code
            </button>
        @else
            <a href="{{ route('coupons.go', $coupon->slug) }}" class="btn btn-primary sp-get-deal-btn" target="_blank" rel="noopener sponsored">Get Deal</a>
        @endif
    </div>
</article>
