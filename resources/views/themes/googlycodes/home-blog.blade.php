@php
    $homePosts = ($latestPosts ?? collect())->take(3);
@endphp

@if($homePosts->isNotEmpty())
<section class="gc-home-blog">
    <div class="container">
        <div class="gc-trending-head">
            <div class="gc-trending-title">
                <span class="gc-trending-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                </span>
                <h2>Blogs</h2>
            </div>
            <a href="{{ route('blog.index') }}" class="gc-trending-all">View All <span aria-hidden="true">→</span></a>
        </div>

        <div class="blog-grid gc-home-blog-grid">
            @foreach($homePosts as $post)
                @include('blog.partials.card', ['post' => $post])
            @endforeach
        </div>
    </div>
</section>
@endif
