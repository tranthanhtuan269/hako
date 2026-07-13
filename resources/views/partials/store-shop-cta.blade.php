@if($store->shopUrl())
    <div class="store-shop-cta">
        <a href="{{ $store->shopUrl() }}" class="btn btn-primary store-shop-cta-btn" target="_blank" rel="noopener sponsored">
            Shop at {{ $store->name }}
        </a>
        @if($store->publicWebsiteLabel())
            <p class="store-page-website">
                <a href="{{ $store->shopUrl() }}" target="_blank" rel="noopener sponsored">{{ $store->shopLinkLabel() }}</a>
            </p>
        @endif
    </div>
@endif
