@php
    $slides = $heroSlides ?? collect();
@endphp
@if($slides->isNotEmpty())
<section aria-label="Featured slider" class="container-site pt-6 sm:pt-10">
    <div class="relative" data-slider="">
        <div class="no-scrollbar -mb-4 flex snap-x snap-mandatory gap-4 overflow-x-auto pb-8 md:gap-6" data-track="">
            @foreach($slides as $idx => $slide)
                @php
                    $slideImg = !empty($slide['image']) ? $slide['image'] : asset('frontend/images/banner-placeholder.png');
                    $slideUrl = !empty($slide['cta_url']) ? $slide['cta_url'] : '#';
                @endphp
                <a aria-label="{{ $idx + 1 }} of {{ $slides->count() }}" aria-roledescription="slide" class="group relative block h-[260px] shrink-0 basis-full snap-start overflow-hidden rounded-3xl bg-sand shadow-card sm:h-[320px] md:basis-[calc(50%-12px)] lg:h-[392px]" href="{{ $slideUrl }}" role="group">
                    <img alt="{{ $slide['headline'] ?? '' }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" decoding="async" src="{{ $slideImg }}" loading="{{ $idx === 0 ? 'eager' : 'lazy' }}">
                    @if(!empty($slide['headline']) || !empty($slide['subtitle']))
                        <div class="absolute inset-0 bg-gradient-to-t from-ink/80 via-ink/20 to-transparent p-6 flex flex-col justify-end text-white sm:p-8">
                            @if(!empty($slide['subtitle']))
                                <div class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-accent">
                                    <span>{{ $slide['subtitle'] }}</span>
                                </div>
                            @endif
                            @if(!empty($slide['headline']))
                                <h2 class="font-display text-xl font-bold leading-snug sm:text-2xl lg:text-3xl line-clamp-2 text-white">{{ $slide['headline'] }}</h2>
                            @endif
                        </div>
                    @endif
                </a>
            @endforeach
        </div>
        <button aria-label="Previous slide" class="absolute left-0 top-[calc(50%-18px)] z-10 hidden h-11 w-11 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-white text-ink shadow-float transition hover:scale-105 sm:h-12 sm:w-12 md:flex" data-prev="" type="button">
            <svg aria-hidden="true" fill="none" height="18" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" viewBox="0 0 24 24" width="18">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
        </button>
        <button aria-label="Next slide" class="absolute right-0 top-[calc(50%-18px)] z-10 hidden h-11 w-11 -translate-y-1/2 translate-x-1/2 items-center justify-center rounded-full bg-white text-ink shadow-float transition hover:scale-105 sm:h-12 sm:w-12 md:flex" data-next="" type="button">
            <svg aria-hidden="true" fill="none" height="18" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" viewBox="0 0 24 24" width="18">
                <path d="m9 18 6-6-6-6"></path>
            </svg>
        </button>
        <div class="mt-1 flex items-center justify-center gap-2" data-dots=""></div>
    </div>
</section>
@endif
