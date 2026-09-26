@php
    $picks = $stores->take(5);
@endphp
@if($picks->isNotEmpty())
<section class="bg-sand py-14 sm:py-[72px]">
    <div class="container-site flex flex-col gap-8 sm:gap-10">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-display text-[32px] font-semibold leading-[1.05] tracking-[-0.02em] text-ink sm:text-[40px] lg:text-5xl">
                Editor’s Picks<span class="text-accent-dot">.</span>
            </h2>
            <a class="inline-flex h-11 shrink-0 items-center gap-2 rounded-full border-[1.5px] border-ink px-4 text-sm font-bold text-ink transition-colors hover:bg-ink hover:text-white sm:px-5 sm:text-[15px]" href="{{ route('stores.index') }}">
                View all
                <svg aria-hidden="true" fill="none" height="18" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" viewBox="0 0 24 24" width="18">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </a>
        </div>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 sm:gap-6">
            @foreach($picks as $store)
                <article class="flex flex-col items-center gap-3.5 text-center">
                    <a aria-hidden="true" class="block aspect-square w-full overflow-hidden rounded-2xl bg-white border border-line p-4 transition-transform duration-300 hover:-translate-y-1 shadow-sm flex items-center justify-center" href="{{ route('stores.show', $store->slug) }}" tabindex="-1">
                        @include('partials.store-logo', ['store' => $store, 'size' => 'lg', 'linked' => false])
                    </a>
                    <h3 class="line-clamp-2 px-1.5 text-[12px] font-bold uppercase leading-relaxed tracking-[0.1em] text-[#4A453C] sm:text-xs" title="{{ $store->name }}">
                        <a class="hover:text-rust transition-colors" href="{{ route('stores.show', $store->slug) }}">
                            {{ $store->name }} ({{ $store->coupons_count }} Deals)
                        </a>
                    </h3>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
