@extends('layouts.admin')

@section('title', 'Manage Coupons')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
    <h1>Coupons</h1>
    <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary">+ Add Coupon</a>
</div>
<table class="admin-table">
    <thead>
        <tr>
            <th>Title</th>
            <th>Store</th>
            <th>Code</th>
            <th>Type</th>
            <th>Status</th>
            <th>Clicks</th>
            <th class="table-actions-col">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($coupons as $coupon)
            <tr>
                <td>{{ $coupon->title }}</td>
                <td>{{ $coupon->store?->name }}</td>
                <td><code>{{ $coupon->code ?? '—' }}</code></td>
                <td>{{ $coupon->typeLabel() }}</td>
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
                <td colspan="7">No coupons yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>
{{ $coupons->links() }}

@include('partials.table-actions-assets')
@endsection
