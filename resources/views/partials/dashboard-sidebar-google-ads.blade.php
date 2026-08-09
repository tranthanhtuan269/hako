@php
    $isAdmin = $isAdmin ?? auth()->user()->isAdmin();
@endphp
<div class="sidebar-nav-section">
    <span class="sidebar-nav-section-label">Google Ads Builder</span>
    <a href="{{ route('member.keywords.create') }}"
        @class(['sidebar-nav-sublink', 'active' => request()->routeIs('member.keywords.*')])>
        Keyword &amp; Campaign Builder
    </a>
    @if($isAdmin ?? auth()->user()->isAdmin())
        <a href="{{ route('admin.ads-settings.index') }}"
            @class(['sidebar-nav-sublink', 'active' => request()->routeIs('admin.ads-settings.*')])>
            Ads Settings
        </a>
    @endif
</div>
