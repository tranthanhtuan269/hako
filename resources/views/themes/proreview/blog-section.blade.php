@php
    $blogPosts = $latestPosts->slice(4, 6);
@endphp
@if($blogPosts->isNotEmpty())
<section class="container-site flex flex-col gap-8 pb-16 sm:gap-10 sm:pb-[88px]">
    <div class="flex items-center justify-between gap-4">
        <h2 class="font-display text-[32px] font-semibold leading-[1.05] tracking-[-0.02em] text-ink sm:text-[40px] lg:text-5xl">
            Blog<span class="text-accent-dot">.</span>
        </h2>
        <a class="inline-flex h-11 shrink-0 items-center gap-2 rounded-full border-[1.5px] border-ink px-4 text-sm font-bold text-ink transition-colors hover:bg-ink hover:text-white sm:px-5 sm:text-[15px]" href="{{ route('blog.index') }}">
            View all
            <svg aria-hidden="true" fill="none" height="18" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" viewBox="0 0 24 24" width="18">
                <path d="m9 18 6-6-6-6"></path>
            </svg>
        </a>
    </div>
    <div class="grid grid-cols-1 gap-x-8 gap-y-10 sm:grid-cols-2 sm:gap-y-12 lg:grid-cols-3">
        @foreach($blogPosts as $post)
            <article class="flex flex-col items-center gap-3.5 text-center">
                <a aria-hidden="true" class="block aspect-[4/3] w-full overflow-hidden rounded-[18px] bg-sand transition-transform duration-300 hover:-translate-y-1 shadow-sm" href="{{ route('blog.show', $post->slug) }}" tabindex="-1">
                    <img alt="{{ $post->title }}" class="h-full w-full object-cover" decoding="async" loading="lazy" src="{{ $post->featuredImageUrl() }}">
                </a>
                <div class="flex flex-wrap items-center justify-center gap-2 text-[13px] leading-snug text-muted">
                    <span class="font-semibold text-ink">{{ config('site.name', 'ProReview') }}</span>
                    <span aria-hidden="true" class="h-[3px] w-[3px] rounded-full bg-faint"></span>
                    <time datetime="{{ $post->published_at?->toDateString() ?? $post->created_at->toDateString() }}">
                        {{ $post->published_at?->format('M d, Y') ?? $post->created_at->format('M d, Y') }}
                    </time>
                </div>
                <h3 class="line-clamp-3 font-display font-semibold text-lg md:text-xl leading-[1.35]" title="{{ $post->title }}">
                    <a class="text-ink hover:text-rust transition-colors" href="{{ route('blog.show', $post->slug) }}">
                        {{ $post->title }}
                    </a>
                </h3>
            </article>
        @endforeach
    </div>
</section>
@endif
