<section class="gc-stores-page section">
    <div class="container">
        <header class="gc-stores-page-head">
            <div class="gc-stores-page-title">
                <span class="gc-stores-page-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </span>
                <h1>Stores</h1>
            </div>
            <p class="gc-stores-page-desc">
                Browse our extensive collection of stores offering exclusive discounts and coupon codes.
                Find the best deals from your favorite brands all in one place.
            </p>
        </header>

        <div class="gc-stores-page-grid">
            @forelse($stores as $store)
                @php
                    $couponCount = (int) ($store->coupons_count ?? 0);
                @endphp
                <a href="{{ route('stores.show', $store->slug) }}" class="gc-stores-page-card">
                    <span class="gc-stores-page-logo">
                        @include('partials.store-logo', ['store' => $store, 'size' => 'xl', 'showVerified' => false, 'linked' => false])
                    </span>
                    <strong>{{ $store->name }}</strong>
                    <span class="gc-stores-page-badge">
                        {{ $couponCount }} {{ $couponCount === 1 ? 'coupon' : 'coupons' }}
                    </span>
                </a>
            @empty
                <p class="gc-stores-page-empty">No stores available yet.</p>
            @endforelse
        </div>

        @if($stores->hasPages())
            <div class="gc-stores-page-pagination pagination">
                {{ $stores->links() }}
            </div>
        @endif
    </div>
</section>
