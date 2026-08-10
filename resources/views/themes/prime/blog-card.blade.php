<article class="pch-blog-card">
    <a href="{{ route('blog.show', $post->slug) }}" class="pch-blog-card-media" aria-hidden="{{ $post->featuredImageUrl() ? 'false' : 'true' }}">
        @if($post->featuredImageUrl())
            <img src="{{ $post->featuredImageUrl() }}" alt="" loading="lazy">
        @else
            <span class="pch-blog-card-placeholder">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($post->cardTitle(), 0, 1)) }}</span>
        @endif
    </a>
    <div class="pch-blog-card-body">
        <h3 class="pch-blog-card-title">
            <a href="{{ route('blog.show', $post->slug) }}" @if($post->title !== $post->cardTitle()) title="{{ $post->title }}" @endif>
                {{ $post->cardTitle() }}
            </a>
        </h3>
        @if($post->published_at)
            <time class="pch-blog-card-date" datetime="{{ $post->published_at->toDateString() }}">
                {{ $post->published_at->format('j M Y') }}
            </time>
        @endif
        <a href="{{ route('blog.show', $post->slug) }}" class="pch-blog-card-link">Open story</a>
    </div>
</article>
