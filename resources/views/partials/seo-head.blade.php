@php
    use App\Support\Seo;

    $pageTitle = trim($__env->yieldContent('title') ?: 'Coupons & Discount Codes');
    $metaTitle = Seo::title($pageTitle);
    $metaDescription = trim($__env->yieldContent('meta_description'))
        ?: Seo::description(config('site.default_description'));
    $canonical = trim($__env->yieldContent('canonical')) ?: Seo::canonical();
    $robots = trim($__env->yieldContent('meta_robots')) ?: 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    $ogType = trim($__env->yieldContent('og_type')) ?: 'website';
    $ogImage = Seo::ogImage(trim($__env->yieldContent('og_image')) ?: null);
@endphp
<title>{{ $metaTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:locale" content="{{ str_replace('_', '-', config('site.locale')) }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:site_name" content="{{ config('site.name') }}">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">
@if(config('site.twitter_handle'))
<meta name="twitter:site" content="{{ config('site.twitter_handle') }}">
@endif

@stack('head_links')
