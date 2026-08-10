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
    @if($activeTheme === 'prime')
        <div class="pch-topbar">
            Verified deals · Updated regularly ·
            <a href="{{ route('pages.disclaimer') }}">How we earn</a>
        </div>
    @endif
    <header class="site-header">
        <div class="container header-inner">
            @include('partials.site-brand')
            @if($activeTheme !== 'prime')
                <form action="{{ route('search') }}" method="GET" class="search-form">
                    <input type="search" name="q" placeholder="Search codes, stores, articles..." value="{{ request('q') }}">
                    <button type="submit">Search</button>
                </form>
            @endif
            <button type="button" class="nav-toggle" data-nav-toggle aria-expanded="false" aria-controls="main-nav" aria-label="Open menu">
                <span class="nav-toggle-bars" aria-hidden="true"></span>
            </button>
            <nav class="main-nav" id="main-nav" data-main-nav>
                @if($activeTheme === 'prime')
                    <a href="{{ route('home') }}">Home</a>
                    <a href="{{ route('blog.index') }}">Blog</a>
                    <a href="{{ route('pages.about') }}">About Us</a>
                    <a href="{{ route('pages.contact') }}">Contact</a>
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="nav-admin">Admin</a>
                            <a href="{{ route('member.import-affiliate.create') }}" class="nav-register">Import</a>
                        @else
                            <a href="{{ route('member.dashboard') }}" class="nav-register">Dashboard</a>
                        @endif
                        <form action="{{ route('logout') }}" method="POST" class="nav-logout-form">
                            @csrf
                            <button type="submit" class="nav-logout-btn">Log out</button>
                        </form>
                    @endauth
                @else
                    <a href="{{ route('coupons.index') }}">Coupons</a>
                    <a href="{{ route('coupons.index', ['type' => 'discount']) }}">Deals</a>
                    <a href="{{ route('stores.index') }}">Stores</a>
                    <a href="{{ route('categories.index') }}">Categories</a>
                    <a href="{{ route('blog.index') }}">Blog</a>
                    @guest
                        <a href="{{ route('login') }}">Sign In</a>
                        <a href="{{ route('register') }}" class="nav-register">Sign Up</a>
                    @else
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="nav-admin">Admin</a>
                            <a href="{{ route('member.import-affiliate.create') }}" class="nav-register">Import</a>
                        @else
                            <a href="{{ route('member.dashboard') }}" class="nav-register">Dashboard</a>
                        @endif
                        <form action="{{ route('logout') }}" method="POST" class="nav-logout-form">
                            @csrf
                            <button type="submit" class="nav-logout-btn">Log out</button>
                        </form>
                    @endguest
                @endif
            </nav>
        </div>
    </header>

    @if(session('success'))
        <div class="alert alert-success container">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error container">{{ session('error') }}</div>
    @endif

    <main>
        @yield('content')
    </main>

    @if($activeTheme === 'prime')
        @include('themes.prime.footer')
    @else
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
    @endif

    <script>
        window.__couponRedirectFlow = @json(\App\Support\SiteCouponRedirect::flow());
    </script>
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    @include('themes.savingspro.coupon-modal')
    @php
        $themeJs = \App\Support\ThemeManager::jsPath();
    @endphp
    @if($themeJs && file_exists(public_path($themeJs)))
        <script src="{{ asset($themeJs) }}?v={{ filemtime(public_path($themeJs)) }}"></script>
    @endif
    @stack('scripts')
</body>
</html>
