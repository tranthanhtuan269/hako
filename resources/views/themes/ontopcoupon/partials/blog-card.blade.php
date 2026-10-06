@php
    use App\Support\OntopcouponTheme as Otc;

    $glow = Otc::blogGlow($index ?? $loop->index ?? 0);
    $image = $post->featuredImageUrl();
    $excerpt = $post->excerpt ?: \Illuminate\Support\Str::limit(trim(strip_tags($post->content)), 150);
@endphp
<a href="{{ route('blog.show', $post->slug) }}" class="ot-blog-card">
    <article>
        <div class="ot-blog-card-media" style="--ot-glow: {{ $glow }}">
            @if($image)
                <img src="{{ $image }}" alt="{{ $post->title }}" loading="lazy">
            @else
                <div class="ot-blog-card-art">
                    <span class="ot-blog-card-watermark">{!! Otc::icon('book-open', 64, '', 1.5) !!}</span>
                    <span class="ot-blog-card-icon">{!! Otc::icon('book-open', 20) !!}</span>
                    <span class="ot-blog-card-art-title">{{ $post->cardTitle(80) }}</span>
                </div>
            @endif
            <span class="ot-blog-card-pill">{{ Otc::postCategory($post) }}</span>
        </div>
        <div class="ot-blog-card-body">
            <h3>{{ $post->cardTitle() }}</h3>
            <p>{{ $excerpt }}</p>
            <div class="ot-blog-card-foot">
                <span>{!! Otc::icon('calendar', 13) !!} {{ $post->published_at?->format('M j, Y') }}</span>
                <span class="ot-blog-card-more">Read more {!! Otc::icon('arrow-right', 14) !!}</span>
            </div>
        </div>
    </article>
</a>
