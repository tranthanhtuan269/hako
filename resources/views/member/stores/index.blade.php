@extends('layouts.member')

@section('title', 'My Stores')

@section('content')
<div style="display:flex;justify-content:space-between;margin-bottom:1.5rem;">
    <h1>My Stores</h1>
    <a href="{{ route('member.stores.create') }}" class="btn btn-primary">+ Add Store</a>
</div>
<table class="admin-table">
    <thead>
        <tr>
            <th>Logo</th>
            <th>Name</th>
            <th>Category</th>
            <th>Coupons</th>
            <th>Page views</th>
            <th>Created</th>
            <th class="table-actions-col">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($stores as $store)
            <tr>
                <td>
                    @if($store->logoUrl())
                        <img src="{{ $store->logoUrl() }}" alt="{{ $store->name }}" class="admin-thumb" loading="lazy">
                    @else
                        <span class="admin-thumb-fallback">{{ $store->initials() }}</span>
                    @endif
                </td>
                <td>{{ $store->name }}</td>
                <td>
                    @if($store->category)
                        <span class="category-inline">@include('partials.category-icon', ['category' => $store->category, 'size' => 'sm']) {{ $store->category->name }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>{{ $store->coupons_count }}</td>
                <td><strong>{{ number_format($store->view_count) }}</strong></td>
                <td>{{ $store->created_at?->format('M j, Y') }}</td>
                <td>
                    @include('partials.store-table-actions', ['store' => $store])
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">No stores yet. <a href="{{ route('member.stores.create') }}">Add your first store</a>.</td>
            </tr>
        @endforelse
    </tbody>
</table>
{{ $stores->links() }}

@include('partials.table-actions-assets')
@endsection

@push('styles')
<style>.text-muted { color: #94a3b8; }</style>
@endpush
