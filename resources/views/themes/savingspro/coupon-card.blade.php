<article class="sp-coupon-card coupon-card {{ $coupon->is_featured ? 'featured sp-coupon-card--featured' : '' }}{{ ($linkCardToDetail ?? false) ? ' coupon-card--clickable' : '' }}" @if($coupon->code) data-code-reveal @endif>
    @if($linkCardToDetail ?? false)
        <a href="{{ route('coupons.show', $coupon->slug) }}" class="coupon-card-overlay" aria-label="View {{ $coupon->title }}"></a>
    @endif
    <div class="sp-coupon-card-head">
        @include('partials.store-logo', ['store' => $coupon->store, 'size' => 'md', 'showVerified' => false])
        <span class="sp-verified-badge">Verified</span>
    </div>
    @if($coupon->code)
        <div class="sp-coupon-code-preview">
            @include('partials.coupon-code-masked', ['coupon' => $coupon])
        </div>
    @endif
    <div class="sp-coupon-discount">{{ $coupon->discountLabel() }}</div>
    <h3 class="coupon-title sp-coupon-title">
        @if($linkCardToDetail ?? false)
            {{ $coupon->title }}
        @else
            <a href="{{ route('coupons.show', $coupon->slug) }}">{{ $coupon->title }}</a>
        @endif
    </h3>
    @if($showDescription ?? false)
        <p class="coupon-description sp-coupon-description">
            @if(filled($coupon->description))
                {{ \Illuminate\Support\Str::limit(strip_tags($coupon->description), 120) }}
            @endif
        </p>
    @endif
    @if($coupon->store)
        <p class="sp-coupon-store-name">{{ $coupon->store->name }}</p>
    @endif
    @if($coupon->expires_at)
        <p class="coupon-expire sp-coupon-expire">{{ $coupon->expiresLabel() }}</p>
    @endif
    <div class="coupon-actions sp-coupon-actions">
        @if($coupon->code)
            <button type="button"
                class="sp-code-copy sp-code-copy--solo"
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
