<a href="{{ route('home') }}" class="site-brand">
    @if(!empty($siteLogoUrl))
        <img src="{{ $siteLogoUrl }}" alt="{{ config('site.name') }}" class="site-brand-logo" width="36" height="36">
    @else
        <span class="site-brand-icon">%</span>
    @endif
    <span class="site-brand-text">
        <strong>{{ config('site.name') }}</strong>
        <small>{{ config('site.tagline') }}</small>
    </span>
</a>
