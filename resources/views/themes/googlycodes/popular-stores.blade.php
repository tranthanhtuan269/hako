@php
    $popularStores = ($stores ?? collect())->take(18);
@endphp

@if($popularStores->isNotEmpty())
<section class="gc-popular-stores section">
    <div class="container">
        <div class="gc-popular-stores-head">
            <div class="gc-popular-stores-title">
                <span class="gc-popular-stores-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </span>
                <h2>Popular Stores</h2>
            </div>
            <a href="{{ route('stores.index') }}" class="gc-popular-stores-all">View All <span aria-hidden="true">›</span></a>
        </div>

        <div class="gc-popular-stores-grid">
            @foreach($popularStores as $store)
                @php
                    $couponCount = (int) ($store->active_coupons_count ?? $store->activeCouponsCount());
                @endphp
                <a href="{{ route('stores.show', $store->slug) }}" class="gc-store-tile">
                    <span class="gc-store-tile-logo">
                        @include('partials.store-logo', ['store' => $store, 'size' => 'lg', 'showVerified' => false, 'linked' => false])
                    </span>
                    <strong>{{ $store->name }}</strong>
                    <span class="gc-store-tile-badge">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l1.5 5.5L19 9l-5.5 1.5L12 16l-1.5-5.5L5 9l5.5-1.5L12 2zm7 11l.9 3.1L23 17l-3.1.9L19 21l-.9-3.1L15 17l3.1-.9L19 13zM5 13l.9 3.1L9 17l-3.1.9L5 21l-.9-3.1L1 17l3.1-.9L5 13z"/></svg>
                        {{ $couponCount }} {{ $couponCount === 1 ? 'coupon' : 'coupons' }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
