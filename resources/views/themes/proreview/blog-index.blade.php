<header class="container-site">
    <div class="flex flex-col items-center gap-4 border-b border-[#DCD5C7] pb-10 pt-12 text-center sm:pb-12 sm:pt-[72px]">
        <h1 class="font-display text-[44px] font-semibold leading-[1.02] tracking-[-0.02em] sm:text-6xl lg:text-7xl">
            {{ $q ? "Search: {$q}" : "All Reviews & Guides" }}
        </h1>
        <p class="max-w-[860px] text-base leading-[1.65] text-muted sm:text-[17px]">
            Independent, hands-on reviews of software, AI tools, and digital products to help you make smarter purchase decisions.
        </p>
    </div>
</header>

<section class="container-site pt-12 sm:pt-14 pb-20 sm:pb-24">
    @if($posts->isEmpty())
        <div class="text-center py-16 text-muted font-medium text-lg">No articles found matching your criteria.</div>
    @else
        <div class="grid grid-cols-1 gap-x-8 gap-y-10 sm:grid-cols-2 sm:gap-y-12 lg:grid-cols-3">
            @foreach($posts as $post)
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
        <div class="pt-12 flex justify-center">{{ $posts->links() }}</div>
    @endif
</section>
