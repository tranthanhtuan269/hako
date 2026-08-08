@php
    /** @var \App\Models\Store $store */
    /** @var \Illuminate\Support\Collection|\App\Models\Coupon[] $coupons */
    $coupons = $coupons ?? collect();
@endphp
<section class="embedded-coupon-list" data-store-coupons="{{ $store->slug }}">
    @if($coupons->isEmpty())
        <p class="embedded-coupon-list__empty">No active coupons for {{ $store->name }} right now. Check back soon.</p>
    @else
        <div class="embedded-coupon-list__grid">
            @foreach($coupons as $coupon)
                @include('partials.coupon-card', [
                    'coupon' => $coupon,
                    'showDescription' => false,
                    'openAffiliateOnCopy' => true,
                ])
            @endforeach
        </div>
        @if($showMoreLink ?? true)
            <p class="embedded-coupon-list__more">
                <a href="{{ route('stores.show', $store->slug) }}">View all {{ $store->name }} offers →</a>
            </p>
        @endif
    @endif
</section>
<style>
.embedded-coupon-list {
    margin: 1.5rem 0 2rem;
    max-width: 100%;
    min-width: 0;
}
.embedded-coupon-list__grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: minmax(0, 1fr);
}
@media (min-width: 720px) {
    .embedded-coupon-list__grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
.embedded-coupon-list__more { margin: 1rem 0 0; }
.embedded-coupon-list__empty { margin: 0; color: #64748b; }
body.theme-googlycodes .embedded-coupon-list__empty { color: #1d3557; }
body.theme-googlycodes .embedded-coupon-list__more a { color: #e63946; font-weight: 600; }
</style>
