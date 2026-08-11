<header class="site-header">
    <div class="container header-inner">
        @include('partials.site-brand')

        <form action="{{ route('search') }}" method="GET" class="search-form">
            <input type="search" name="q" placeholder="Search codes, stores, articles..." value="{{ request('q') }}">
            <button type="submit">Search</button>
        </form>

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

