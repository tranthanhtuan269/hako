@extends('layouts.admin')

@section('title', 'Stores')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
    <div>
        <h1 style="margin:0;">Stores</h1>
        <p class="form-hint" style="margin:.35rem 0 0;">
            @if(($sort ?? 'order') === 'order')
                Drag rows to set display order on the public <a href="{{ route('stores.index') }}" target="_blank" rel="noopener">/stores</a> page. Top rows appear first.
            @else
                Sorted view — click <strong>Page order</strong> to restore drag-and-drop display order.
            @endif
        </p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
        <a href="{{ route('admin.stores.export', array_filter([
            'q' => $q ?? null,
            'sort' => ($sort ?? 'order') !== 'order' ? $sort : null,
            'dir' => ($sort ?? 'order') !== 'order' ? $dir : null,
        ])) }}" class="btn btn-outline">Export store links</a>
        <a href="{{ route('admin.stores.catalog-display') }}" class="btn btn-outline">Stores page display</a>
        <a href="{{ route('admin.stores.create') }}" class="btn btn-primary">+ Add Store</a>
    </div>
</div>

@include('partials.admin-list-search', [
    'action' => route('admin.stores.index'),
    'value' => $q ?? '',
    'placeholder' => 'Search by store name…',
    'clearUrl' => route('admin.stores.index', array_filter([
        'sort' => ($sort ?? 'order') !== 'order' ? $sort : null,
        'dir' => ($sort ?? 'order') !== 'order' ? $dir : null,
        'date' => $statsDate ?? null,
    ])),
    'hiddenFields' => array_filter([
        'sort' => ($sort ?? 'order') !== 'order' ? $sort : null,
        'dir' => ($sort ?? 'order') !== 'order' ? $dir : null,
        'date' => $statsDate ?? null,
    ]),
])

@include('partials.click-stats-date-filter', [
    'action' => route('admin.stores.index'),
    'statsDate' => $statsDate ?? now()->toDateString(),
    'period' => $period ?? null,
    'preserve' => array_filter([
        'q' => $q ?? null,
        'sort' => ($sort ?? 'order') !== 'order' ? $sort : null,
        'dir' => ($sort ?? 'order') !== 'order' ? $dir : null,
    ]),
])

<div class="coupon-sort-table-wrap">
<table class="admin-table coupon-sort-table">
    <thead>
        <tr>
            <th class="coupon-sort-col" aria-label="Reorder"></th>
            @include('partials.table-sort-th', ['column' => 'order', 'label' => 'Page order', 'currentSort' => $sort ?? 'order', 'currentDir' => $dir ?? 'desc'])
            <th>Logo</th>
            @include('partials.table-sort-th', ['column' => 'name', 'label' => 'Name', 'currentSort' => $sort ?? 'order', 'currentDir' => $dir ?? 'desc'])
            <th>Home</th>
            <th>On /stores</th>
            @include('partials.table-sort-th', ['column' => 'coupons', 'label' => 'Coupons', 'currentSort' => $sort ?? 'order', 'currentDir' => $dir ?? 'desc'])
            <th>Day</th>
            <th>Month</th>
            <th>Year</th>
            @include('partials.table-sort-th', ['column' => 'clicks', 'label' => 'Total', 'currentSort' => $sort ?? 'order', 'currentDir' => $dir ?? 'desc'])
            @include('partials.table-sort-th', ['column' => 'created_at', 'label' => 'Created', 'currentSort' => $sort ?? 'order', 'currentDir' => $dir ?? 'desc'])
            <th>Status</th>
            <th class="table-actions-col">Actions</th>
        </tr>
    </thead>
    <tbody id="store-sortable"
        data-reorder-url="{{ route('admin.stores.sort-order') }}"
        data-sort-enabled="{{ ($sort ?? 'order') === 'order' ? '1' : '0' }}">
        @forelse($stores as $store)
            <tr data-store-id="{{ $store->id }}">
                <td class="coupon-sort-col">
                    @if(($sort ?? 'order') === 'order')
                        <button type="button" class="coupon-sort-handle" aria-label="Drag to reorder {{ $store->name }}" title="Drag to reorder">⠿</button>
                    @else
                        <span class="coupon-sort-handle coupon-sort-handle--disabled" aria-hidden="true">⠿</span>
                    @endif
                </td>
                <td><span class="coupon-sort-order" data-order-label>{{ $store->stores_list_sort_order }}</span></td>
                <td>
                    @if($store->logoUrl())
                        <img src="{{ $store->logoUrl() }}" alt="{{ $store->name }}" class="admin-thumb" loading="lazy">
                    @else
                        <span class="admin-thumb-fallback">{{ $store->initials() }}</span>
                    @endif
                </td>
                <td>{{ $store->name }}</td>
                <td>
                    <form action="{{ route('admin.stores.toggle-home-pin', $store) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('PATCH')
                        @foreach(request()->only(['q', 'sort', 'dir', 'date']) as $key => $value)
                            @if(filled($value))
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <button type="submit" class="btn btn-outline btn-sm" title="{{ $store->is_pinned_home ? 'Unpin from homepage' : 'Pin to homepage' }}">
                            {{ $store->is_pinned_home ? '📌 Pinned' : 'Pin' }}
                        </button>
                    </form>
                </td>
                <td>{{ $store->show_on_stores ? 'Yes' : 'No' }}</td>
                <td>
                    <button
                        type="button"
                        class="btn btn-outline btn-sm js-store-coupons-open"
                        data-coupons-url="{{ route('admin.stores.coupons', $store) }}"
                        data-store-name="{{ $store->name }}"
                        title="Manage coupons for {{ $store->name }}"
                    >
                        {{ number_format($store->coupons_count) }}
                    </button>
                </td>
                <td><strong>{{ number_format((int) ($store->day_clicks ?? 0)) }}</strong></td>
                <td>{{ number_format((int) ($store->month_clicks ?? 0)) }}</td>
                <td>{{ number_format((int) ($store->year_clicks ?? 0)) }}</td>
                <td><strong>{{ number_format((int) ($store->coupons_click_sum ?? 0)) }}</strong></td>
                <td>{{ $store->created_at?->format('M j, Y') }}</td>
                <td>{{ $store->is_active ? 'Active' : 'Inactive' }}</td>
                <td>
                    @include('partials.store-table-actions', [
                        'store' => $store,
                        'editUrl' => route('admin.stores.edit', $store),
                        'destroyUrl' => route('admin.stores.destroy', $store),
                        'showAdsToggle' => true,
                    ])
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="14">
                    @if(filled($q ?? null))
                        No stores match your search.
                    @else
                        No stores yet. <a href="{{ route('admin.stores.create') }}">Add a store</a>.
                    @endif
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>

@include('partials.table-actions-assets')
@include('partials.admin-store-coupons-modal')
@endsection

@push('styles')
<style>
.coupon-sort-col { width: 2.5rem; text-align: center; }
.coupon-sort-handle {
    border: 0;
    background: transparent;
    color: var(--muted);
    cursor: grab;
    font-size: 1.1rem;
    line-height: 1;
    padding: .15rem .35rem;
}
.coupon-sort-handle--disabled {
    cursor: not-allowed;
    opacity: .35;
}
.coupon-sort-handle:active { cursor: grabbing; }
.coupon-sort-table tr.is-dragging { opacity: .55; }
.coupon-sort-table tr.is-drop-target td { background: #eff6ff; }
.coupon-sort-order {
    display: inline-block;
    min-width: 2rem;
    font-weight: 600;
    color: #1d4ed8;
}
.coupon-sort-status {
    margin: 0 0 .75rem;
    font-size: .875rem;
    color: var(--muted);
}
.coupon-sort-status[data-type="success"] { color: #047857; }
.coupon-sort-status[data-type="error"] { color: #dc2626; }
.btn-sm { padding: .25rem .55rem; font-size: .8125rem; }

.store-coupons-modal[hidden] { display: none !important; }
.store-coupons-modal {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.store-coupons-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, .45);
}
.store-coupons-modal__dialog {
    position: relative;
    z-index: 1;
    width: min(1100px, 100%);
    max-height: min(90vh, 900px);
    display: flex;
    flex-direction: column;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, .25);
    overflow: hidden;
}
.store-coupons-modal__header,
.store-coupons-modal__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border);
    background: #f8fafc;
}
.store-coupons-modal__footer {
    border-bottom: 0;
    border-top: 1px solid var(--border);
    justify-content: flex-end;
}
.store-coupons-modal__header h3 {
    margin: 0;
    font-size: 1.1rem;
}
.store-coupons-modal__close {
    border: 0;
    background: transparent;
    font-size: 1.5rem;
    line-height: 1;
    color: var(--muted);
    cursor: pointer;
    padding: .15rem .4rem;
}
.store-coupons-modal__body {
    padding: 1rem 1.25rem;
    overflow: auto;
}
.store-coupons-modal__loading,
.store-coupons-modal__empty {
    color: var(--muted);
    margin: .5rem 0;
}
.store-coupons-table-wrap { overflow-x: auto; }
.store-coupons-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .875rem;
}
.store-coupons-table th,
.store-coupons-table td {
    border: 1px solid var(--border);
    padding: .55rem .6rem;
    vertical-align: top;
    text-align: left;
}
.store-coupons-table th {
    background: #f8fafc;
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: var(--muted);
    white-space: nowrap;
}
.store-coupons-input {
    width: 100%;
    min-width: 8rem;
    padding: .4rem .5rem;
    border: 1px solid var(--border);
    border-radius: 6px;
    font: inherit;
}
.store-coupons-input--desc {
    margin-top: .4rem;
    min-width: 14rem;
    resize: vertical;
}
.store-coupons-input--date { min-width: 11rem; }
.store-coupons-flags {
    display: grid;
    gap: .35rem;
    white-space: nowrap;
}
.store-coupons-flags label {
    display: flex;
    align-items: center;
    gap: .35rem;
    font-size: .8125rem;
    margin: 0;
}
.store-coupons-row-actions {
    display: flex;
    flex-direction: column;
    gap: .35rem;
    min-width: 5.5rem;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/admin-store-sort.js') }}?v={{ filemtime(public_path('js/admin-store-sort.js')) }}" defer></script>
<script src="{{ asset('js/admin-store-coupons-popup.js') }}?v={{ filemtime(public_path('js/admin-store-coupons-popup.js')) }}" defer></script>
@endpush
