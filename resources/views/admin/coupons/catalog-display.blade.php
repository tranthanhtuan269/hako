@extends('layouts.admin')

@section('title', 'Coupons Page Display')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
    <div>
        <h1 style="margin:0;">Coupons page display</h1>
        <p class="form-hint" style="margin:.35rem 0 0;">Choose which offers appear on the public <a href="{{ route('coupons.index') }}" target="_blank" rel="noopener">/coupons</a> page.</p>
    </div>
    <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline">← Back to coupons</a>
</div>

<form method="POST" action="{{ route('admin.coupons.catalog-display.update') }}">
    @csrf
    @method('PUT')

    <div class="form-group" style="margin-bottom:1.25rem;">
        <label for="coupons_page_limit">Maximum coupons per page</label>
        <input
            type="number"
            id="coupons_page_limit"
            name="coupons_page_limit"
            min="1"
            max="100"
            value="{{ old('coupons_page_limit', $couponsPageLimit) }}"
            style="max-width:8rem;"
        >
        <p class="form-hint">Only checked offers below are eligible. Up to this many show per page (sorted by order).</p>
        @error('coupons_page_limit')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    @if($coupons->isEmpty())
        <p class="form-hint">No coupons yet. <a href="{{ route('admin.coupons.create') }}">Add a coupon</a> first.</p>
    @else
        <div class="store-coupon-display-table-wrap">
            <table class="store-coupon-display-table">
                <thead>
                    <tr>
                        <th>Show</th>
                        <th>Offer</th>
                        <th>Store</th>
                        <th>Status</th>
                        <th>Order</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($coupons as $coupon)
                        @php
                            $selectedIds = old('show_on_coupons');
                            if ($selectedIds === null) {
                                $selectedIds = $coupons->where('show_on_coupons', true)->pluck('id')->all();
                            }
                            $checked = in_array($coupon->id, $selectedIds, true);
                        @endphp
                        <tr>
                            <td>
                                <input type="checkbox" name="show_on_coupons[]" value="{{ $coupon->id }}" @checked($checked)>
                            </td>
                            <td>
                                <strong>{{ $coupon->title }}</strong>
                                @if($coupon->code)
                                    <span class="form-hint" style="display:block;margin:.15rem 0 0;">Code: {{ $coupon->code }}</span>
                                @endif
                            </td>
                            <td>{{ $coupon->store?->name }}</td>
                            <td>
                                @if($coupon->is_active && ! $coupon->isExpired())
                                    <span class="store-coupon-display-status store-coupon-display-status--active">Active</span>
                                @else
                                    <span class="store-coupon-display-status">Hidden / expired</span>
                                @endif
                            </td>
                            <td>
                                <input
                                    type="number"
                                    name="coupons_sort_order[{{ $coupon->id }}]"
                                    min="0"
                                    max="9999"
                                    value="{{ old('coupons_sort_order.'.$coupon->id, $coupon->coupons_sort_order) }}"
                                    style="width:5rem;"
                                >
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="form-hint" style="margin-top:.75rem;">Higher order numbers appear first. Uncheck to hide from the coupons listing page.</p>
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
