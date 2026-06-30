<a href="{{ route('home') }}" class="sidebar-logo">
    @if(!empty($siteLogoUrl))
        <img src="{{ $siteLogoUrl }}" alt="" class="sidebar-logo-img" width="36" height="36">
    @else
        <span class="sidebar-logo-icon" aria-hidden="true">%</span>
    @endif
    <span class="sidebar-logo-text">
        <strong>{{ config('site.name') }}</strong>
        @if(!empty($showAdminBadge))
            <small>Admin</small>
        @else
            <small>{{ config('site.tagline') }}</small>
        @endif
    </span>
</a>
