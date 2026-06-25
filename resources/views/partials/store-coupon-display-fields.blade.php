@if($store->exists)
@php
    $storeCoupons = $storeCoupons ?? collect();
@endphp
<div class="form-group store-coupon-display">
    <h3 style="margin:0 0 .75rem;font-size:1.05rem;">Store page coupons</h3>
    <p class="form-hint" style="margin-bottom:1rem;">Choose which offers appear on the public store page and how many to show.</p>

    <div class="form-group" style="margin-bottom:1rem;">
        <label for="store_coupon_limit">Maximum coupons displayed</label>
        <input
            type="number"
            id="store_coupon_limit"
            name="store_coupon_limit"
            min="1"
            max="100"
            value="{{ old('store_coupon_limit', $store->store_coupon_limit ?? 16) }}"
            style="max-width:8rem;"
        >
        <p class="form-hint">Only checked offers below are eligible. Up to this many will show (sorted by order).</p>
        @error('store_coupon_limit')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    @if($storeCoupons->isEmpty())
        <p class="form-hint">No coupons for this store yet. Add coupons first, then return here to choose what appears on the store page.</p>
    @else
        <div class="store-coupon-display-table-wrap">
            <table class="store-coupon-display-table">
                <thead>
                    <tr>
                        <th>Show</th>
                        <th>Offer</th>
                        <th>Status</th>
                        <th>Order</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($storeCoupons as $coupon)
                        @php
                            $selectedIds = old('show_on_store');
                            if ($selectedIds === null) {
                                $selectedIds = $storeCoupons->where('show_on_store', true)->pluck('id')->all();
                            }
                            $checked = in_array($coupon->id, $selectedIds, true);
                        @endphp
                        <tr>
                            <td>
                                <input
                                    type="checkbox"
                                    name="show_on_store[]"
                                    value="{{ $coupon->id }}"
                                    @checked($checked)
                                >
                            </td>
                            <td>
                                <strong>{{ $coupon->title }}</strong>
                                @if($coupon->code)
                                    <span class="form-hint" style="display:block;margin:.15rem 0 0;">Code: {{ $coupon->code }}</span>
                                @endif
                            </td>
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
                                    name="store_coupon_sort[{{ $coupon->id }}]"
                                    min="0"
                                    max="9999"
                                    value="{{ old('store_coupon_sort.'.$coupon->id, $coupon->store_sort_order) }}"
                                    style="width:5rem;"
                                >
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="form-hint" style="margin-top:.75rem;">Higher order numbers appear first. Uncheck an offer to hide it from the store page.</p>
    @endif
</div>

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
.store-coupon-display-status {
    font-size: .78rem;
    color: var(--muted);
}
.store-coupon-display-status--active {
    color: #047857;
    font-weight: 600;
}
.form-error { color: #dc2626; font-size: .875rem; margin-top: .35rem; }
</style>
@endpush
@endif
