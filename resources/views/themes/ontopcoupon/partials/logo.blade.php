@php
    $logoUrl = $store?->logoUrl();
    $class = trim('ot-logo '.($class ?? ''));
@endphp
@if($store)
<span class="{{ $class }}">
    @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $alt ?? $store->name }}" loading="lazy" onerror="this.remove();this.parentNode.classList.add('is-fallback');">
    @endif
    <span class="ot-logo-fallback">{{ $store->initials() ?: mb_substr($store->name, 0, 2) }}</span>
</span>
@endif
