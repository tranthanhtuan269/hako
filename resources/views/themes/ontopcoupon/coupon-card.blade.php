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

<article class="otc-coupon-card coupon-card {{ $coupon->is_featured ? 'featured' : '' }}{{ ($linkCardToDetail ?? false) ? ' coupon-card--clickable' : '' }}" @if($coupon->code) data-code-reveal @endif>
    @if($linkCardToDetail ?? false)
        <a href="{{ route('coupons.show', $coupon->slug) }}" class="coupon-card-overlay" aria-label="View {{ $coupon->title }}"></a>
    @endif

    <div class="otc-coupon-card-head">
        <span class="otc-coupon-watermark" aria-hidden="true">{{ $otcDiscount['watermark'] }}</span>
        <div class="otc-coupon-card-meta">
            <div class="otc-coupon-store">
                @include('partials.store-logo', ['store' => $coupon->store, 'size' => 'md', 'showVerified' => false])
                @if($coupon->store)
                    <a href="{{ route('stores.show', $coupon->store->slug) }}" class="otc-coupon-store-name">{{ $coupon->store->name }}</a>
                @endif
            </div>
            <span class="otc-coupon-kind">{{ $otcDiscount['label'] }}</span>
        </div>
        <div class="otc-coupon-amount">
            <span class="otc-coupon-amount-value">{{ $otcDiscount['amount'] }}</span>
            @if($otcDiscount['suffix'] !== '')
                <span class="otc-coupon-amount-suffix">{{ $otcDiscount['suffix'] }}</span>
            @endif
        </div>
    </div>

    <div class="otc-coupon-perforation" aria-hidden="true">
        <span class="otc-coupon-notch otc-coupon-notch--left"></span>
        <span class="otc-coupon-dash"></span>
        <span class="otc-coupon-notch otc-coupon-notch--right"></span>
    </div>

    <div class="otc-coupon-card-body">
        <h3 class="otc-coupon-title coupon-title">
            @if($linkCardToDetail ?? false)
                {{ $coupon->title }}
            @else
                <a href="{{ route('coupons.show', $coupon->slug) }}">{{ $coupon->title }}</a>
            @endif
        </h3>
        @if(filled($coupon->description))
            <p class="otc-coupon-desc">{{ \Illuminate\Support\Str::limit(strip_tags($coupon->description), 90) }}</p>
        @endif

        <div class="otc-coupon-actions coupon-actions">
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
                    <span class="otc-code-btn-code">@include('partials.coupon-code-masked', ['coupon' => $coupon])</span>
                </button>
            @else
                <a href="{{ route('coupons.go', $coupon->slug) }}" class="otc-code-btn otc-code-btn--deal" target="_blank" rel="noopener sponsored">
                    <span class="otc-code-btn-label">Get Deal</span>
                    <span class="otc-code-btn-code">Shop →</span>
                </a>
            @endif
        </div>
    </div>
</article>
