@extends('layouts.app')

@section('title', 'Our Authors — ' . config('site.name'))
@section('meta_description', 'Meet the writers behind savings guides, coupon tips, and deal reviews at ' . config('site.name') . '.')
@section('canonical', route('authors.index'))

@section('content')
<section class="page-hero">
    <div class="container">
        <h1>Our Authors</h1>
        <p class="page-subtitle">Meet the editorial team and writers behind our coupon guides and savings articles.</p>
    </div>
</section>

<div class="container page-body">
    <div class="author-grid">
        <article class="author-card">
            <a href="{{ $defaultAuthor->url() }}" class="author-card-avatar" aria-hidden="true">
                @if($defaultAuthor->avatarUrl)
                    <img src="{{ $defaultAuthor->avatarUrl }}" alt="{{ $defaultAuthor->name }}">
                @else
                    {{ $defaultAuthor->initials() }}
                @endif
            </a>
            <div class="author-card-body">
                <h2><a href="{{ $defaultAuthor->url() }}">{{ $defaultAuthor->name }}</a></h2>
                @if($defaultAuthor->title)
                    <p class="author-card-title">{{ $defaultAuthor->title }}</p>
                @endif
                <p class="author-card-bio">{{ Str::limit($defaultAuthor->bio, 180) }}</p>
                <p class="author-card-meta">{{ $defaultAuthor->publishedPostCount() }} articles</p>
                <a href="{{ $defaultAuthor->url() }}" class="read-more">View profile →</a>
            </div>
        </article>

        @foreach($authors as $author)
            <article class="author-card">
                <a href="{{ $author->url() }}" class="author-card-avatar" aria-hidden="true">
                    @if($author->avatarUrl)
                        <img src="{{ $author->avatarUrl }}" alt="{{ $author->name }}">
                    @else
                        {{ $author->initials() }}
                    @endif
                </a>
                <div class="author-card-body">
                    <h2><a href="{{ $author->url() }}">{{ $author->name }}</a></h2>
                    @if($author->title)
                        <p class="author-card-title">{{ $author->title }}</p>
                    @endif
                    <p class="author-card-bio">{{ Str::limit($author->bio, 180) }}</p>
                    <p class="author-card-meta">{{ $author->publishedPostCount() }} articles</p>
                    <a href="{{ $author->url() }}" class="read-more">View profile →</a>
                </div>
            </article>
        @endforeach
    </div>
</div>
@endsection
