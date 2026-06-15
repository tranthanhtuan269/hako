<a href="{{ route('home') }}" class="sidebar-logo">
    <span class="sidebar-logo-icon" aria-hidden="true">%</span>
    <span class="sidebar-logo-text">
        <strong>{{ config('site.name') }}</strong>
        @if(!empty($showAdminBadge))
            <small>Admin</small>
        @else
            <small>{{ config('site.tagline') }}</small>
        @endif
    </span>
</a>
