@if(!empty($scrollPopup) && !empty($scrollPopup['coupons']))
<div class="scroll-coupon-popup" id="scroll-coupon-popup" hidden aria-hidden="true">
    <div class="scroll-coupon-popup-backdrop" data-scroll-popup-close></div>
    <div class="scroll-coupon-popup-dialog" role="dialog" aria-modal="true" aria-labelledby="scroll-coupon-popup-title">
        <button type="button" class="scroll-coupon-popup-close" data-scroll-popup-close aria-label="Close">&times;</button>

        <div class="scroll-coupon-popup-header">
            @if(!empty($scrollPopup['storeLogo']))
                <img src="{{ $scrollPopup['storeLogo'] }}" alt="{{ $scrollPopup['storeName'] }}" class="scroll-coupon-popup-logo" width="56" height="56">
            @endif
            <div>
                <span class="scroll-coupon-popup-badge">Exclusive Offers</span>
                <h2 class="scroll-coupon-popup-title" id="scroll-coupon-popup-title">
                    {{ $scrollPopup['storeName'] }} Coupons
                </h2>
                <p class="scroll-coupon-popup-subtitle">Save with these verified promo codes</p>
            </div>
        </div>

        <ul class="scroll-coupon-popup-list">
            @foreach($scrollPopup['coupons'] as $coupon)
                <li class="scroll-coupon-popup-item" @if(!empty($coupon['hasCode'])) data-code-reveal @endif>
                    <div class="scroll-coupon-popup-item-main">
                        @if(!empty($coupon['hasCode']) || !empty($coupon['discount']))
                            <div class="scroll-coupon-popup-offer-meta">
                                @if(!empty($coupon['hasCode']) && !empty($coupon['codeMasked']))
                                    <span class="scroll-coupon-popup-code-preview">
                                        <span class="coupon-code-masked" data-masked-code>
                                            <span class="coupon-code-visible">{{ $coupon['codeMasked']['visible'] }}</span><span class="coupon-code-blur">{{ $coupon['codeMasked']['hidden'] }}</span>
                                        </span>
                                    </span>
                                @endif
                                @if(!empty($coupon['discount']))
                                    <span class="scroll-coupon-popup-discount">{{ $coupon['discount'] }}</span>
                                @endif
                            </div>
                        @endif
                        <div>
                            <strong class="scroll-coupon-popup-item-title">{{ $coupon['title'] }}</strong>
                            @if(!empty($coupon['expires']))
                                <span class="scroll-coupon-popup-expires">{{ $coupon['expires'] }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="scroll-coupon-popup-item-action">
                        @if(!empty($coupon['hasCode']))
                            <div class="scroll-coupon-popup-code">
                                <button type="button"
                                    class="scroll-coupon-popup-copy"
                                    data-reveal-url="{{ $coupon['revealUrl'] }}"
                                    data-go-url="{{ $coupon['goUrl'] }}"
                                    data-shop-url="{{ $coupon['goUrl'] }}"
                                    data-affiliate-url="{{ $coupon['affiliateUrl'] ?? $scrollPopup['affiliateUrl'] }}"
                                    data-coupon-title="{{ $coupon['title'] }}"
                                    data-coupon-discount="{{ $coupon['discount'] ?? '' }}"
                                    data-coupon-store="{{ $scrollPopup['storeName'] }}"
                                    data-coupon-expires="{{ $coupon['expires'] ?? '' }}">
                                    Show Code
                                </button>
                            </div>
                        @else
                            <a href="{{ $coupon['goUrl'] }}"
                                class="btn btn-primary scroll-coupon-popup-deal"
                                target="_blank"
                                rel="noopener sponsored"
                                data-scroll-popup-deal>
                                Get Deal
                            </a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        <a href="{{ $scrollPopup['affiliateUrl'] }}"
            class="btn btn-primary scroll-coupon-popup-shop"
            target="_blank"
            rel="noopener sponsored"
            data-scroll-popup-deal>
            Shop at {{ $scrollPopup['storeName'] }}
        </a>
    </div>
</div>

@push('scripts')
<script>
    window.__scrollCouponPopup = @json($scrollPopup);
</script>
<script src="{{ asset('js/scroll-coupon-popup.js') }}?v={{ filemtime(public_path('js/scroll-coupon-popup.js')) }}"></script>
@endpush
@endif
