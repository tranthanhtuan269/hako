@extends('layouts.admin')

@section('title', 'Stores')

@section('content')
<div style="display:flex;justify-content:space-between;margin-bottom:1.5rem;">
    <h1>Stores</h1>
    <a href="{{ route('admin.stores.create') }}" class="btn btn-primary">+ Add Store</a>
</div>
<table class="admin-table">
    <thead>
        <tr>
            <th>Logo</th>
            <th>Name</th>
            <th>Slug</th>
            <th>Coupons</th>
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
                <td><code>{{ $store->slug }}</code></td>
                <td>{{ $store->coupons_count }}</td>
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
                <td colspan="5">No stores yet. <a href="{{ route('admin.stores.create') }}">Add a store</a>.</td>
            </tr>
        @endforelse
    </tbody>
</table>
{{ $stores->links() }}

@include('partials.table-actions-assets')
@endsection
