@php
    $category = $category ?? null;
    $class = $class ?? '';
    $fallback = $fallback ?? '🏷️';
    $size = $size ?? 'md';
    $iconUrl = $category?->iconUrl();
    $emoji = $category?->iconEmoji();
@endphp
@if($iconUrl)
    <img src="{{ $iconUrl }}" alt="" class="category-icon category-icon--img category-icon--{{ $size }} {{ $class }}" loading="lazy">
@elseif($emoji)
    <span class="category-icon category-icon--emoji category-icon--{{ $size }} {{ $class }}" aria-hidden="true">{{ $emoji }}</span>
@else
    <span class="category-icon category-icon--emoji category-icon--{{ $size }} {{ $class }}" aria-hidden="true">{{ $fallback }}</span>
@endif
