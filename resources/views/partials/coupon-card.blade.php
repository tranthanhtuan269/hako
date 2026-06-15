@php
    $themeCard = 'themes.'.($activeTheme ?? '').'.coupon-card';
@endphp
@if(view()->exists($themeCard))
    @include($themeCard, get_defined_vars())
@else
<article class="coupon-card {{ $coupon->is_featured ? 'featured' : '' }}">
    <div class="coupon-card-top">
        @include('partials.store-logo', ['store' => $coupon->store, 'size' => 'md', 'showVerified' => false])
        <div class="coupon-card-badges">
            <div class="coupon-badge">{{ $coupon->discountLabel() }}</div>
            <div class="coupon-type">{{ $coupon->typeLabel() }}</div>
        </div>
    </div>
    <h3 class="coupon-title">
        <a href="{{ route('coupons.show', $coupon->slug) }}">
            @if($showDescription ?? false)
                {{ \Illuminate\Support\Str::limit(strip_tags($coupon->description ?: $coupon->title), 160) }}
            @else
                {{ $coupon->title }}
            @endif
        </a>
    </h3>
    <div class="coupon-store">
        @if($coupon->store)
            <a href="{{ route('stores.show', $coupon->store->slug) }}">{{ $coupon->store->name }}</a>
        @endif
        @if($coupon->store?->category)
            <span class="coupon-cat">{{ $coupon->store->category->icon }} {{ $coupon->store->category->name }}</span>
        @endif
    </div>
    @if($coupon->expires_at)
        <p class="coupon-expire">Expires: {{ $coupon->expires_at->format('m/d/Y') }}</p>
    @endif
    <div class="coupon-actions">
        @if($coupon->code)
            <button type="button" class="btn btn-copy" data-code="{{ $coupon->code }}" data-reveal-url="{{ route('coupons.reveal', $coupon->slug) }}">
                Copy Code
            </button>
            <a href="{{ route('coupons.go', $coupon->slug) }}" class="btn btn-outline" target="_blank" rel="noopener sponsored">Shop Now</a>
        @else
            <a href="{{ route('coupons.go', $coupon->slug) }}" class="btn btn-outline" target="_blank" rel="noopener sponsored">Shop Now</a>
        @endif
    </div>
</article>
@endif
