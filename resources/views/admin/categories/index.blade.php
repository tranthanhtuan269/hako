@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
<div class="admin-page-header">
    <h1>Categories</h1>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">+ Add Category</a>
</div>

<p class="form-hint" style="margin-bottom:1.25rem;">
    Categories group stores on the public site. Each store belongs to one category.
</p>

@if($categories->isEmpty())
    <div class="import-card">
        <p style="margin:0;">No categories yet. <a href="{{ route('admin.categories.create') }}">Create the first category</a>.</p>
    </div>
@else
    <table class="admin-table">
        <thead>
            <tr>
                <th>Icon</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Stores</th>
                <th>Coupons</th>
                <th>Order</th>
                <th>Status</th>
                <th class="table-actions-col">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categories as $category)
                <tr>
                    <td><span style="font-size:1.35rem;">{{ $category->icon ?: '—' }}</span></td>
                    <td><strong>{{ $category->name }}</strong></td>
                    <td><code>{{ $category->slug }}</code></td>
                    <td>{{ $category->stores_count }}</td>
                    <td>{{ $category->coupons_count }}</td>
                    <td>{{ $category->sort_order }}</td>
                    <td>
                        @if($category->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-muted">Hidden</span>
                        @endif
                    </td>
                    <td>
                        @include('partials.category-table-actions', ['category' => $category])
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $categories->links() }}
@endif

@include('partials.table-actions-assets')
@endsection

@push('styles')
<style>
.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.admin-page-header h1 { margin: 0; }
.badge {
    display: inline-block;
    padding: .2rem .5rem;
    border-radius: 4px;
    font-size: .75rem;
    font-weight: 600;
}
.badge-success { background: #dcfce7; color: #166534; }
.badge-muted { background: #f3f4f6; color: #6b7280; }
.import-card {
    background: var(--card, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
    padding: 1.25rem 1.5rem;
}
</style>
@endpush
