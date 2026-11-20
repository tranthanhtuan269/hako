@extends('layouts.admin')

@section('title', 'Top Menu')

@section('content')
<div class="admin-page-header">
    <h1>Top Menu</h1>
    <a href="{{ route('admin.nav-links.create') }}" class="btn btn-primary">+ Add item</a>
</div>

<p class="form-hint" style="margin-bottom:1.25rem;">
    Đây là menu trên header hiện tại: Coupons, Deals, Stores, Categories, Blog.
    Sửa / thêm / đổi thứ tự ở đây sẽ đổi ngay trên header. Bấm Active / Hidden trên bảng để ẩn hiện. Sign In / Sign Up vẫn tự động.
    Dùng path kiểu <code>/coupons</code> hoặc URL đầy đủ <code>https://example.com</code>.
</p>

<div class="import-card" style="margin-bottom:1.25rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
        <div>
            <strong>Header search</strong>
            <p class="form-hint" style="margin:0.25rem 0 0;">Thanh search trên header (cạnh logo). Bấm status để ẩn/hiện.</p>
        </div>
        <form action="{{ route('admin.nav-links.toggle-search') }}" method="POST" style="display:inline;margin:0;">
            @csrf
            @method('PATCH')
            <button type="submit" class="badge {{ ($headerSearchVisible ?? true) ? 'badge-success' : 'badge-muted' }} badge-toggle" title="{{ ($headerSearchVisible ?? true) ? 'Click to hide header search' : 'Click to show header search' }}">
                {{ ($headerSearchVisible ?? true) ? 'Active' : 'Hidden' }}
            </button>
        </form>
    </div>
</div>

@if($links->isEmpty())
    <div class="import-card">
        <p style="margin:0;">No menu items yet. <a href="{{ route('admin.nav-links.create') }}">Add the first item</a>.</p>
    </div>
@else
    <table class="admin-table">
        <thead>
            <tr>
                <th>Label</th>
                <th>URL</th>
                <th>Order</th>
                <th>Status</th>
                <th class="table-actions-col">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($links as $link)
                <tr>
                    <td><strong>{{ $link->label }}</strong></td>
                    <td>
                        <code>{{ $link->url }}</code>
                        @if($link->open_in_new_tab)
                            <span class="form-hint" style="display:inline;margin-left:.35rem;">new tab</span>
                        @endif
                    </td>
                    <td>{{ $link->sort_order }}</td>
                    <td>
                        <form action="{{ route('admin.nav-links.toggle-active', $link) }}" method="POST" style="display:inline;margin:0;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="badge {{ $link->is_active ? 'badge-success' : 'badge-muted' }} badge-toggle" title="{{ $link->is_active ? 'Click to hide from header' : 'Click to show on header' }}">
                                {{ $link->is_active ? 'Active' : 'Hidden' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="{{ $link->resolvedUrl() }}" class="table-action-btn" target="_blank" rel="noopener" title="Open link" aria-label="Open link">
                                @include('partials.icons.eye')
                            </a>
                            <a href="{{ route('admin.nav-links.edit', $link) }}" class="table-action-btn" title="Edit" aria-label="Edit">
                                @include('partials.icons.edit')
                            </a>
                            <form action="{{ route('admin.nav-links.destroy', $link) }}" method="POST" class="table-action-form" onsubmit="return confirm(@json('Delete menu item “'.$link->label.'”?'))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="table-action-btn table-action-btn-danger" title="Delete" aria-label="Delete">
                                    @include('partials.icons.trash')
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
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
.badge-toggle {
    border: 0;
    cursor: pointer;
    font: inherit;
    font-size: .75rem;
    font-weight: 600;
}
.badge-toggle:hover {
    filter: brightness(.95);
    outline: 2px solid currentColor;
    outline-offset: 1px;
}
.import-card {
    background: var(--card, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
    padding: 1.25rem 1.5rem;
}
</style>
@endpush
