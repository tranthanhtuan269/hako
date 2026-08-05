@php
    $brand = $siteName ?? config('site.name');
    $domain = $siteDomain ?? config('site.domain');
@endphp

<section class="gc-about section">
    <div class="container">
        <header class="gc-about-hero">
            <p class="gc-about-eyebrow">Welcome to</p>
            <h1 class="gc-about-brand">{{ strtoupper($brand) }}</h1>
        </header>

        <div class="gc-about-panel">
            <p>
                {{ $domain }} is your trusted source for the latest discount codes and exclusive offers from top brands.
                Our platform curates a diverse selection of verified vouchers, giving you access to exceptional savings
                on a wide range of products and services. Whether you're shopping for fashion, technology, electronics,
                travel, or more, we make it simple to discover the best deals and maximize your budget.
                Experience smarter shopping with {{ $brand }} today!
            </p>
        </div>

        <div class="gc-about-features">
            <article class="gc-about-feature">
                <span class="gc-about-feature-icon" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                </span>
                <h2>Exclusive Offers</h2>
                <p>Access to deals you won't find anywhere else</p>
            </article>

            <article class="gc-about-feature">
                <span class="gc-about-feature-icon" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                </span>
                <h2>Verified Codes</h2>
                <p>All discount codes are tested and verified</p>
            </article>

            <article class="gc-about-feature">
                <span class="gc-about-feature-icon" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M19 5c-1.5 0-2.8 1.4-3 2-1-.6-2.5-1-4-1-3.5 0-6 2-6 5 0 2.5 2 4.5 5 5v3h2v-3c1.2-.2 2.3-.7 3.2-1.4"/><circle cx="16.5" cy="9.5" r="1.2" fill="currentColor" stroke="none"/><path d="M5 11c0-2.5 2-4.5 5-5"/></svg>
                </span>
                <h2>Save More</h2>
                <p>Maximize your savings on every purchase</p>
            </article>
        </div>

        <div class="gc-about-panel gc-about-contact">
            <h2>Contact Us</h2>
            <p>
                Questions or feedback? Email us at
                <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                or visit our <a href="{{ route('pages.contact') }}">Contact</a> page.
            </p>
        </div>
    </div>
</section>
