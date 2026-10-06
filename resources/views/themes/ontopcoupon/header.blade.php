@php
    use App\Support\OntopcouponTheme as Otc;

    $headerNavLinks = $headerNavLinks ?? collect();
    $brandName = $siteDisplayName ?: $siteName;
    $pageLinks = [
        ['label' => 'About Us', 'url' => route('pages.about')],
        ['label' => 'Contact', 'url' => route('pages.contact')],
        ['label' => 'Authors', 'url' => route('authors.index')],
        ['label' => 'Privacy Policy', 'url' => route('pages.privacy')],
        ['label' => 'Terms of Service', 'url' => route('pages.terms')],
    ];
@endphp
<header class="ot-header">
    <div class="ot-container ot-header-inner">
        <a href="{{ route('home') }}" class="ot-brand">
            @if(!empty($siteLogoUrl))
                <img src="{{ $siteLogoUrl }}" alt="{{ $brandName }}" class="ot-brand-logo" width="32" height="32">
            @else
                <span class="ot-brand-mark">{!! Otc::icon('tag', 16, '', 2.4) !!}</span>
            @endif
            <span class="ot-brand-name">{{ $brandName }}</span>
        </a>

        <nav class="ot-nav" aria-label="Main">
            @forelse($headerNavLinks as $navLink)
                <a href="{{ $navLink->resolvedUrl() }}" @class(['ot-nav-link', 'is-current' => $navLink->isCurrent()]) @if($navLink->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $navLink->label }}</a>
            @empty
                <a href="{{ route('coupons.index') }}" @class(['ot-nav-link', 'is-current' => request()->routeIs('coupons.*')])>Coupons</a>
                <a href="{{ route('stores.index') }}" @class(['ot-nav-link', 'is-current' => request()->routeIs('stores.*')])>Stores</a>
                <a href="{{ route('categories.index') }}" @class(['ot-nav-link', 'is-current' => request()->routeIs('categories.*')])>Categories</a>
                <a href="{{ route('blog.index') }}" @class(['ot-nav-link', 'is-current' => request()->routeIs('blog.*')])>Blog</a>
            @endforelse
            <details class="ot-nav-pages" data-ot-dropdown>
                <summary class="ot-nav-link">Pages {!! Otc::icon('chevron-down', 14) !!}</summary>
                <div class="ot-nav-menu">
                    @foreach($pageLinks as $link)
                        <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                    @endforeach
                </div>
            </details>
        </nav>

        <div class="ot-header-actions">
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="ot-header-account">Admin</a>
                @else
                    <a href="{{ route('member.dashboard') }}" class="ot-header-account">Dashboard</a>
                @endif
            @endauth
            <a href="{{ route('coupons.index') }}" class="ot-btn-outline">Get Coupons</a>
        </div>

        <button type="button" class="ot-menu-toggle" data-nav-toggle aria-expanded="false" aria-controls="ot-mobile-nav" aria-label="Open menu">
            {!! Otc::icon('menu', 22) !!}
        </button>
    </div>

    <nav class="ot-mobile-nav" id="ot-mobile-nav" data-main-nav aria-label="Mobile">
        <div class="ot-container">
            @forelse($headerNavLinks as $navLink)
                <a href="{{ $navLink->resolvedUrl() }}" @if($navLink->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $navLink->label }}</a>
            @empty
                <a href="{{ route('coupons.index') }}">Coupons</a>
                <a href="{{ route('stores.index') }}">Stores</a>
                <a href="{{ route('categories.index') }}">Categories</a>
                <a href="{{ route('blog.index') }}">Blog</a>
            @endforelse
            @foreach($pageLinks as $link)
                <a href="{{ $link['url'] }}" class="ot-mobile-nav-sub">{{ $link['label'] }}</a>
            @endforeach
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}">Admin</a>
                @else
                    <a href="{{ route('member.dashboard') }}">Dashboard</a>
                @endif
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit">Log out</button>
                </form>
            @endauth
            <a href="{{ route('coupons.index') }}" class="ot-btn-solid ot-mobile-nav-cta">Get Coupons</a>
        </div>
    </nav>
</header>
