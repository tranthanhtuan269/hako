@extends('layouts.member')

@section('title', 'My Blog Posts')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
    <h1>My Blog Posts</h1>
    <a href="{{ route('member.posts.create') }}" class="btn btn-primary">+ New Post</a>
</div>
<table class="admin-table">
    <thead>
        <tr>
            <th>Image</th>
            <th>Title</th>
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
                <td>{{ $post->is_published ? 'Published' : 'Draft' }}</td>
                <td>{{ $post->published_at?->format('m/d/Y') ?? '—' }}</td>
                <td>{{ number_format($post->view_count) }}</td>
                <td>
                    @include('partials.post-table-actions', ['post' => $post])
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">No blog posts yet. <a href="{{ route('member.posts.create') }}">Write your first post</a>.</td>
            </tr>
        @endforelse
    </tbody>
</table>
{{ $posts->links() }}

@include('partials.table-actions-assets')
@endsection
