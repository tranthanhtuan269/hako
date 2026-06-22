@if($store && $storeCoupons->isNotEmpty())
@php($affiliateUrl = $store->affiliate_url ?: $store->shopUrl())
<div
    id="blog-coupon-popup"
    class="blog-coupon-popup"
    hidden
    data-storage-key="blog-coupon-popup-{{ $post->id }}"
    @if($affiliateUrl) data-affiliate-url="{{ $affiliateUrl }}" @endif
    role="dialog"
    aria-labelledby="blog-coupon-popup-title"
    aria-modal="true"
>
    <button type="button" class="blog-coupon-popup-backdrop" aria-label="Close and visit store"></button>
    <div class="blog-coupon-popup-panel">
        <div class="blog-coupon-popup-header">
            <div class="blog-coupon-popup-heading">
                @include('partials.store-logo', ['store' => $store, 'size' => 'sm', 'showVerified' => false, 'linked' => false])
                <div>
                    <h2 id="blog-coupon-popup-title">{{ $store->name }} coupons</h2>
                    <p>{{ $storeCoupons->count() }} active offer{{ $storeCoupons->count() === 1 ? '' : 's' }} right now</p>
                </div>
            </div>
            <button type="button" class="blog-coupon-popup-close" aria-label="Close and visit store">&times;</button>
        </div>
        <div class="blog-coupon-popup-list">
            @foreach($storeCoupons as $coupon)
                <article class="blog-coupon-popup-item">
                    <div class="blog-coupon-popup-meta">
                        <span class="blog-coupon-popup-badge">{{ $coupon->discountLabel() }}</span>
                        <span class="blog-coupon-popup-type">{{ $coupon->typeLabel() }}</span>
                    </div>
                    <h3>{{ $coupon->title }}</h3>
                    @if($coupon->code)
                        <div class="blog-coupon-popup-actions">
                            <button type="button" class="btn btn-copy btn-sm" data-code="{{ $coupon->code }}" data-reveal-url="{{ route('coupons.reveal', $coupon->slug) }}">Copy Code</button>
                            <a href="{{ route('coupons.go', $coupon->slug) }}" class="btn btn-outline btn-sm" target="_blank" rel="noopener sponsored">Shop Now</a>
                        </div>
                    @else
                        <div class="blog-coupon-popup-actions">
                            <a href="{{ route('coupons.go', $coupon->slug) }}" class="btn btn-primary btn-sm" target="_blank" rel="noopener sponsored">Get Deal</a>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
        <a href="{{ route('stores.show', $store->slug) }}" class="blog-coupon-popup-store-link">View all {{ $store->name }} offers →</a>
    </div>
</div>
@endif
