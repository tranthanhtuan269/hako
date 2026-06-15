@php($author = $post->authorProfile())
<a href="{{ $author->url() }}" class="author-link">
    <span class="author-avatar" aria-hidden="true">
        @if($author->avatarUrl)
            <img src="{{ $author->avatarUrl }}" alt="">
        @else
            {{ $author->initials() }}
        @endif
    </span>
    <span>{{ $author->name }}</span>
</a>
