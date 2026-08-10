<article
    class="pch-coupon-card coupon-card{{ $coupon->is_featured ? ' featured' : '' }}{{ ($linkCardToDetail ?? false) ? ' coupon-card--clickable' : '' }}"
    @if($coupon->code) data-code-reveal @endif
>
    @if($linkCardToDetail ?? false)
        <a href="{{ route('coupons.show', $coupon->slug) }}" class="coupon-card-overlay" aria-label="View {{ $coupon->title }}"></a>
    @endif

    <div class="pch-coupon-rail" aria-hidden="true">
        <span class="pch-coupon-rail-icon">%</span>
        <span class="pch-coupon-rail-label">{{ $coupon->code ? 'Code' : 'Deal' }}</span>
    </div>

    <div class="pch-coupon-body">
        <div class="pch-coupon-store">
            @include('partials.store-logo', [
                'store' => $coupon->store,
                'size' => 'md',
                'showVerified' => false,
                'linked' => !($linkCardToDetail ?? false),
            ])
            @if($coupon->store)
                <a href="{{ route('stores.show', $coupon->store->slug) }}" class="pch-coupon-store-name">
                    {{ $coupon->store->name }}
                </a>
            @endif
        </div>

        <h3 class="pch-coupon-title">
            @if($linkCardToDetail ?? false)
                {{ $coupon->title }}
            @else
                <a href="{{ route('coupons.show', $coupon->slug) }}">{{ $coupon->title }}</a>
            @endif
        </h3>

        <div class="pch-coupon-actions">
            <div class="pch-code-box{{ $coupon->code ? '' : ' pch-code-box--deal' }}">
                @if($coupon->code)
                    @include('partials.coupon-code-masked', ['coupon' => $coupon, 'class' => 'pch-code-masked'])
                    <button
                        type="button"
                        class="pch-code-copy"
                        data-reveal-url="{{ route('coupons.reveal', $coupon->slug) }}"
                        data-affiliate-url="{{ $coupon->affiliateClickUrl() }}"
                        data-shop-url="{{ route('coupons.go', $coupon->slug) }}"
                        data-coupon-title="{{ $coupon->title }}"
                        data-coupon-discount="{{ $coupon->discountLabel() }}"
                        data-coupon-store="{{ $coupon->store?->name }}"
                        data-coupon-expires="{{ $coupon->expiresLabel() }}"
                    >Copy</button>
                @else
                    <span class="pch-code-plain">No Need Code</span>
                @endif
            </div>

            <a
                href="{{ route('coupons.go', $coupon->slug) }}"
                class="pch-get-deal"
                target="_blank"
                rel="noopener sponsored"
            >Get Deal</a>
        </div>
    </div>
</article>
