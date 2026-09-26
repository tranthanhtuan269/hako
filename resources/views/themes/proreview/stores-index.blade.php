<header class="container-site">
    <div class="flex flex-col gap-6 border-b border-[#DCD5C7] pb-10 pt-12 sm:pb-12 sm:pt-[72px]">
        <div class="flex flex-col gap-4">
            <h1 class="font-display text-[38px] font-semibold leading-[1.02] tracking-[-0.02em] sm:text-5xl lg:text-[64px]">Browse All Stores</h1>
            <p class="max-w-[860px] text-base leading-[1.65] text-muted sm:text-[17px]">Find verified coupons, promo codes and expert reviews for top brands, sorted alphabetically.</p>
        </div>
    </div>
</header>

<section class="container-site pb-20 pt-10 sm:pb-24 sm:pt-12">
    <div class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($stores as $store)
            <a class="flex items-center gap-4 rounded-[18px] border border-line bg-white p-3.5 text-[15px] font-bold text-ink transition hover:border-ink hover:shadow-card group" href="{{ route('stores.show', $store->slug) }}">
                <div class="h-12 w-12 rounded-xl border border-line bg-cream p-1.5 flex items-center justify-center shrink-0 overflow-hidden">
                    @include('partials.store-logo', ['store' => $store, 'size' => 'sm', 'linked' => false])
                </div>
                <div class="flex flex-col min-w-0 flex-1">
                    <span class="text-base font-bold text-ink group-hover:text-rust transition-colors truncate">{{ $store->name }}</span>
                    <span class="text-xs font-semibold text-muted">{{ $store->coupons_count }} {{ Str::plural('Offer', $store->coupons_count) }}</span>
                </div>
                <svg aria-hidden="true" fill="none" height="18" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" viewBox="0 0 24 24" width="18" class="text-muted group-hover:text-ink group-hover:translate-x-1 transition-all">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </a>
        @endforeach
    </div>
    <div class="pt-10 flex justify-center">{{ $stores->links() }}</div>
</section>
