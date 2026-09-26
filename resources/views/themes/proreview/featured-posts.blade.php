@php
    $featured = $latestPosts->slice(0, 6);
@endphp
@if($featured->isNotEmpty())
<section aria-label="Featured posts" class="container-site pb-16 pt-10 sm:pb-[88px] sm:pt-14">
    <div class="grid grid-cols-1 gap-x-8 gap-y-10 sm:grid-cols-2 sm:gap-y-12 lg:grid-cols-3">
        @foreach($featured as $post)
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
                    <span aria-hidden="true" class="h-[3px] w-[3px] rounded-full bg-faint"></span>
                    <span>{{ ceil(str_word_count(strip_tags($post->content ?? '')) / 200) }} min read</span>
                </div>
                <h2 class="line-clamp-3 font-display font-semibold text-xl md:text-[22px] leading-[1.3]" title="{{ $post->title }}">
                    <a class="text-ink hover:text-rust transition-colors" href="{{ route('blog.show', $post->slug) }}">
                        {{ $post->title }}
                    </a>
                </h2>
            </article>
        @endforeach
    </div>
</section>
@endif
