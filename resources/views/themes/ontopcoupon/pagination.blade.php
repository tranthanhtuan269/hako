@if ($paginator->hasPages())
<nav class="ot-pagination" aria-label="{{ $label ?? 'Pagination' }}">
    @if ($paginator->onFirstPage())
        <span class="ot-page ot-page--arrow is-disabled" aria-hidden="true">{!! \App\Support\OntopcouponTheme::icon('chevron-left', 16, '', 2.4) !!}</span>
    @else
        <a class="ot-page ot-page--arrow" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">{!! \App\Support\OntopcouponTheme::icon('chevron-left', 16, '', 2.4) !!}</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="ot-page ot-page--gap" aria-hidden="true">…</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="ot-page is-active" aria-current="page">{{ $page }}</span>
                @else
                    <a class="ot-page" href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a class="ot-page ot-page--arrow" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">{!! \App\Support\OntopcouponTheme::icon('chevron-right', 16, '', 2.4) !!}</a>
    @else
        <span class="ot-page ot-page--arrow is-disabled" aria-hidden="true">{!! \App\Support\OntopcouponTheme::icon('chevron-right', 16, '', 2.4) !!}</span>
    @endif
</nav>
@endif
