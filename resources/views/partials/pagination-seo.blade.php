@if(isset($paginator) && $paginator instanceof \Illuminate\Contracts\Pagination\Paginator)
    @if($paginator->previousPageUrl())
        <link rel="prev" href="{{ $paginator->previousPageUrl() }}">
    @endif
    @if($paginator->hasMorePages())
        <link rel="next" href="{{ $paginator->nextPageUrl() }}">
    @endif
@endif
