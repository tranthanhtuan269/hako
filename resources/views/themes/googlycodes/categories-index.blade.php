<section class="gc-categories-page section">
    <div class="container">
        <header class="gc-categories-page-head">
            <div class="gc-categories-page-title">
                <span class="gc-categories-page-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                </span>
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
