<div class="border-b border-line">
    <div class="container-site flex items-center gap-4 py-6 sm:gap-7 sm:py-10">
        <div class="flex h-[72px] w-[72px] shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-line bg-white sm:h-[120px] sm:w-[120px] sm:rounded-[28px] p-2">
            @include('partials.store-logo', ['store' => $store, 'size' => 'lg', 'linked' => false])
        </div>
        <div class="flex min-w-0 flex-col gap-2 sm:gap-3.5">
            <h1 class="font-display text-[22px] font-semibold leading-[1.15] tracking-[-0.02em] sm:text-[38px] sm:leading-[1.08] lg:text-[46px] lg:leading-[1.05]">
                {{ $store->name }} Coupons & Promo Codes
            </h1>
            <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-muted sm:text-sm">
                <span class="font-bold text-ink">{{ $coupons->count() }} Verified Offers</span>
                @if($store->website)
                    <span aria-hidden="true" class="text-line">•</span>
                    <a href="{{ $store->website }}" target="_blank" rel="nofollow noopener" class="text-rust font-semibold hover:underline flex items-center gap-1">
                        Visit Official Website
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="container-site flex flex-col gap-10 pb-16 pt-10 sm:pt-12 xl:flex-row xl:items-start xl:pb-[72px]">
    <div class="flex min-w-0 flex-1 flex-col gap-6">
        <section class="flex flex-col gap-5" id="deals">
            <h2 class="text-xl font-extrabold sm:text-[22px]">
                Top {{ $store->name }} Promo Codes for {{ date('F Y') }}
            </h2>
            @if($store->listingDescription())
                <div class="rich-pro -mt-1 mb-2 !text-base !leading-relaxed !text-muted">
                    {{ $store->listingDescription() }}
                </div>
            @endif

            @forelse($coupons as $coupon)
                <article class="grid grid-cols-[96px_minmax(0,1fr)] overflow-hidden rounded-[20px] border border-line bg-white sm:grid-cols-[148px_minmax(0,1fr)] md:grid-cols-[148px_minmax(0,1fr)_220px] transition-all hover:shadow-card" id="offer-{{ $coupon->id }}">
                    <div class="row-span-2 flex flex-col items-center justify-center gap-0.5 border-r-2 border-dashed border-line bg-cream px-2 py-5 text-center md:row-span-1">
                        <span class="break-words font-display text-[26px] font-bold leading-none text-ink sm:text-[36px]">
                            {{ $coupon->discount_amount ?: 'DEAL' }}
                        </span>
                        <span class="text-[11px] font-extrabold uppercase tracking-[0.1em] text-rust-deep sm:text-[13px]">
                            {{ $coupon->code ? 'OFF' : 'ACTIVE' }}
                        </span>
                    </div>
                    <div class="flex min-w-0 flex-col gap-2 px-4 py-4 sm:px-6 sm:py-[22px]">
                        <div class="flex items-center gap-2 text-xs font-semibold text-muted">
                            {{ $store->name }}
                        </div>
                        <h3 class="text-base font-extrabold leading-[1.35] sm:text-[19px]">
                            <a class="hover:text-rust transition-colors" href="{{ $store->affiliate_link ?: $store->website }}" target="_blank" rel="nofollow">
                                {{ $coupon->title }}
                            </a>
                        </h3>
                        @if($coupon->description)
                            <p class="text-sm leading-[1.55] text-muted">{{ $coupon->description }}</p>
                        @endif
                        <div class="mt-0.5 flex flex-wrap items-center gap-x-3.5 gap-y-2">
                            <span class="flex items-center gap-1.5 rounded-md bg-mint px-[9px] py-[3px] text-[11px] font-extrabold tracking-[0.06em] text-mint-ink">
                                {{ $coupon->code ? 'CODE •' : 'DEAL •' }} VERIFIED
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center justify-center px-4 pb-4 sm:px-6 sm:pb-5 md:py-[22px] md:pl-0 md:pr-6">
                        @if($coupon->code)
                            <button type="button" onclick="openCouponCodeModal('{{ $coupon->code }}', '{{ $coupon->title }}', '{{ $store->name }}', '{{ $store->affiliate_link ?: $store->website }}')" class="group relative block h-[52px] w-full md:w-[200px]">
                                <span class="absolute inset-0 flex items-center justify-end rounded-xl border-2 border-dashed border-gold-edge bg-gold-wash pr-3 font-mono text-base font-bold text-ink">
                                    {{ substr($coupon->code, 0, 3) }}***
                                </span>
                                <span class="absolute inset-y-0 left-0 right-10 flex items-center justify-center rounded-l-xl rounded-r bg-gold text-sm font-extrabold text-ink shadow-[4px_0_0_rgba(23,21,15,0.08)] transition-[right] group-hover:right-12">
                                    Show Code
                                </span>
                            </button>
                        @else
                            <a href="{{ $store->affiliate_link ?: $store->website }}" target="_blank" rel="nofollow" class="inline-flex h-[52px] w-full md:w-[200px] items-center justify-center rounded-xl bg-ink text-sm font-extrabold text-white transition-colors hover:bg-navy">
                                Get Deal →
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-line bg-white p-8 text-center text-muted font-medium">
                    No active coupon codes right now. Check back soon!
                </div>
            @endforelse
        </section>

        @if($store->renderedDescription())
            <section class="rounded-2xl border border-line bg-white p-6 sm:p-8">
                <h2 class="mb-4 text-xl font-bold">About {{ $store->name }}</h2>
                <div class="prose-pro max-w-none text-muted leading-relaxed">
                    {!! $store->renderedDescription() !!}
                </div>
            </section>
        @endif
    </div>

    <aside class="w-full xl:w-[320px] flex flex-col gap-6 shrink-0">
        <div class="rounded-2xl border border-line bg-white p-6">
            <h3 class="text-lg font-bold mb-3">Store Info</h3>
            <ul class="flex flex-col gap-3 text-sm">
                <li class="flex justify-between border-b border-line pb-2"><span class="text-muted">Store:</span> <span class="font-bold">{{ $store->name }}</span></li>
                <li class="flex justify-between border-b border-line pb-2"><span class="text-muted">Total Offers:</span> <span class="font-bold">{{ $coupons->count() }}</span></li>
                @if($store->category)
                    <li class="flex justify-between"><span class="text-muted">Category:</span> <span class="font-bold">{{ $store->category->name }}</span></li>
                @endif
            </ul>
        </div>
    </aside>
</div>

{{-- Coupon Code Modal --}}
<div id="proreviewCouponModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-ink/60 p-4" role="dialog">
    <div class="relative w-full max-w-[540px] rounded-3xl bg-white p-6 text-center shadow-float sm:p-8">
        <button type="button" onclick="closeCouponCodeModal()" class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full bg-sand text-ink hover:bg-line text-lg font-bold">&times;</button>
        <h2 id="modalCouponTitle" class="mb-3 font-display text-2xl font-bold sm:text-[26px]">Coupon Code</h2>
        <p class="mb-4 text-sm text-muted">Copy this promo code and apply it during checkout at <strong id="modalStoreName"></strong></p>
        <div class="mx-auto mb-5 flex max-w-[420px] items-center gap-2 rounded-2xl border-2 border-dashed border-gold-edge bg-gold-wash p-2">
            <div id="modalCodeDisplay" class="flex-grow font-mono text-xl font-bold tracking-widest text-ink sm:text-2xl select-all"></div>
            <button type="button" id="btnCopyCoupon" onclick="copyCouponCode()" class="h-11 rounded-xl bg-gold px-6 text-sm font-extrabold uppercase tracking-wide text-ink hover:bg-gold-edge transition-colors">Copy</button>
        </div>
        <a id="modalAffiliateLink" href="#" target="_blank" rel="nofollow" class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-ink px-6 text-sm font-extrabold text-white hover:bg-navy transition-colors">
            Go to Store →
        </a>
    </div>
</div>

<script>
function openCouponCodeModal(code, title, storeName, url) {
    document.getElementById('modalCouponTitle').innerText = title;
    document.getElementById('modalStoreName').innerText = storeName;
    document.getElementById('modalCodeDisplay').innerText = code;
    document.getElementById('modalAffiliateLink').href = url;
    document.getElementById('proreviewCouponModal').classList.remove('hidden');
    document.getElementById('proreviewCouponModal').classList.add('flex');
    window.open(url, '_blank');
}
function closeCouponCodeModal() {
    document.getElementById('proreviewCouponModal').classList.remove('flex');
    document.getElementById('proreviewCouponModal').classList.add('hidden');
}
function copyCouponCode() {
    var code = document.getElementById('modalCodeDisplay').innerText;
    navigator.clipboard.writeText(code).then(function() {
        var btn = document.getElementById('btnCopyCoupon');
        btn.innerText = 'Copied!';
        setTimeout(function() { btn.innerText = 'Copy'; }, 2000);
    });
}
</script>
