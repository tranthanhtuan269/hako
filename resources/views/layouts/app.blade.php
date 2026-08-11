<!DOCTYPE html>
<html lang="en-US">
<head>
    @include('partials.tracking-head')
    @if(\App\Support\TrackingScripts::conversionActiveForRequest())
        @include('partials.tracking-conversion')
    @endif
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="ZoEPV8YUdyhz-bdAHihGZ8aKmr0N1K3xhWsZOfZY29U">
    @include('partials.seo-head')
    @include('partials.theme-styles')
    @stack('styles')
    @include('partials.site-schema')
    @stack('structured_data')
</head>
<body class="theme-{{ $activeTheme }}">
    @php
        $themeHeaderView = 'themes.' . ($activeTheme ?? 'classic') . '.partials.header';
    @endphp
    @if(view()->exists($themeHeaderView))
        @include($themeHeaderView)
    @else
        @include('partials.site-header')
    @endif

    @if(session('success'))
        <div class="alert alert-success container">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error container">{{ session('error') }}</div>
    @endif

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <strong>{{ $siteHomeH1 }}</strong>
                <p class="footer-tagline">{{ $siteFooterTagline }}</p>
                <p class="footer-sub-tagline">{{ $siteFooterSubTagline }}</p>
                <p class="footer-description-tagline">{{ $siteFooterDescriptionTagline }}</p>
            </div>
            <div>
                <h4>Explore</h4>
                <a href="{{ route('coupons.index') }}">All Coupons</a>
                <a href="{{ route('stores.index') }}">Stores</a>
                <a href="{{ route('categories.index') }}">Categories</a>
                <a href="{{ route('blog.index') }}">Blog</a>
                <a href="{{ route('authors.index') }}">Authors</a>
            </div>
            <div>
                <h4>Company</h4>
                <a href="{{ route('pages.about') }}">About Us</a>
                <a href="{{ route('pages.contact') }}">Contact Us</a>
            </div>
            <div class="footer-legal-col">
                <h4>Legal</h4>
                <a href="{{ route('pages.privacy') }}">Privacy Policy</a>
                <a href="{{ route('pages.terms') }}">Terms of Service</a>
                <a href="{{ route('pages.cookies') }}">Cookie Policy</a>
                <a href="{{ route('pages.disclaimer') }}">Disclaimer</a>
                @if(!empty($siteSocialLinks))
                    <h4 class="footer-social-heading">Follow Us</h4>
                    @include('partials.site-social-links')
                @endif
            </div>
        </div>
        <div class="container footer-copy">
            &copy; {{ date('Y') }} {{ config('site.domain') }}. All rights reserved.
        </div>
    </footer>

    <script>
        window.__couponRedirectFlow = @json(\App\Support\SiteCouponRedirect::flow());
    </script>
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    @include('themes.savingspro.coupon-modal')
    @if($activeTheme === 'savingspro' && file_exists(public_path('js/themes/savingspro.js')))
        <script src="{{ asset('js/themes/savingspro.js') }}?v={{ filemtime(public_path('js/themes/savingspro.js')) }}"></script>
    @endif
    @php
        $themeJs = !empty($activeTheme) ? ('js/themes/' . $activeTheme . '.js') : null;
    @endphp
    @if($themeJs && file_exists(public_path($themeJs)))
        <script src="{{ asset($themeJs) }}?v={{ filemtime(public_path($themeJs)) }}"></script>
    @endif
    @stack('scripts')
</body>
</html>
