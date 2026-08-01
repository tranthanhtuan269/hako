<section class="gc-categories-page section">
    <div class="container">
        <header class="gc-categories-page-head">
            <div class="gc-categories-page-title">
                <span class="gc-categories-page-icon" aria-hidden="true">#</span>
                <h1>Categories</h1>
            </div>
            <p class="gc-categories-page-desc">
                Find your favorite deals by categories. Explore our curated selection of top deals across various shopping categories.
            </p>
        </header>

        <div class="gc-categories-page-grid">
            @forelse($categories as $category)
                <a href="{{ route('categories.show', $category->slug) }}" class="gc-categories-page-card">
                    <span class="gc-categories-page-ring">
                        @include('partials.category-icon', ['category' => $category, 'size' => 'lg', 'fallback' => '🏷️'])
                    </span>
                    <strong>{{ $category->name }}</strong>
                </a>
            @empty
                <p class="gc-categories-page-empty">No categories available yet.</p>
            @endforelse
        </div>
    </div>
</section>
