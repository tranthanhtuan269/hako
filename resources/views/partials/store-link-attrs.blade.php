@php
    $store = $store ?? null;
@endphp
@if($store && $store->clickOpensExternal())
target="_blank" rel="noopener sponsored"
@endif
