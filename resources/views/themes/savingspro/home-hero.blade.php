<section class="sp-hero hero">
    <div class="container sp-hero-inner">
        <span class="sp-trust-badge">Trusted by {{ number_format($stats['coupons'] + $stats['stores'] * 100) }}+ shoppers daily</span>
        <h1>{{ $siteHomeH1 }}</h1>
        <p class="hero-subtitle">{{ $siteHeroSubtitle }}</p>
        <form action="{{ route('search') }}" method="GET" class="search-form sp-hero-search">
            <input type="search" name="q" placeholder="Search stores, brands, or coupon codes...">
            <button type="submit">Search</button>
        </form>
        <div class="hero-stats sp-hero-stats">
            <div><strong>{{ $stats['coupons'] }}</strong> Active Offers</div>
            <div><strong>{{ $stats['stores'] }}</strong> Stores</div>
            <div><strong>{{ $stats['categories'] }}</strong> Categories</div>
        </div>
    </div>
</section>
