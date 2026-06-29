@extends('layouts.app')

@section('title', $post->seoTitle())
@section('meta_description', $post->seoDescription())
@section('og_title', $post->ogShareTitle())
@section('og_description', $post->ogShareDescription())
@section('canonical', route('blog.show', $post->slug))
@section('og_url', route('blog.show', $post->slug))
@section('og_type', 'article')
@if($post->ogImageUrl())
@section('og_image', $post->ogImageUrl())
@endif
@section('og_image_alt', $post->title)

@push('styles')
<style>
    .article-content h2 { font-size: 1.35rem; margin: 2rem 0 .75rem; color: var(--secondary); }
    .article-content h3 { font-size: 1.1rem; margin: 1.5rem 0 .5rem; }
    .article-content p { margin-bottom: 1rem; line-height: 1.75; color: #374151; }
    .article-content ul, .article-content ol { margin: 0 0 1rem 1.25rem; color: #374151; }
    .article-content li { margin-bottom: .4rem; }
    .article-content a { color: var(--primary); }
    .article-content table.comparison-table {
        width: 100%;
        border-collapse: collapse;
        margin: 1rem 0 1.5rem;
        font-size: .95rem;
    }
    .article-content table.comparison-table th,
    .article-content table.comparison-table td {
        border: 1px solid var(--border);
        padding: .65rem .75rem;
        text-align: left;
        vertical-align: top;
    }
    .article-content table.comparison-table th {
        background: #f8fafc;
        font-weight: 600;
    }
</style>
@endpush

@push('structured_data')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BlogPosting",
    "headline": @json($post->title),
    "description": @json($post->seoDescription()),
    "datePublished": @json($post->published_at?->toIso8601String()),
    "dateModified": @json($post->updated_at->toIso8601String()),
    "author": {
        "@type": "Person",
        "name": @json($post->authorProfile()->name),
        "url": @json($post->authorProfile()->url())
    },
    "publisher": {
        "@type": "Organization",
        "name": @json(config('site.name')),
        "url": @json(config('site.url'))
    },
    "mainEntityOfPage": @json(route('blog.show', $post->slug))
}
</script>
@include('partials.breadcrumb-schema', ['breadcrumbs' => [
    ['name' => 'Home', 'url' => route('home')],
    ['name' => 'Blog', 'url' => route('blog.index')],
    ['name' => $post->title, 'url' => route('blog.show', $post->slug)],
]])
@endpush

@section('content')
<article class="blog-article">
    <header class="blog-article-header">
        <div class="container">
            <a href="{{ route('blog.index') }}" class="blog-back">← Back to Blog</a>
            <h1>{{ $post->title }}</h1>
            <div class="blog-article-meta">
                @include('blog.partials.author-link', ['post' => $post])
                <span>·</span>
                <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('F j, Y') }}</time>
                <span>·</span>
                <span>{{ $post->readingTime() }} min read</span>
            </div>
        </div>
    </header>

    @if($post->featuredImageUrl())
        <div class="container">
            <div class="blog-featured-img-wrap">
                <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" class="blog-featured-img">
            </div>
        </div>
    @endif

    <div class="container">
        <div class="blog-article-layout">
            <div class="article-content legal-content">
                {!! $post->content !!}
            </div>
            <aside class="blog-sidebar">
                @include('partials.social-share', [
                    'url' => route('blog.show', $post->slug),
                    'title' => $post->title,
                    'description' => $post->seoDescription(),
                    'image' => $post->featuredImageUrl(),
                    'label' => 'Share this article',
                ])
                @include('blog.partials.author-box', ['post' => $post])
                <div class="sidebar-box">
                    <h3>Save More Today</h3>
                    <p>Browse verified coupon codes at {{ config('site.name') }}.</p>
                    <a href="{{ route('coupons.index') }}" class="btn btn-primary" style="width:100%;text-align:center;">View Coupons</a>
                </div>
            </aside>
        </div>
    </div>
</article>

@if($related->isNotEmpty())
<section class="section">
    <div class="container">
        <h2 class="section-title">Related Articles</h2>
        <div class="blog-grid">
            @foreach($related as $item)
                @include('blog.partials.card', ['post' => $item])
            @endforeach
        </div>
    </div>
</section>
@endif

@include('partials.scroll-coupon-popup')
@endsection
