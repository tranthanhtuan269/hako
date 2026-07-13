@php
    $store = $post->resolveStore();
@endphp
@if($store)
    <div class="sidebar-box blog-store-box">
        <div class="blog-store-box-brand">
            @include('partials.store-logo', ['store' => $store, 'size' => 'md', 'showVerified' => false, 'linked' => false])
            <h3>{{ $store->name }}</h3>
        </div>
        <p>Browse verified coupon codes and deals for {{ $store->name }}.</p>
        <a href="{{ route('stores.show', $store->slug) }}" class="btn btn-outline" style="width:100%;text-align:center;margin-bottom:.5rem;">View {{ $store->name }} Coupons</a>
        @include('partials.store-shop-cta', ['store' => $store])
    </div>
@endif
