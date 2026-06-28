@extends('layouts.member')

@section('title', 'My Coupons')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
    <h1>My Coupons &amp; Deals</h1>
    <a href="{{ route('member.coupons.create') }}" class="btn btn-primary">+ Add Coupon</a>
</div>

@include('partials.coupon-index-filters', ['action' => route('member.coupons.index'), 'stores' => $stores])

<table class="admin-table">
    <thead>
        <tr>
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
    <tbody>
        @forelse($coupons as $coupon)
            <tr>
                <td>{{ $coupon->title }}</td>
                <td>{{ $coupon->store?->name }}</td>
                <td><code>{{ $coupon->code ?? '—' }}</code></td>
                <td>{{ $coupon->typeLabel() }}</td>
                <td>{{ $coupon->show_on_coupons ? 'Yes' : 'No' }}</td>
                <td>{{ $coupon->is_active ? 'Active' : 'Inactive' }}</td>
                <td><strong>{{ number_format($coupon->click_count) }}</strong></td>
                <td>
                    @include('partials.coupon-table-actions', ['coupon' => $coupon])
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8">
                    @if(request()->hasAny(['title', 'store_id']))
                        No coupons match your search.
                    @else
                        No coupons yet. <a href="{{ route('member.coupons.create') }}">Create one</a> (add a store first if needed).
                    @endif
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
{{ $coupons->links() }}

@include('partials.table-actions-assets')
@endsection
