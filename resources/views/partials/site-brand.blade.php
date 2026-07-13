<a href="{{ route('home') }}" @class([
    'site-brand',
    'site-brand--logo-only' => empty($siteBrandShowText),
])>
    @if(!empty($siteLogoUrl))
        <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" class="site-brand-logo">
    @else
        <span class="site-brand-icon">%</span>
    @endif
    @if(!empty($siteBrandShowText))
        <span class="site-brand-text">
            @if(!empty($siteDisplayName))
                <strong>{{ $siteDisplayName }}</strong>
            @endif
            @if(!empty($siteDisplayTagline))
                <small>{{ $siteDisplayTagline }}</small>
            @endif
        </span>
    @endif
</a>
