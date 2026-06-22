@if($store && $storeCoupons->isNotEmpty())
<div class="sidebar-box blog-store-coupons">
    <div class="blog-store-coupons-header">
        @include('partials.store-logo', ['store' => $store, 'size' => 'sm', 'showVerified' => false, 'linked' => false])
        <div>
            <h3>{{ $store->name }} coupons</h3>
            <p>{{ $storeCoupons->count() }} active offer{{ $storeCoupons->count() === 1 ? '' : 's' }}</p>
        </div>
    </div>
    <div class="blog-store-coupons-list">
        @foreach($storeCoupons as $coupon)
            <article class="blog-store-coupon-item">
                <div class="blog-store-coupon-meta">
                    <span class="blog-store-coupon-badge">{{ $coupon->discountLabel() }}</span>
                    <span class="blog-store-coupon-type">{{ $coupon->typeLabel() }}</span>
                </div>
                <h4>{{ $coupon->title }}</h4>
                @if($coupon->code)
                    <div class="blog-store-coupon-actions">
                        <button type="button" class="btn btn-copy btn-sm" data-code="{{ $coupon->code }}" data-reveal-url="{{ route('coupons.reveal', $coupon->slug) }}">Copy Code</button>
                        <a href="{{ route('coupons.go', $coupon->slug) }}" class="btn btn-outline btn-sm" target="_blank" rel="noopener sponsored">Shop Now</a>
                    </div>
                @else
                    <div class="blog-store-coupon-actions">
                        <a href="{{ route('coupons.go', $coupon->slug) }}" class="btn btn-primary btn-sm" target="_blank" rel="noopener sponsored">Get Deal</a>
                    </div>
                @endif
            </article>
        @endforeach
    </div>
    <a href="{{ route('stores.show', $store->slug) }}" class="blog-store-coupons-link">View all {{ $store->name }} offers →</a>
</div>
@endif
