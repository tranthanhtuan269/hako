@php
    $heroPosts = $latestPosts->take(4);
@endphp
@if($heroPosts->isNotEmpty())
<section aria-label="Featured slider" class="container-site pt-6 sm:pt-10">
    <div class="relative" data-slider="">
        <div class="no-scrollbar -mb-4 flex snap-x snap-mandatory gap-4 overflow-x-auto pb-8 md:gap-6" data-track="">
            @foreach($heroPosts as $idx => $p)
                <a aria-label="{{ $idx + 1 }} of {{ $heroPosts->count() }}" aria-roledescription="slide" class="group relative block h-[260px] shrink-0 basis-full snap-start overflow-hidden rounded-3xl bg-sand shadow-card sm:h-[320px] md:basis-[calc(50%-12px)] lg:h-[392px]" href="{{ route('blog.show', $p->slug) }}" role="group">
                    <img alt="{{ $p->title }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" decoding="async" src="{{ $p->featuredImageUrl() }}" loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-ink/80 via-ink/20 to-transparent p-6 flex flex-col justify-end text-white sm:p-8">
                        <div class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-accent">
                            <span>Featured Review</span>
                            <span>•</span>
                            <time>{{ $p->published_at?->format('M d, Y') ?? $p->created_at->format('M d, Y') }}</time>
                        </div>
                        <h2 class="font-display text-xl font-bold leading-snug sm:text-2xl lg:text-3xl line-clamp-2 text-white">{{ $p->title }}</h2>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="flex items-center justify-center gap-2 pt-2" data-dots=""></div>
    </div>
</section>
@endif
