@php
    $otcDiscount = $coupon->ticketDisplay();
    $rawTitle = trim((string) $coupon->title);
    $rawDesc = trim((string) ($coupon->description ?? ''));
    $rawAmount = trim((string) ($otcDiscount['amount'] ?? ''));
    $titleIsAmount = strcasecmp($rawTitle, $rawAmount) === 0
        || strcasecmp(preg_replace('/\s+/', '', $rawTitle), preg_replace('/\s+/', '', $rawAmount)) === 0;
    if ($titleIsAmount && filled($rawDesc)) {
        $displayTitle = $rawDesc;
    } else {
        $displayTitle = $rawTitle;
    }
    $subDesc = $rawDesc;
    $discountBadge = $otcDiscount['amount'];
    $discountLabel = $otcDiscount['label'];
@endphp

<article class="otc-coupon-row {{ $coupon->is_featured ? 'otc-coupon-row--featured' : '' }}" @if($coupon->code) data-code-reveal @endif>
    {{-- Left discount badge for desktop layout --}}
    <div class="otc-coupon-badge">
        <span class="otc-coupon-badge-amount">{{ $discountBadge }}</span>
        <span class="otc-coupon-badge-kind">{{ $coupon->code ? 'CODE' : 'DEAL' }}</span>
    </div>

    <div class="otc-coupon-row-watermark" aria-hidden="true">{{ $otcDiscount['watermark'] }}</div>
    <div class="otc-coupon-row-body">
        <div class="otc-coupon-row-meta">
            <span class="otc-coupon-row-kind">{{ $otcDiscount['label'] }}</span>
            <span class="otc-coupon-row-verified">
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                Verified
            </span>
            <span class="otc-coupon-row-dot">•</span>
            <span class="otc-coupon-row-tested">Tested Today</span>
        </div>
        @if($subDesc)
            <p class="otc-coupon-row-title">{{ $subDesc }}</p>
        @endif
    </div>
    <div class="otc-coupon-row-action">
        @if($coupon->code)
            <button type="button"
                class="btn-copy otc-get-code"
                data-reveal-url="{{ route('coupons.reveal', $coupon->slug) }}"
                data-code="{{ $coupon->code }}"
                data-affiliate-url="{{ $coupon->affiliateClickUrl() }}"
                data-shop-url="{{ route('coupons.go', $coupon->slug) }}"
                data-coupon-title="{{ $coupon->title }}"
                data-coupon-discount="{{ $coupon->discountLabel() }}"
                data-coupon-store="{{ $coupon->store?->name }}"
                data-coupon-expires="{{ $coupon->expiresLabel() }}"
            >
                <span class="otc-get-code-peek" data-masked-code>@include('partials.coupon-code-masked', ['coupon' => $coupon])</span>
                <span class="otc-get-code-label">GET CODE</span>
            </button>
        @else
            <a href="{{ route('coupons.go', $coupon->slug) }}" class="otc-get-code otc-get-code--deal" target="_blank" rel="noopener sponsored">
                <span class="otc-get-code-label">GET DEAL</span>
            </a>
        @endif
    </div>
</article>
