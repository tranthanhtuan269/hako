@php
    $popularCategories = ($categories ?? collect())->take(12);
@endphp

@if($popularCategories->isNotEmpty())
<section class="gc-categories section">
    <div class="container">
        <div class="gc-categories-head">
            <div class="gc-categories-title">
                <span class="gc-categories-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="3" y="3" width="5" height="5" rx="1"/>
                        <rect x="10" y="3" width="5" height="5" rx="1"/>
                        <rect x="17" y="3" width="5" height="5" rx="1"/>
                        <rect x="3" y="10" width="5" height="5" rx="1"/>
                        <rect x="10" y="10" width="5" height="5" rx="1"/>
                        <rect x="17" y="10" width="5" height="5" rx="1"/>
                        <rect x="3" y="17" width="5" height="5" rx="1"/>
                        <rect x="10" y="17" width="5" height="5" rx="1"/>
                        <rect x="17" y="17" width="5" height="5" rx="1"/>
                    </svg>
                </span>
                <h2>Popular Categories</h2>
            </div>
            <a href="{{ route('categories.index') }}" class="gc-categories-all">View All <span aria-hidden="true">›</span></a>
        </div>

        <div class="gc-categories-grid">
            @foreach($popularCategories as $category)
                <a href="{{ route('categories.show', $category->slug) }}" class="gc-cat-card">
                    <span class="gc-cat-ring">
                        @include('partials.category-icon', ['category' => $category, 'size' => 'lg', 'fallback' => '🏷️'])
                    </span>
                    <strong>{{ $category->name }}</strong>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
