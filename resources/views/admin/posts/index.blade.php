@extends('layouts.admin')

@section('title', 'Blog Posts')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
    <h1 style="margin:0;">Blog Posts</h1>
    <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">+ New Post</a>
</div>

@include('partials.admin-list-search', [
    'action' => route('admin.posts.index'),
    'value' => $q ?? '',
    'placeholder' => 'Search by title…',
    'clearUrl' => route('admin.posts.index'),
])

<table class="admin-table">
    <thead>
        <tr>
            <th>Image</th>
            <th>Title</th>
            <th>Home</th>
            <th>Status</th>
            <th>Published</th>
            <th>Views</th>
            <th class="table-actions-col">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($posts as $post)
            <tr>
                <td>
                    @if($post->featuredImageUrl())
                        <img src="{{ $post->featuredImageUrl() }}" alt="" class="admin-thumb admin-thumb--wide" loading="lazy">
                    @else
                        <span class="admin-thumb-empty">—</span>
                    @endif
                </td>
                <td>{{ $post->title }}</td>
                <td>
                    <form action="{{ route('admin.posts.toggle-home-pin', $post) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline btn-sm" title="{{ $post->is_pinned_home ? 'Unpin from homepage' : 'Pin to homepage' }}">
                            {{ $post->is_pinned_home ? '📌 Pinned' : 'Pin' }}
                        </button>
                    </form>
                </td>
                <td>{{ $post->is_published ? 'Published' : 'Draft' }}</td>
                <td>{{ $post->published_at?->format('m/d/Y') ?? '—' }}</td>
                <td>{{ number_format($post->view_count) }}</td>
                <td>
                    @include('partials.post-table-actions', [
                        'post' => $post,
                        'editUrl' => route('admin.posts.edit', $post),
                        'destroyUrl' => route('admin.posts.destroy', $post),
                    ])
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">No blog posts yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>
{{ $posts->links() }}

@include('partials.table-actions-assets')
@endsection

@push('styles')
<style>
.btn-sm { padding: .25rem .55rem; font-size: .8125rem; }
</style>
@endpush
