<footer class="site-footer pch-footer">
    <div class="container pch-footer-grid">
        <div class="pch-footer-brand">
            <a href="{{ route('home') }}" class="pch-footer-logo">
                {{ $siteDisplayName ?: $siteName }}<span class="pch-footer-dot">.</span>
            </a>
            <p class="pch-footer-tagline">
                Coupons, promotions and trusted store reviews. Updated regularly.
            </p>
        </div>

        <div>
            <h4>Explore</h4>
            <a href="{{ route('home') }}">Home</a>
            <a href="{{ route('blog.index') }}">Review Blog</a>
            <a href="{{ route('stores.index') }}">Stores</a>
            <a href="{{ route('coupons.index') }}">Coupons</a>
        </div>

        <div>
            <h4>Legal</h4>
            <a href="{{ route('pages.about') }}">About Us</a>
            <a href="{{ route('pages.contact') }}">Contact</a>
            <a href="{{ route('pages.privacy') }}">Privacy Policy</a>
            <a href="{{ route('pages.cookies') }}">Cookie Policy</a>
            <a href="{{ route('pages.terms') }}">Terms of Use</a>
        </div>

        <div>
            <h4>Links</h4>
            <a href="{{ route('pages.contact') }}">Feedback</a>
            <a href="{{ route('coupons.index', ['type' => 'discount']) }}">Deals</a>
            <a href="{{ route('pages.disclaimer') }}">Affiliate Disclosure</a>
        </div>
    </div>

    <div class="container pch-footer-bottom">
        <p class="pch-footer-disclaimer">
            We may earn a commission when you use our links, at no extra cost to you.
            See our <a href="{{ route('pages.disclaimer') }}">Affiliate Disclosure</a>
            and <a href="{{ route('pages.privacy') }}">Privacy Policy</a>.
        </p>
        <p class="pch-footer-copy">
            &copy; {{ date('Y') }} {{ $siteDisplayName ?: $siteName }}. All rights reserved.
        </p>
    </div>
</footer>
