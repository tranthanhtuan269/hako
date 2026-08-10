<section class="section pch-blog-section">
    <div class="container">
        <p class="pch-deals-eyebrow">Editorial</p>
        <h2 class="pch-deals-title">Latest from the blog</h2>
        <p class="pch-deals-subtitle">
            Short reads on saving tactics, store notes, and what changed this week.
        </p>

        <div class="pch-blog-grid">
            @foreach($latestPosts as $post)
                @include('themes.prime.blog-card', ['post' => $post])
            @endforeach
        </div>

        <div class="pch-deals-more">
            <a href="{{ route('blog.index') }}" class="pch-deals-more-link">Browse the full archive</a>
        </div>
    </div>
</section>
