<header class="site-header">
    <div @class(['container header-inner', 'header-inner--no-search' => ! ($headerSearchVisible ?? true)])>
        @include('partials.site-brand')

        @if($headerSearchVisible ?? true)
            <form action="{{ route('search') }}" method="GET" class="search-form">
                <input type="search" name="q" placeholder="Search codes, stores, articles..." value="{{ request('q') }}">
                <button type="submit">Search</button>
            </form>
        @endif

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
            @include('partials.header-nav-links')

            @guest
                @if($showHeaderSignIn)
                    <a href="{{ route('login') }}">Sign In</a>
                @endif
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

