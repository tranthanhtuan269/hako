@php
    $headerNavLinks = $headerNavLinks ?? collect();
    $headerNavConfigured = $headerNavConfigured ?? false;
@endphp
@if($headerNavConfigured)
    @foreach($headerNavLinks as $navLink)
        <a
            href="{{ $navLink->resolvedUrl() }}"
            @class(['is-current' => $navLink->isCurrent()])
            @if($navLink->open_in_new_tab) target="_blank" rel="noopener" @endif
        >{{ $navLink->label }}</a>
    @endforeach
@else
    <a href="{{ route('coupons.index') }}">Coupons</a>
    <a href="{{ route('coupons.index', ['type' => 'discount']) }}">Deals</a>
    <a href="{{ route('stores.index') }}">Stores</a>
    <a href="{{ route('categories.index') }}">Categories</a>
    <a href="{{ route('blog.index') }}">Blog</a>
@endif
