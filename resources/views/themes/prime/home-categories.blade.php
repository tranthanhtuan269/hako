<section class="section pch-categories-section">
    <div class="container">
        <div class="pch-categories-intro">
            <p class="pch-deals-eyebrow">Topics</p>
            <h2 class="pch-deals-title">Browse by category</h2>
            <p class="pch-deals-subtitle">
                Jump into the verticals we cover most — each link filters the featured strip.
            </p>
        </div>

        <div class="pch-category-pills">
            @foreach($categories as $category)
                <a href="{{ route('categories.show', $category->slug) }}" class="pch-category-pill">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    </div>
</section>
