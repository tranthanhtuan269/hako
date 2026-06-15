@php
    $publicUrl = route('blog.show', $post->slug);
    $editUrl = $editUrl ?? route('member.posts.edit', $post);
    $destroyUrl = $destroyUrl ?? route('member.posts.destroy', $post);
    $deleteConfirm = $deleteConfirm ?? 'Delete this post?';
    $canView = $post->is_published;
@endphp
<div class="table-actions">
    @if($canView)
        <a href="{{ $publicUrl }}" class="table-action-btn" target="_blank" rel="noopener" title="View public page" aria-label="View public page">
            @include('partials.icons.eye')
        </a>
    @else
        <span class="table-action-btn is-disabled" title="Post is not published" aria-label="Post is not published">
            @include('partials.icons.eye')
        </span>
    @endif
    @if($canView)
        <button
            type="button"
            class="table-action-btn js-copy-link"
            data-copy-url="{{ $publicUrl }}"
            data-copy-title="Copy post link"
            title="Copy post link"
            aria-label="Copy post link"
        >
            @include('partials.icons.copy')
        </button>
    @endif
    <a href="{{ $editUrl }}" class="table-action-btn" title="Edit post" aria-label="Edit post">
        @include('partials.icons.edit')
    </a>
    <form action="{{ $destroyUrl }}" method="POST" class="table-action-form" onsubmit="return confirm(@json($deleteConfirm))">
        @csrf
        @method('DELETE')
        <button type="submit" class="table-action-btn table-action-btn-danger" title="Delete post" aria-label="Delete post">
            @include('partials.icons.trash')
        </button>
    </form>
</div>
