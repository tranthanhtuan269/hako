@php
    $store = $store ?? null;
    $size = $size ?? 'md';
    $showVerified = $showVerified ?? false;
    $showName = $showName ?? false;
    $linked = $linked ?? true;
    $logoPixels = match ($size) {
        'xxl' => 112,
        'xl' => 88,
        'lg' => 64,
        default => 52,
    };
@endphp

@if($store)
<div class="store-logo-wrap store-logo-wrap--{{ $size }}">
    @if($linked)
        <a href="{{ route('stores.show', $store->slug) }}" class="store-logo" title="{{ $store->name }}">
    @else
        <div class="store-logo" aria-hidden="true">
    @endif
        @if($store->logoUrl())
            <img
                src="{{ $store->logoUrl() }}"
                alt=""
                class="store-logo-img"
                loading="lazy"
                width="{{ $logoPixels }}"
                height="{{ $logoPixels }}"
                onerror="this.style.display='none';this.nextElementSibling?.classList.add('is-visible');"
            >
        @endif
        <span class="store-logo-fallback {{ $store->logoUrl() ? '' : 'is-visible' }}">{{ $store->initials() }}</span>
    @if($linked)
        </a>
    @else
        </div>
    @endif
    @if($showVerified)
        <span class="store-verified" title="Featured store">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            Featured
        </span>
    @endif
    @if($showName && $linked)
        <a href="{{ route('stores.show', $store->slug) }}" class="store-logo-name">{{ $store->name }}</a>
    @elseif($showName)
        <span class="store-logo-name">{{ $store->name }}</span>
    @endif
</div>
@endif
