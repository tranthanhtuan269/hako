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
    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('home') }}" class="site-brand">
                <span class="site-brand-icon">%</span>
                <span class="site-brand-text">
                    <strong>{{ config('site.name') }}</strong>
                    <small>{{ config('site.tagline') }}</small>
                </span>
            </a>
            <form action="{{ route('search') }}" method="GET" class="search-form">
                <input type="search" name="q" placeholder="Search codes, stores..." value="{{ request('q') }}">
                <button type="submit">Search</button>
            </form>
            <nav class="main-nav">
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

    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <strong>{{ config('site.name') }}</strong>
                <p class="footer-tagline">{{ config('site.tagline') }}</p>
                <p>Coupon codes and discount deals for U.S. shoppers. Updated daily at {{ config('site.domain') }}.</p>
                @include('partials.site-affiliate-notice')
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
            <div>
                <h4>Legal</h4>
                <a href="{{ route('pages.privacy') }}">Privacy Policy</a>
                <a href="{{ route('pages.terms') }}">Terms of Service</a>
                <a href="{{ route('pages.cookies') }}">Cookie Policy</a>
                <a href="{{ route('pages.disclaimer') }}">Disclaimer</a>
            </div>
        </div>
        <div class="container footer-copy">
            &copy; {{ date('Y') }} {{ config('site.domain') }}. All rights reserved.
        </div>
    </footer>

    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    @if($activeTheme === 'savingspro')
        @include('themes.savingspro.coupon-modal')
        @if(file_exists(public_path('js/themes/savingspro.js')))
            <script src="{{ asset('js/themes/savingspro.js') }}?v={{ filemtime(public_path('js/themes/savingspro.js')) }}"></script>
        @endif
    @endif
    @stack('scripts')
</body>
</html>
