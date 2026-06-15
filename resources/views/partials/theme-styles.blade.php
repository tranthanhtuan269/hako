@php
    $themeKey = $activeTheme ?? 'classic';
    $themeConfig = config('themes.themes.' . $themeKey, config('themes.themes.classic'));
    $themeCss = public_path('css/' . $themeConfig['css']);
@endphp
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="{{ $themeConfig['font'] }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
@if(file_exists($themeCss))
    <link rel="stylesheet" href="{{ asset('css/' . $themeConfig['css']) }}?v={{ filemtime($themeCss) }}">
@endif
