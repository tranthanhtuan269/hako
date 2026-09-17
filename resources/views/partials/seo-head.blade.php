@php
    use App\Support\HtmlCleaner;
    use App\Support\Seo;

    $yielded = static function (string $section, string $default = '') use ($__env): string {
        return HtmlCleaner::decodeEntities(trim($__env->yieldContent($section) ?: $default));
    };

    $pageTitle = $yielded('title', 'Coupons & Discount Codes');
    $metaTitle = Seo::title($pageTitle);
    $metaDescription = $yielded('meta_description')
        ?: Seo::description(config('site.default_description'));

    $ogTitle = $yielded('og_title') ?: $pageTitle;
    $ogDescription = $yielded('og_description') ?: $metaDescription;

    $canonical = $yielded('canonical') ?: Seo::canonical();
    $ogUrl = $yielded('og_url') ?: $canonical;
    if (! preg_match('#^https?://#i', $ogUrl)) {
        $ogUrl = Seo::absoluteUrl($ogUrl);
    }
    if (! preg_match('#^https?://#i', $canonical)) {
        $canonical = Seo::absoluteUrl($canonical);
    }

    $robots = $yielded('meta_robots') ?: 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    $ogType = $yielded('og_type') ?: 'website';
    $ogImage = Seo::ogImage($yielded('og_image') ?: null);
    $ogImageAlt = $yielded('og_image_alt') ?: $ogTitle;
@endphp
<title>{{ $metaTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
<meta name="robots" content="{{ $robots }}">
@include('partials.favicon')
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:locale" content="{{ str_replace('_', '-', config('site.locale')) }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:url" content="{{ $ogUrl }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:alt" content="{{ $ogImageAlt }}">
@stack('og_meta')

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ $ogDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">
@if(config('site.twitter_handle'))
<meta name="twitter:site" content="{{ config('site.twitter_handle') }}">
@endif

@stack('head_links')
