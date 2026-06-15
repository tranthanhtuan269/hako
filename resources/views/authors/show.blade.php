@extends('layouts.app')

@section('title', $author->name . ' — Author at ' . config('site.name'))
@section('meta_description', Str::limit($author->bio ?: "Articles by {$author->name} at " . config('site.name'), 160))
@section('canonical', $author->url())

@push('structured_data')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "ProfilePage",
    "mainEntity": {
        "@type": "Person",
        "name": @json($author->name),
        "description": @json($author->bio),
        "jobTitle": @json($author->title),
        "url": @json($author->url())
    }
}
</script>
@include('partials.breadcrumb-schema', ['breadcrumbs' => [
    ['name' => 'Home', 'url' => route('home')],
    ['name' => 'Authors', 'url' => route('authors.index')],
    ['name' => $author->name, 'url' => $author->url()],
]])
@endpush

@section('content')
<section class="author-profile-hero">
    <div class="container">
        <a href="{{ route('authors.index') }}" class="blog-back">← All Authors</a>
        <div class="author-profile-header">
            <div class="author-profile-avatar" aria-hidden="true">
                @if($author->avatarUrl)
                    <img src="{{ $author->avatarUrl }}" alt="{{ $author->name }}">
                @else
                    {{ $author->initials() }}
                @endif
            </div>
            <div>
                <h1>{{ $author->name }}</h1>
                @if($author->title)
                    <p class="author-profile-title">{{ $author->title }}</p>
                @endif
                @if($author->bio)
                    <p class="author-profile-bio">{{ $author->bio }}</p>
                @endif
                <p class="author-profile-meta">{{ $author->publishedPostCount() }} published articles on {{ config('site.name') }}</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title">Articles by {{ $author->name }}</h2>

        @if($posts->isEmpty())
            <p class="blog-empty">No published articles yet.</p>
        @else
            <div class="blog-grid">
                @foreach($posts as $post)
                    @include('blog.partials.card', ['post' => $post])
                @endforeach
            </div>
            <div class="pagination">{{ $posts->links() }}</div>
        @endif
    </div>
</section>
@endsection
