<section class="gc-blog-page section">
    <div class="container">
        <header class="gc-blog-page-head">
            <div class="gc-blog-page-title">
                <span class="gc-blog-page-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                </span>
                <h1>Blogs</h1>
            </div>
            <p class="gc-blog-page-desc">
                Coupon strategies, deal guides, and shopping tips to help you save more online.
            </p>
            <p class="gc-blog-page-authors">
                <a href="{{ route('authors.index') }}">Meet our authors →</a>
            </p>
        </header>

        <form action="{{ route('blog.index') }}" method="GET" class="gc-blog-search">
            <input type="search" name="q" placeholder="Search articles..." value="{{ $q }}">
            <button type="submit">Search</button>
        </form>

        @if($posts->isEmpty())
            <p class="gc-blog-empty">No articles found.@if($q) Try a different keyword.@endif</p>
        @else
            <div class="blog-grid gc-blog-grid">
                @foreach($posts as $post)
                    @include('blog.partials.card', ['post' => $post])
                @endforeach
            </div>
            @if($posts->hasPages())
                <div class="gc-blog-pagination pagination">{{ $posts->links() }}</div>
            @endif
        @endif
    </div>
</section>
