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
        <a href="{{ route('admin.stores.catalog-display') }}" class="btn btn-outline">Stores page display</a>
        <a href="{{ route('admin.stores.create') }}" class="btn btn-primary">+ Add Store</a>
    </div>
</div>

<div class="coupon-sort-table-wrap">
<table class="admin-table coupon-sort-table">
    <thead>
        <tr>
            <th class="coupon-sort-col" aria-label="Reorder"></th>
            @include('partials.table-sort-th', ['column' => 'order', 'label' => 'Page order', 'currentSort' => $sort ?? 'order', 'currentDir' => $dir ?? 'desc'])
            <th>Logo</th>
            @include('partials.table-sort-th', ['column' => 'name', 'label' => 'Name', 'currentSort' => $sort ?? 'order', 'currentDir' => $dir ?? 'desc'])
            <th>On /stores</th>
            @include('partials.table-sort-th', ['column' => 'coupons', 'label' => 'Coupons', 'currentSort' => $sort ?? 'order', 'currentDir' => $dir ?? 'desc'])
            @include('partials.table-sort-th', ['column' => 'clicks', 'label' => 'Clicks', 'currentSort' => $sort ?? 'order', 'currentDir' => $dir ?? 'desc'])
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
                <td>{{ $store->show_on_stores ? 'Yes' : 'No' }}</td>
                <td>{{ number_format($store->coupons_count) }}</td>
                <td><strong>{{ number_format((int) ($store->coupons_click_sum ?? 0)) }}</strong></td>
                <td>{{ $store->created_at?->format('M j, Y') }}</td>
                <td>{{ $store->is_active ? 'Active' : 'Inactive' }}</td>
                <td>
                    @include('partials.store-table-actions', [
                        'store' => $store,
                        'editUrl' => route('admin.stores.edit', $store),
                        'destroyUrl' => route('admin.stores.destroy', $store),
                    ])
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10">No stores yet. <a href="{{ route('admin.stores.create') }}">Add a store</a>.</td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>

@include('partials.table-actions-assets')
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
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/admin-store-sort.js') }}?v={{ filemtime(public_path('js/admin-store-sort.js')) }}" defer></script>
@endpush
