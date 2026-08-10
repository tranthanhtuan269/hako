@php
    $forceBrandText = ($activeTheme ?? '') === 'prime';
    $brandName = !empty($siteDisplayName) ? $siteDisplayName : ($forceBrandText ? ($siteName ?? config('site.name')) : null);
    $showBrandText = !empty($siteBrandShowText) || ($forceBrandText && filled($brandName));
@endphp
<a href="{{ route('home') }}" @class([
    'site-brand',
    'site-brand--logo-only' => ! $showBrandText,
])>
    @if(!empty($siteLogoUrl))
        <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" class="site-brand-logo">
    @else
        <span class="site-brand-icon">%</span>
    @endif
    @if($showBrandText)
        <span class="site-brand-text">
            @if(filled($brandName))
                <strong>{{ $brandName }}{{ $forceBrandText && ! str_ends_with($brandName, '.') ? '.' : '' }}</strong>
            @endif
            @if(!empty($siteDisplayTagline) && ! $forceBrandText)
                <small>{{ $siteDisplayTagline }}</small>
            @endif
        </span>
    @endif
</a>
