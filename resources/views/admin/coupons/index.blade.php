@extends('layouts.admin')

@section('title', 'Manage Coupons')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
    <div>
        <h1 style="margin:0;">Coupons</h1>
        <p class="form-hint" style="margin:.35rem 0 0;">Drag rows to set display order on the public <a href="{{ route('coupons.index') }}" target="_blank" rel="noopener">/coupons</a> page. Top rows appear first.</p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
        <a href="{{ route('admin.coupons.catalog-display') }}" class="btn btn-outline">Coupons page display</a>
        <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary">+ Add Coupon</a>
    </div>
</div>

<div class="coupon-sort-table-wrap">
<table class="admin-table coupon-sort-table">
    <thead>
        <tr>
            <th class="coupon-sort-col" aria-label="Reorder"></th>
            <th>Page order</th>
            <th>Title</th>
            <th>Store</th>
            <th>Code</th>
            <th>Type</th>
            <th>On /coupons</th>
            <th>Status</th>
            <th>Clicks</th>
            <th class="table-actions-col">Actions</th>
        </tr>
    </thead>
    <tbody id="coupon-sortable" data-reorder-url="{{ route('admin.coupons.sort-order') }}">
        @forelse($coupons as $coupon)
            <tr data-coupon-id="{{ $coupon->id }}">
                <td class="coupon-sort-col">
                    <button type="button" class="coupon-sort-handle" aria-label="Drag to reorder {{ $coupon->title }}" title="Drag to reorder">⠿</button>
                </td>
                <td><span class="coupon-sort-order" data-order-label>{{ $coupon->coupons_sort_order }}</span></td>
                <td>{{ $coupon->title }}</td>
                <td>{{ $coupon->store?->name }}</td>
                <td><code>{{ $coupon->code ?? '—' }}</code></td>
                <td>{{ $coupon->typeLabel() }}</td>
                <td>{{ $coupon->show_on_coupons ? 'Yes' : 'No' }}</td>
                <td>{{ $coupon->is_active ? 'Active' : 'Inactive' }}</td>
                <td><strong>{{ number_format($coupon->click_count) }}</strong></td>
                <td>
                    @include('partials.coupon-table-actions', [
                        'coupon' => $coupon,
                        'editUrl' => route('admin.coupons.edit', $coupon),
                        'destroyUrl' => route('admin.coupons.destroy', $coupon),
                    ])
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10">No coupons yet.</td>
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
<script src="{{ asset('js/admin-coupon-sort.js') }}?v={{ filemtime(public_path('js/admin-coupon-sort.js')) }}" defer></script>
@endpush
