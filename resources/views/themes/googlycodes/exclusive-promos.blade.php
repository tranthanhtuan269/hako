@php
    $exclusiveCoupons = ($exclusiveCoupons ?? collect())->take(8);
@endphp

@if($exclusiveCoupons->isNotEmpty())
<section class="gc-exclusive section">
    <div class="container">
        <div class="gc-exclusive-head">
            <div class="gc-exclusive-title">
                <span class="gc-exclusive-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                </span>
                <h2>Exclusive Promo Codes</h2>
            </div>
        </div>

        <div class="gc-exclusive-grid">
            @foreach($exclusiveCoupons as $coupon)
                <article class="gc-exclusive-card coupon-card{{ $coupon->is_featured ? ' is-featured' : '' }}" @if($coupon->code) data-code-reveal @endif>
                    <div class="gc-exclusive-logo">
                        @include('partials.store-logo', ['store' => $coupon->store, 'size' => 'lg', 'showVerified' => false, 'linked' => false])
                    </div>

                    <div class="gc-exclusive-body">
                        <span class="gc-exclusive-badge" aria-hidden="true">★ EXCLUSIVE</span>

                        <h3 class="gc-exclusive-offer">
                            <a href="{{ route('coupons.show', $coupon->slug) }}">{{ $coupon->title }}</a>
                        </h3>

                        <div class="gc-exclusive-meta">
                            <span class="gc-exclusive-pill gc-exclusive-pill--verified">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                Verified
                            </span>
                            <span class="gc-exclusive-pill gc-exclusive-pill--views">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.4 7.2H22l-6 4.8 2.3 7L12 16.8 5.7 21l2.3-7-6-4.8h7.6z"/></svg>
                                {{ number_format((int) $coupon->click_count) }} views
                            </span>
                        </div>

                        <div class="gc-exclusive-actions">
                            @if($coupon->code)
                                <button type="button"
                                    class="btn btn-copy gc-exclusive-cta"
                                    data-reveal-url="{{ route('coupons.reveal', $coupon->slug) }}"
                                    data-affiliate-url="{{ $coupon->affiliateClickUrl() }}"
                                    data-shop-url="{{ route('coupons.go', $coupon->slug) }}"
                                    data-coupon-title="{{ $coupon->title }}"
                                    data-coupon-discount="{{ $coupon->discountLabel() }}"
                                    data-coupon-store="{{ $coupon->store?->name }}"
                                    data-coupon-expires="{{ $coupon->expiresLabel() }}"
                                >
                                    Show Coupon Code <span aria-hidden="true">→</span>
                                </button>
                            @else
                                <a href="{{ route('coupons.go', $coupon->slug) }}" class="gc-exclusive-cta" target="_blank" rel="noopener sponsored">
                                    Get Deal <span aria-hidden="true">→</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
