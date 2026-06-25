@extends('layouts.admin')

@section('title', 'Stores Page Display')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
    <div>
        <h1 style="margin:0;">Stores page display</h1>
        <p class="form-hint" style="margin:.35rem 0 0;">Choose which stores appear on the public <a href="{{ route('stores.index') }}" target="_blank" rel="noopener">/stores</a> page.</p>
    </div>
    <a href="{{ route('admin.stores.index') }}" class="btn btn-outline">← Back to stores</a>
</div>

<form method="POST" action="{{ route('admin.stores.catalog-display.update') }}">
    @csrf
    @method('PUT')

    <div class="form-group" style="margin-bottom:1.25rem;">
        <label for="stores_page_limit">Maximum stores per page</label>
        <input
            type="number"
            id="stores_page_limit"
            name="stores_page_limit"
            min="1"
            max="100"
            value="{{ old('stores_page_limit', $storesPageLimit) }}"
            style="max-width:8rem;"
        >
        <p class="form-hint">Only checked stores below are eligible. Up to this many show per page (sorted by order).</p>
        @error('stores_page_limit')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    @if($stores->isEmpty())
        <p class="form-hint">No stores yet. <a href="{{ route('admin.stores.create') }}">Add a store</a> first.</p>
    @else
        <div class="store-coupon-display-table-wrap">
            <table class="store-coupon-display-table">
                <thead>
                    <tr>
                        <th>Show</th>
                        <th>Store</th>
                        <th>Category</th>
                        <th>Offers</th>
                        <th>Status</th>
                        <th>Order</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stores as $store)
                        @php
                            $selectedIds = old('show_on_stores');
                            if ($selectedIds === null) {
                                $selectedIds = $stores->where('show_on_stores', true)->pluck('id')->all();
                            }
                            $checked = in_array($store->id, $selectedIds, true);
                        @endphp
                        <tr>
                            <td>
                                <input type="checkbox" name="show_on_stores[]" value="{{ $store->id }}" @checked($checked)>
                            </td>
                            <td>
                                <strong>{{ $store->name }}</strong>
                                <span class="form-hint" style="display:block;margin:.15rem 0 0;">{{ $store->slug }}</span>
                            </td>
                            <td>{{ $store->category?->name ?? '—' }}</td>
                            <td>{{ $store->coupons_count }}</td>
                            <td>
                                @if($store->is_active)
                                    <span class="store-coupon-display-status store-coupon-display-status--active">Active</span>
                                @else
                                    <span class="store-coupon-display-status">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <input
                                    type="number"
                                    name="stores_list_sort_order[{{ $store->id }}]"
                                    min="0"
                                    max="9999"
                                    value="{{ old('stores_list_sort_order.'.$store->id, $store->stores_list_sort_order) }}"
                                    style="width:5rem;"
                                >
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="form-hint" style="margin-top:.75rem;">Higher order numbers appear first. Uncheck to hide from the stores listing page.</p>
    @endif

    <button type="submit" class="btn btn-primary" style="margin-top:1.25rem;">Save display settings</button>
</form>
@endsection

@push('styles')
<style>
.store-coupon-display-table-wrap { overflow-x: auto; }
.store-coupon-display-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .9rem;
}
.store-coupon-display-table th,
.store-coupon-display-table td {
    border: 1px solid var(--border);
    padding: .6rem .65rem;
    text-align: left;
    vertical-align: top;
}
.store-coupon-display-table th {
    background: #f8fafc;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: var(--muted);
}
.store-coupon-display-status { font-size: .78rem; color: var(--muted); }
.store-coupon-display-status--active { color: #047857; font-weight: 600; }
.form-error { color: #dc2626; font-size: .875rem; margin-top: .35rem; }
</style>
@endpush
