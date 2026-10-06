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
@php
    use App\Support\OntopcouponTheme as Otc;

    $author = $post->authorProfile();
    $lead = $post->excerpt ?: null;
@endphp

<article class="ot-article">
    <header class="ot-article-hero">
        <div class="ot-container ot-container--article">
            <nav class="ot-crumbs ot-crumbs--caps" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a><span>›</span>
                <a href="{{ route('blog.index') }}">Blog</a><span>›</span>
                <span class="is-current">{{ \Illuminate\Support\Str::limit($post->title, 60) }}</span>
            </nav>
            <div class="ot-article-meta">
                <span class="ot-article-pill">{{ Otc::postCategory($post) }}</span>
                <span>{!! Otc::icon('calendar', 14) !!} {{ $post->published_at?->format('F j, Y') }}</span>
                <span>· {{ $post->readingTime() }} min read</span>
                <span>· By @include('blog.partials.author-link', ['post' => $post])</span>
            </div>
            <h1>{{ $post->title }}</h1>
            @if($lead)
                <p class="ot-article-lead">{{ $lead }}</p>
            @endif
        </div>
    </header>

    <div class="ot-article-body">
        <div class="ot-container ot-container--article">
            @if($post->featuredImageUrl())
                <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" class="ot-article-image">
            @endif
            <div class="ot-prose ot-prose--article article-content">
                {!! $post->renderedContent() !!}
            </div>

            <div class="ot-article-foot">
                @include('partials.social-share', [
                    'url' => route('blog.show', $post->slug),
                    'title' => $post->title,
                    'description' => $post->seoDescription(),
                    'image' => $post->featuredImageUrl(),
                    'label' => 'Share this article',
                ])
                @include('blog.partials.author-box', ['post' => $post])
                <a href="{{ route('blog.index') }}" class="ot-back-link">{!! Otc::icon('arrow-left', 14) !!} BACK TO BLOG</a>
            </div>
        </div>
    </div>
</article>

@if($related->isNotEmpty())
<section class="ot-section ot-section--alt ot-section--bordered">
    <div class="ot-container">
        <p class="ot-more-label">— MORE ARTICLES</p>
        <div class="ot-blog-grid">
            @foreach($related as $item)
                @include('themes.ontopcoupon.partials.blog-card', ['post' => $item, 'index' => $loop->index])
            @endforeach
        </div>
    </div>
</section>
@endif

@include('partials.scroll-coupon-popup')
@endsection
