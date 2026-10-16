<header class="site-header">
    <div class="pch-topbar">
        Verified deals · Updated regularly ·
        <a href="{{ route('pages.disclaimer') }}">How we earn</a>
    </div>

    <div class="container header-inner">
        @include('partials.site-brand')

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

        <nav class="main-nav" id="main-nav" data-main-nav>
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
            @else
                <a href="{{ route('login') }}">Sign In</a>
                <a href="{{ route('register') }}" class="nav-register">Sign Up</a>
            @endauth
        </nav>
    </div>
</header>

