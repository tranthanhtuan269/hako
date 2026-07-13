<a href="{{ route('home') }}" @class([
    'sidebar-logo',
    'sidebar-logo--text-only' => empty($siteLogoUrl) && !empty($siteBrandShowText),
    'sidebar-logo--logo-only' => !empty($siteLogoUrl) && empty($siteBrandShowText),
])>
    @if(!empty($siteLogoUrl))
        <img src="{{ $siteLogoUrl }}" alt="" class="sidebar-logo-img">
    @else
        <span class="sidebar-logo-icon" aria-hidden="true">%</span>
    @endif
    @if(!empty($siteBrandShowText))
        <span class="sidebar-logo-text">
            @if(!empty($siteDisplayName))
                <strong>{{ $siteDisplayName }}</strong>
            @endif
            @if(!empty($showAdminBadge))
                <small>Admin</small>
            @elseif(!empty($siteDisplayTagline))
                <small>{{ $siteDisplayTagline }}</small>
            @endif
        </span>
    @elseif(!empty($showAdminBadge))
        <span class="sidebar-logo-text">
            <strong>{{ $siteName }}</strong>
            <small>Admin</small>
        </span>
    @endif
</a>
