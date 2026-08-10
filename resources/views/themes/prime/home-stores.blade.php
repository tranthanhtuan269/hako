<section class="section pch-stores-section">
    <div class="container">
        <p class="pch-deals-eyebrow">Stores in focus</p>
        <h2 class="pch-deals-title">Featured destinations</h2>
        <p class="pch-deals-subtitle">
            Tap a logo to jump straight into coupons and campaign details for that brand.
        </p>

        @if($stores->isNotEmpty())
            <div class="pch-store-tray" aria-label="Featured stores">
                <div class="pch-store-tray-track">
                    @foreach($stores as $store)
                        <a href="{{ route('stores.show', $store->slug) }}" class="pch-store-item">
                            <span class="pch-store-logo">
                                @include('partials.store-logo', [
                                    'store' => $store,
                                    'size' => 'lg',
                                    'showVerified' => false,
                                    'linked' => false,
                                ])
                            </span>
                            <span class="pch-store-name">{{ $store->name }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            <p class="pch-deals-empty">No featured stores yet.</p>
        @endif
    </div>
</section>
