@php
    $panel = $panel ?? 'member';
    $isAdmin = auth()->user()->isAdmin();
@endphp
<nav class="sidebar-nav" aria-label="Dashboard menu">
    <div class="sidebar-nav-group">
        @if($panel === 'admin')
            <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>Dashboard</a>
            <a href="{{ route('admin.categories.index') }}" @class(['active' => request()->routeIs('admin.categories.*')])>Categories</a>
            <a href="{{ route('admin.stores.index') }}" @class(['active' => request()->routeIs('admin.stores.*')])>Stores</a>
            <a href="{{ route('admin.coupons.index') }}" @class(['active' => request()->routeIs('admin.coupons.*')])>Coupons</a>
            <a href="{{ route('admin.posts.index') }}" @class(['active' => request()->routeIs('admin.posts.*')])>Blogs</a>
            <a href="{{ route('admin.themes.index') }}" @class(['active' => request()->routeIs('admin.themes.*')])>Frontend Theme</a>
            <a href="{{ route('admin.tracking.index') }}" @class(['active' => request()->routeIs('admin.tracking.*')])>Tracking Scripts</a>
            @if(config('affiliate.enabled'))
            <a href="{{ route('admin.affiliate.orders.index') }}" @class(['active' => request()->routeIs('admin.affiliate.*')])>Referral Program</a>
            @endif
        @else
            <a href="{{ route('member.dashboard') }}" @class(['active' => request()->routeIs('member.dashboard')])>Dashboard</a>
            @if($isAdmin)
                <a href="{{ route('admin.categories.index') }}" @class(['active' => request()->routeIs('admin.categories.*')])>Categories</a>
                <a href="{{ route('admin.themes.index') }}" @class(['active' => request()->routeIs('admin.themes.*')])>Frontend Theme</a>
                <a href="{{ route('admin.tracking.index') }}" @class(['active' => request()->routeIs('admin.tracking.*')])>Tracking Scripts</a>
            @endif
            <a href="{{ route('member.stores.index') }}" @class(['active' => request()->routeIs('member.stores.*')])>Stores</a>
            <a href="{{ route('member.coupons.index') }}" @class(['active' => request()->routeIs('member.coupons.*')])>Coupons</a>
            <a href="{{ route('member.posts.index') }}" @class(['active' => request()->routeIs('member.posts.*')])>Blogs</a>
        @endif
    </div>

    <hr class="sidebar-divider" aria-hidden="true">

    <div class="sidebar-nav-group">
        <a href="{{ route('member.import-affiliate.create') }}" @class(['active' => request()->routeIs('member.import-affiliate.*')])>Import from Affiliate Link</a>
        @if(config('affiliate.enabled'))
        <a href="{{ route('member.affiliate.index') }}" @class(['active' => request()->routeIs('member.affiliate.*')])>Referral Program</a>
        @endif
        <a href="{{ route('member.keywords.create') }}" @class(['active' => request()->routeIs('member.keywords.*')])>Keyword Generator</a>
    </div>

    <hr class="sidebar-divider" aria-hidden="true">

    <div class="sidebar-nav-group sidebar-nav-group--footer">
        <a href="{{ route('home') }}">← Back to site</a>
        <form action="{{ route('logout') }}" method="POST" class="sidebar-logout-form">
            @csrf
            <button type="submit" class="sidebar-logout-btn">Sign Out</button>
        </form>
    </div>
</nav>
