@php
    $currentSort = $currentSort ?? 'clicks';
    $currentDir = $currentDir ?? 'desc';
    $isActive = $currentSort === $column;

    if ($column === 'latest' || $column === 'order') {
        $url = request()->fullUrlWithQuery(['sort' => null, 'dir' => null]);
    } else {
        $nextDir = $isActive && $currentDir === 'desc' ? 'asc' : 'desc';
        $url = request()->fullUrlWithQuery(['sort' => $column, 'dir' => $nextDir]);
    }
@endphp
<th @if($isActive) aria-sort="{{ $currentDir === 'asc' ? 'ascending' : 'descending' }}" @endif>
    <a href="{{ $url }}" class="table-sort-link{{ $isActive ? ' is-active' : '' }}">
        <span>{{ $label }}</span>
        @if($isActive && ! in_array($column, ['latest', 'order'], true))
            <span class="table-sort-indicator" aria-hidden="true">{{ $currentDir === 'desc' ? '↓' : '↑' }}</span>
        @endif
    </a>
</th>
