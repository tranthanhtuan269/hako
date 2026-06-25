<article class="sp-coupon-row {{ $coupon->is_featured ? 'sp-coupon-row--featured' : '' }}">
    @if($coupon->is_featured)
        <span class="sp-best-value-ribbon">Best Value</span>
    @endif
    <div class="sp-coupon-row-discount">{{ $coupon->discountLabel() }}</div>
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
            <div class="sp-code-split sp-code-split--row" data-code-reveal>
                <span class="sp-code-text">
                    @include('partials.coupon-code-masked', ['coupon' => $coupon])
                </span>
                <button type="button"
                    class="sp-code-copy"
                    data-reveal-url="{{ route('coupons.reveal', $coupon->slug) }}"
                    data-coupon-title="{{ $coupon->title }}"
                    data-coupon-discount="{{ $coupon->discountLabel() }}"
                    data-coupon-store="{{ $coupon->store?->name }}"
                    data-coupon-expires="{{ $coupon->expiresLabel() }}"
                    data-shop-url="{{ route('coupons.go', $coupon->slug) }}"
                    @if($openAffiliateOnCopy ?? false)
                        data-open-affiliate-on-copy="1"
                        data-affiliate-url="{{ $coupon->affiliateClickUrl() }}"
                    @endif
                    aria-label="Copy promo code">
                    COPY
                </button>
            </div>
        @else
            <a href="{{ route('coupons.go', $coupon->slug) }}" class="btn btn-primary sp-get-deal-btn" target="_blank" rel="noopener sponsored">Get Deal</a>
        @endif
    </div>
</article>
