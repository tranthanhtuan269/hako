@php
    $featureSlides = [
        [
            'eyebrow' => 'Why shoppers stay',
            'title' => 'Curated',
            'body' => 'Human-reviewed paths to real savings — fewer dead codes, clearer next steps.',
        ],
        [
            'eyebrow' => 'Why shoppers stay',
            'title' => 'Verified',
            'body' => 'Fresh checks on active offers so you spend less time hunting and more time saving.',
        ],
        [
            'eyebrow' => 'Why shoppers stay',
            'title' => 'Editorial',
            'body' => 'Honest store picks and guides written for shoppers — not just another coupon dump.',
        ],
    ];
@endphp

<section class="pch-hero">
    <div class="container pch-hero-grid">
        <div class="pch-hero-copy">
            <span class="pch-trust-badge">Deals you can trust</span>
            <h1>{{ $siteHomeH1 === ($siteName ?? '') ? 'Save smarter with curated coupons & honest store picks' : $siteHomeH1 }}</h1>
            <p class="pch-hero-subtitle">
                {{ str_contains($siteHeroSubtitle, 'not affiliated')
                    ? 'Search verified promotions, explore top stores, and read updates from our blog — refreshed often so you never miss a strong offer.'
                    : $siteHeroSubtitle }}
            </p>
            <p class="pch-disclosure">
                We may earn a commission when you use our links.
                <a href="{{ route('pages.disclaimer') }}">Read our disclosure</a>.
            </p>
            <form action="{{ route('search') }}" method="GET" class="search-form pch-hero-search">
                <input type="search" name="q" placeholder="Search brands, stores, or offers..." value="{{ request('q') }}" aria-label="Search brands, stores, or offers">
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="pch-feature" data-pch-feature data-autoplay="5000" aria-roledescription="carousel" aria-label="Why shoppers stay">
            <div class="pch-feature-slides">
                @foreach($featureSlides as $index => $slide)
                    <article class="pch-feature-slide{{ $index === 0 ? ' is-active' : '' }}" data-pch-slide @if($index !== 0) hidden @endif>
                        <p class="pch-feature-eyebrow">{{ $slide['eyebrow'] }}</p>
                        <h2 class="pch-feature-title">{{ $slide['title'] }}</h2>
                        <p class="pch-feature-body">{{ $slide['body'] }}</p>
                    </article>
                @endforeach
            </div>
            <div class="pch-feature-dots" role="tablist" aria-label="Feature slides">
                @foreach($featureSlides as $index => $slide)
                    <button
                        type="button"
                        class="pch-feature-dot{{ $index === 0 ? ' is-active' : '' }}"
                        data-pch-dot
                        data-slide-index="{{ $index }}"
                        role="tab"
                        aria-label="Slide {{ $index + 1 }}: {{ $slide['title'] }}"
                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                    ></button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="container pch-stats">
        <div class="pch-stat-card">
            <strong>{{ number_format($stats['stores']) }}+</strong>
            <span>Verified brands</span>
        </div>
        <div class="pch-stat-card">
            <strong>{{ number_format($stats['coupons']) }}+</strong>
            <span>Active coupons</span>
        </div>
        <div class="pch-stat-card">
            <strong>Editorial</strong>
            <span>Guides &amp; picks</span>
        </div>
        <div class="pch-stat-card">
            <strong>Daily</strong>
            <span>Fresh checks</span>
        </div>
    </div>
</section>
