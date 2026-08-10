<section class="pch-deals section">
    <div class="container">
        <p class="pch-deals-eyebrow">Limited windows</p>
        <h2 class="pch-deals-title">Hot coupons &amp; standout deals</h2>
        <p class="pch-deals-subtitle">
            High-signal picks from brands we track — copy a code or open the offer in one tap.
        </p>

        <div class="pch-disclosure-box">
            Promotions can expire or change without notice. We may earn a commission when you shop via our links —
            <a href="{{ route('pages.disclaimer') }}">see disclosure</a>.
        </div>

        @if($hotCoupons->isNotEmpty())
            <div class="pch-coupon-grid">
                @foreach($hotCoupons as $coupon)
                    @include('partials.coupon-card', ['coupon' => $coupon])
                @endforeach
            </div>

            <div class="pch-deals-more">
                <a href="{{ route('coupons.index') }}" class="pch-deals-more-link">Browse all coupons →</a>
            </div>
        @else
            <p class="pch-deals-empty">No active coupons yet. Check back soon.</p>
        @endif
    </div>
</section>
