<article class="container-site pb-20 pt-10 sm:pb-[104px] sm:pt-16">
    <div class="mx-auto max-w-[820px]">
        <div class="mb-4 flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-muted">
            <a href="{{ route('home') }}" class="hover:text-ink">Home</a>
            <span>/</span>
            <a href="{{ route('blog.index') }}" class="hover:text-ink">Review</a>
            @if($post->resolveStore())
                <span>/</span>
                <a href="{{ route('stores.show', $post->resolveStore()->slug) }}" class="text-rust hover:underline">{{ $post->resolveStore()->name }}</a>
            @endif
        </div>

        <h1 class="mb-6 font-display text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] sm:mb-8 sm:text-4xl lg:text-[50px] lg:leading-[1.1] text-ink">
            {{ $post->title }}
        </h1>

        <div class="mb-8 flex flex-wrap items-center gap-3 border-b border-line pb-6 text-sm text-muted">
            <span class="font-bold text-ink">{{ $post->authorProfile()->name }}</span>
            <span>•</span>
            <time datetime="{{ $post->published_at?->toDateString() ?? $post->created_at->toDateString() }}">
                {{ $post->published_at?->format('M d, Y') ?? $post->created_at->format('M d, Y') }}
            </time>
            <span>•</span>
            <span>{{ ceil(str_word_count(strip_tags($post->content ?? '')) / 200) }} min read</span>
            @if($post->resolveStore() && $post->resolveStore()->affiliate_link)
                <span class="ml-auto">
                    <a href="{{ $post->resolveStore()->affiliate_link }}" target="_blank" rel="nofollow noopener" class="inline-flex items-center gap-1.5 rounded-full bg-accent px-4 py-1.5 text-xs font-bold text-ink hover:opacity-90">
                        Visit {{ $post->resolveStore()->name }} →
                    </a>
                </span>
            @endif
        </div>

        @if($post->featuredImageUrl())
            <div class="mb-10 overflow-hidden rounded-3xl bg-sand shadow-sm">
                <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" class="h-auto w-full object-cover">
            </div>
        @endif

        <div class="prose-pro max-w-none text-ink leading-relaxed">
            {!! $post->renderedContent() !!}
        </div>

        @if($related->isNotEmpty())
            <div class="mt-16 border-t border-line pt-12">
                <h3 class="mb-8 font-display text-2xl font-bold sm:text-3xl">Related Reviews</h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    @foreach($related->take(4) as $item)
                        <article class="flex flex-col gap-3 rounded-2xl border border-line bg-white p-4 shadow-sm">
                            <a href="{{ route('blog.show', $item->slug) }}" class="aspect-[16/9] w-full overflow-hidden rounded-xl bg-sand">
                                <img src="{{ $item->featuredImageUrl() }}" alt="{{ $item->title }}" class="h-full w-full object-cover">
                            </a>
                            <h4 class="font-display font-bold text-base line-clamp-2">
                                <a href="{{ route('blog.show', $item->slug) }}" class="text-ink hover:text-rust">{{ $item->title }}</a>
                            </h4>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</article>
