@php($author = $post->authorProfile())
<div class="author-box">
    <a href="{{ $author->url() }}" class="author-box-avatar" aria-hidden="true">
        @if($author->avatarUrl)
            <img src="{{ $author->avatarUrl }}" alt="{{ $author->name }}">
        @else
            {{ $author->initials() }}
        @endif
    </a>
    <div class="author-box-copy">
        <span class="author-box-label">Written by</span>
        <a href="{{ $author->url() }}" class="author-box-name">{{ $author->name }}</a>
        @if($author->title)
            <p class="author-box-title">{{ $author->title }}</p>
        @endif
        @if($author->bio)
            <p class="author-box-bio">{{ Str::limit($author->bio, 180) }}</p>
        @endif
        <a href="{{ $author->url() }}" class="author-box-more">View author profile →</a>
    </div>
</div>
