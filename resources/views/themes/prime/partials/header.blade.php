<header class="site-header">
    <div class="pch-topbar">
        Verified deals · Updated regularly ·
        <a href="{{ route('pages.disclaimer') }}">How we earn</a>
    </div>

    <div class="container header-inner">
        @include('partials.site-brand')

        @php
            $showHeaderSignIn = $headerSignInVisible ?? true;
            $headerHasMenu = ($headerNavConfigured ?? false)
                ? ($headerNavLinks ?? collect())->isNotEmpty()
                : true;
            $showHeaderNavToggle = auth()->check() || $headerHasMenu || $showHeaderSignIn;
        @endphp

        @if($showHeaderNavToggle)
            <button
                type="button"
                class="nav-toggle"
                data-nav-toggle
                aria-expanded="false"
                aria-controls="main-nav"
                aria-label="Open menu"
            >
                <span class="nav-toggle-bars" aria-hidden="true"></span>
            </button>
        @endif

        <nav class="main-nav" id="main-nav" data-main-nav>
            @if($headerNavConfigured ?? false)
                @include('partials.header-nav-links')
            @else
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('blog.index') }}">Blog</a>
                <a href="{{ route('pages.about') }}">About Us</a>
                <a href="{{ route('pages.contact') }}">Contact</a>
            @endif

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
            @elseif($showHeaderSignIn)
                <a href="{{ route('login') }}">Sign In</a>
            @endauth
        </nav>
    </div>
</header>

