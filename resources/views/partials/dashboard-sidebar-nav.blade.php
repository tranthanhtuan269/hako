@php
    $panel = $panel ?? 'member';
    $isAdmin = auth()->user()->isAdmin();
    $showGoogleAdsBuilder = (bool) config('site.google_ads_builder_enabled', false);

    $siteConfigOpen = request()->routeIs(
        'admin.branding.*',
        'admin.integrations.*',
        'admin.domain-change.*',
        'admin.account.*',
        'admin.tracking.*',
        'admin.themes.*',
        'admin.hero-slider.*',
    );
    $contentOpen = request()->routeIs(
        'admin.posts.*',
        'admin.categories.*',
        'admin.stores.*',
        'admin.coupons.*',
        'member.import-affiliate.*',
        'admin.affiliate-excel-import.*',
        'member.posts.*',
        'member.stores.*',
        'member.coupons.*',
    );
    $affiliateOpen = request()->routeIs(
        'admin.affiliate-signups.*',
        'admin.affiliate.*',
        'member.affiliate.*',
    );
    $googleAdsOpen = request()->routeIs('member.keywords.*', 'admin.ads-settings.*');
@endphp
<nav class="sidebar-nav" aria-label="Dashboard menu">
    @if($panel === 'admin' || $isAdmin)
        <div class="sidebar-nav-group">
            @if($panel === 'admin')
                <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>Dashboard</a>
            @else
                <a href="{{ route('member.dashboard') }}" @class(['active' => request()->routeIs('member.dashboard')])>Dashboard</a>
            @endif
        </div>

        <hr class="sidebar-divider" aria-hidden="true">
    @endif

    @if($panel === 'admin')
        <div class="sidebar-nav-group">
            @include('partials.dashboard-sidebar-collapsible-section-start', [
                'sectionId' => 'site-config',
                'sectionLabel' => 'Site Configuration',
                'sectionOpen' => $siteConfigOpen,
            ])
                <a href="{{ route('admin.branding.index') }}"
                    @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.branding.*')])>Logo &amp; Social</a>
                <a href="{{ route('admin.integrations.index') }}"
                    @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.integrations.*')])>Integrations</a>
                <a href="{{ route('admin.domain-change.index') }}"
                    @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.domain-change.*')])>Change Domain</a>
                <a href="{{ route('admin.account.edit') }}"
                    @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.account.*')])>Account</a>
                <a href="{{ route('admin.tracking.index') }}"
                    @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.tracking.*')])>Tracking Scripts</a>
                <a href="{{ route('admin.themes.index') }}"
                    @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.themes.*')])>Frontend Theme</a>
                <a href="{{ route('admin.hero-slider.index') }}"
                    @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.hero-slider.*')])>Homepage Slider</a>
            @include('partials.dashboard-sidebar-collapsible-section-end')
        </div>

        <hr class="sidebar-divider" aria-hidden="true">

        <div class="sidebar-nav-group">
            @include('partials.dashboard-sidebar-collapsible-section-start', [
                'sectionId' => 'content',
                'sectionLabel' => 'Content',
                'sectionOpen' => $contentOpen,
            ])
                <a href="{{ route('admin.posts.index') }}"
                    @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.posts.*')])>Blogs</a>
                <a href="{{ route('admin.categories.index') }}"
                    @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.categories.*')])>Categories</a>
                <a href="{{ route('admin.stores.index') }}"
                    @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.stores.*')])>Stores</a>
                <a href="{{ route('admin.coupons.index') }}"
                    @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.coupons.*')])>Coupons</a>
                <a href="{{ route('member.import-affiliate.create') }}"
                    @class(['sidebar-nav-sublink', 'active' => request()->routeIs('member.import-affiliate.*')])>Import from Affiliate Link</a>
                <a href="{{ route('admin.affiliate-excel-import.index') }}"
                    @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.affiliate-excel-import.*')])>Auto Import (Excel)</a>
            @include('partials.dashboard-sidebar-collapsible-section-end')
        </div>

        <hr class="sidebar-divider" aria-hidden="true">

        <div class="sidebar-nav-group">
            @include('partials.dashboard-sidebar-collapsible-section-start', [
                'sectionId' => 'affiliate',
                'sectionLabel' => 'Affiliate',
                'sectionOpen' => $affiliateOpen,
            ])
                <a href="{{ route('admin.affiliate-signups.index') }}"
                    @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.affiliate-signups.*')])>Affiliate Signups</a>
                @if(config('affiliate.enabled'))
                    <a href="{{ route('admin.affiliate.orders.index') }}"
                        @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.affiliate.*')])>Referral Program</a>
                @endif
            @include('partials.dashboard-sidebar-collapsible-section-end')
        </div>
    @else
        @if($isAdmin)
            <div class="sidebar-nav-group">
                @include('partials.dashboard-sidebar-collapsible-section-start', [
                    'sectionId' => 'site-config',
                    'sectionLabel' => 'Site Configuration',
                    'sectionOpen' => $siteConfigOpen,
                ])
                    <a href="{{ route('admin.branding.index') }}"
                        @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.branding.*')])>Logo &amp; Social</a>
                    <a href="{{ route('admin.integrations.index') }}"
                        @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.integrations.*')])>Integrations</a>
                    <a href="{{ route('admin.domain-change.index') }}"
                        @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.domain-change.*')])>Change Domain</a>
                    <a href="{{ route('admin.tracking.index') }}"
                        @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.tracking.*')])>Tracking Scripts</a>
                    <a href="{{ route('admin.themes.index') }}"
                        @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.themes.*')])>Frontend Theme</a>
                    <a href="{{ route('admin.hero-slider.index') }}"
                        @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.hero-slider.*')])>Homepage Slider</a>
                @include('partials.dashboard-sidebar-collapsible-section-end')
            </div>

            <hr class="sidebar-divider" aria-hidden="true">

            <div class="sidebar-nav-group">
                @include('partials.dashboard-sidebar-collapsible-section-start', [
                    'sectionId' => 'content',
                    'sectionLabel' => 'Content',
                    'sectionOpen' => $contentOpen,
                ])
                    <a href="{{ route('member.posts.index') }}"
                        @class(['sidebar-nav-sublink', 'active' => request()->routeIs('member.posts.*')])>Blogs</a>
                    <a href="{{ route('admin.categories.index') }}"
                        @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.categories.*')])>Categories</a>
                    <a href="{{ route('member.stores.index') }}"
                        @class(['sidebar-nav-sublink', 'active' => request()->routeIs('member.stores.*')])>Stores</a>
                    <a href="{{ route('member.coupons.index') }}"
                        @class(['sidebar-nav-sublink', 'active' => request()->routeIs('member.coupons.*')])>Coupons</a>
                    <a href="{{ route('member.import-affiliate.create') }}"
                        @class(['sidebar-nav-sublink', 'active' => request()->routeIs('member.import-affiliate.*')])>Import from Affiliate Link</a>
                    <a href="{{ route('admin.affiliate-excel-import.index') }}"
                        @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.affiliate-excel-import.*')])>Auto Import (Excel)</a>
                @include('partials.dashboard-sidebar-collapsible-section-end')
            </div>
            <hr class="sidebar-divider" aria-hidden="true">

            <div class="sidebar-nav-group">
                @include('partials.dashboard-sidebar-collapsible-section-start', [
                    'sectionId' => 'affiliate',
                    'sectionLabel' => 'Affiliate',
                    'sectionOpen' => $affiliateOpen,
                ])
                    <a href="{{ route('admin.affiliate-signups.index') }}"
                        @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.affiliate-signups.*')])>Affiliate Signups</a>
                    @if(config('affiliate.enabled'))
                        <a href="{{ route('admin.affiliate.orders.index') }}"
                            @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.affiliate.*')])>Referral Program</a>
                    @endif
                @include('partials.dashboard-sidebar-collapsible-section-end')
            </div>
        @elseif(config('affiliate.enabled'))
            <div class="sidebar-nav-group">
                <a href="{{ route('member.affiliate.index') }}" @class(['active' => request()->routeIs('member.affiliate.*')])>Referral Program</a>
            </div>
        @endif
    @endif

    @if($showGoogleAdsBuilder && $isAdmin)
        <hr class="sidebar-divider" aria-hidden="true">

        <div class="sidebar-nav-group">
            @include('partials.dashboard-sidebar-collapsible-section-start', [
                'sectionId' => 'google-ads',
                'sectionLabel' => 'Google Ads Builder',
                'sectionOpen' => $googleAdsOpen,
            ])
                <a href="{{ route('member.keywords.create') }}"
                    @class(['sidebar-nav-sublink', 'active' => request()->routeIs('member.keywords.*')])>Keyword Generator</a>
                <a href="{{ route('admin.ads-settings.index') }}"
                    @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.ads-settings.*')])>Ads Settings</a>
            @include('partials.dashboard-sidebar-collapsible-section-end')
        </div>
    @endif
    <hr class="sidebar-divider" aria-hidden="true">

    <div class="sidebar-nav-group sidebar-nav-group--footer">
        <a href="{{ route('home') }}">← Back to site</a>
        <form action="{{ route('logout') }}" method="POST" class="sidebar-logout-form">
            @csrf
            <button type="submit" class="sidebar-logout-btn">Sign Out</button>
        </form>
    </div>
</nav>

@once
    @push('scripts')
    <script>
    (() => {
        const storageKey = 'hako-sidebar-sections';
        let saved = {};

        try {
            saved = JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
        } catch (error) {
            saved = {};
        }

        document.querySelectorAll('[data-sidebar-section]').forEach((section) => {
            const id = section.dataset.sidebarSection;
            const toggle = section.querySelector('.sidebar-nav-section-toggle');
            const hasActiveLink = !!section.querySelector('.sidebar-nav-sublink.active, a.active');
            const defaultOpen = section.hasAttribute('data-sidebar-open-default') || hasActiveLink;
            const open = Object.prototype.hasOwnProperty.call(saved, id) ? !!saved[id] : defaultOpen;

            section.classList.toggle('is-open', open);
            if (toggle) {
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            }

            toggle?.addEventListener('click', () => {
                const nextOpen = !section.classList.contains('is-open');
                section.classList.toggle('is-open', nextOpen);
                toggle.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
                saved[id] = nextOpen;
                localStorage.setItem(storageKey, JSON.stringify(saved));
            });
        });
    })();
    </script>
    @endpush
@endonce
