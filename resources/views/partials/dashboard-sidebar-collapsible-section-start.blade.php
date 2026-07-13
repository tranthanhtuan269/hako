@php
    $sectionId = $sectionId ?? 'section';
    $sectionLabel = $sectionLabel ?? 'Section';
    $sectionOpen = (bool) ($sectionOpen ?? false);
@endphp
<div @class([
    'sidebar-nav-section',
    'sidebar-nav-section--collapsible',
    'is-open' => $sectionOpen,
]) data-sidebar-section="{{ $sectionId }}" @if($sectionOpen) data-sidebar-open-default @endif>
    <button type="button" class="sidebar-nav-section-toggle" aria-expanded="{{ $sectionOpen ? 'true' : 'false' }}">
        <span class="sidebar-nav-section-toggle-label">{{ $sectionLabel }}</span>
        <span class="sidebar-nav-chevron" aria-hidden="true"></span>
    </button>
    <div class="sidebar-nav-section-body">
