@php
    $themeCard = 'themes.'.($activeTheme ?? '').'.coupon-card';
@endphp
@if(view()->exists($themeCard))
    @include($themeCard, get_defined_vars())
@else
<article class="coupon-card {{ $coupon->is_featured ? 'featured' : '' }}{{ ($linkCardToDetail ?? false) ? ' coupon-card--clickable' : '' }}">
    @if($linkCardToDetail ?? false)
        <a href="{{ route('coupons.show', $coupon->slug) }}" class="coupon-card-overlay" aria-label="View {{ $coupon->title }}"></a>
    @endif
    <div class="coupon-card-top">
        @include('partials.store-logo', ['store' => $coupon->store, 'size' => 'md', 'showVerified' => false])
        <div class="coupon-card-badges">
            <div class="coupon-badge">{{ $coupon->discountLabel() }}</div>
            <div class="coupon-type">{{ $coupon->typeLabel() }}</div>
        </div>
    </div>
    <h3 class="coupon-title">
        @if($linkCardToDetail ?? false)
            {{ $coupon->title }}
        @else
            <a href="{{ route('coupons.show', $coupon->slug) }}">{{ $coupon->title }}</a>
        @endif
    </h3>
    @if($showDescription ?? false)
        <p class="coupon-description">
            @if(filled($coupon->description))
                {{ \Illuminate\Support\Str::limit(strip_tags($coupon->description), 160) }}
            @endif
        </p>
    @endif
    @if($coupon->expires_at)
        <p class="coupon-expire">Expires: {{ $coupon->expires_at->format('m/d/Y') }}</p>
    @endif
    <div class="coupon-actions">
        @if($coupon->code)
            <div class="coupon-code-split" data-code-reveal>
                <span class="coupon-code-display">
                    @include('partials.coupon-code-masked', ['coupon' => $coupon])
                </span>
                <button type="button" class="btn btn-copy"
                    data-reveal-url="{{ route('coupons.reveal', $coupon->slug) }}"
                    @if($openAffiliateOnCopy ?? false)
                        data-open-affiliate-on-copy="1"
                        data-affiliate-url="{{ $coupon->affiliateClickUrl() }}"
                    @endif
                >
                    Show Code
                </button>
            </div>
            <a href="{{ route('coupons.go', $coupon->slug) }}" class="btn btn-outline" target="_blank" rel="noopener sponsored">Shop Now</a>
        @else
            <a href="{{ route('coupons.go', $coupon->slug) }}" class="btn btn-outline" target="_blank" rel="noopener sponsored">Shop Now</a>
        @endif
    </div>
</article>
@endif
