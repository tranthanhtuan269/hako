<article class="blog-card">
    @if($post->featuredImageUrl())
        <a href="{{ route('blog.show', $post->slug) }}" class="blog-card-image">
            <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" loading="lazy">
        </a>
    @endif
    <div class="blog-card-body">
        <time datetime="{{ $post->published_at?->toDateString() }}">
            {{ $post->published_at?->format('F j, Y') }}
        </time>
        <p class="blog-card-author">@include('blog.partials.author-link', ['post' => $post])</p>
        <h2><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h2>
        <p>{{ $post->excerpt ?: Str::limit(strip_tags($post->content), 160) }}</p>
        <div class="blog-card-meta">
            <span>{{ $post->readingTime() }} min read</span>
            <a href="{{ route('blog.show', $post->slug) }}" class="read-more">Read article →</a>
        </div>
    </div>
</article>
