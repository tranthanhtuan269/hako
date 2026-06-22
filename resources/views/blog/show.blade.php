@extends('layouts.app')

@section('title', $post->seoTitle())
@section('meta_description', $post->seoDescription())
@section('canonical', route('blog.show', $post->slug))
@section('og_type', 'article')

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
    .blog-coupon-popup {
        position: fixed;
        inset: 0;
        z-index: 1300;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        padding: 1rem;
        pointer-events: none;
        opacity: 0;
        transition: opacity .25s ease;
    }
    .blog-coupon-popup[hidden] {
        display: none !important;
    }
    .blog-coupon-popup.is-visible {
        opacity: 1;
        pointer-events: auto;
    }
    .blog-coupon-popup-backdrop {
        position: absolute;
        inset: 0;
        border: 0;
        background: rgba(15, 23, 42, .45);
        cursor: pointer;
        padding: 0;
    }
    .blog-coupon-popup-panel {
        position: relative;
        z-index: 1;
        width: min(100%, 420px);
        max-height: min(85vh, 560px);
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px 16px 12px 12px;
        box-shadow: 0 20px 48px rgba(15, 23, 42, .22);
        overflow: hidden;
        transform: translateY(1.25rem);
        transition: transform .25s ease;
    }
    .blog-coupon-popup.is-visible .blog-coupon-popup-panel {
        transform: translateY(0);
    }
    .blog-coupon-popup-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
        padding: 1rem 1rem .85rem;
        border-bottom: 1px solid var(--border);
        background: #f8fafc;
    }
    .blog-coupon-popup-heading {
        display: flex;
        align-items: center;
        gap: .65rem;
        min-width: 0;
    }
    .blog-coupon-popup-heading h2 {
        margin: 0;
        font-size: 1rem;
        line-height: 1.25;
        color: var(--secondary);
    }
    .blog-coupon-popup-heading p {
        margin: .2rem 0 0;
        font-size: .8rem;
        color: var(--muted);
    }
    .blog-coupon-popup-close {
        border: 0;
        background: transparent;
        color: #64748b;
        font-size: 1.6rem;
        line-height: 1;
        cursor: pointer;
        padding: 0;
        flex-shrink: 0;
    }
    .blog-coupon-popup-list {
        max-height: min(52vh, 360px);
        overflow-y: auto;
        padding: .85rem;
        display: grid;
        gap: .65rem;
    }
    .blog-coupon-popup-item {
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: .7rem .75rem;
        background: #fafafa;
    }
    .blog-coupon-popup-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .3rem;
        margin-bottom: .35rem;
    }
    .blog-coupon-popup-badge,
    .blog-coupon-popup-type {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .02em;
        padding: .12rem .4rem;
        border-radius: 999px;
        background: #eef2ff;
        color: #3730a3;
    }
    .blog-coupon-popup-type {
        background: #f1f5f9;
        color: #475569;
    }
    .blog-coupon-popup-item h3 {
        margin: 0 0 .5rem;
        font-size: .9rem;
        line-height: 1.35;
        color: #0f172a;
    }
    .blog-coupon-popup-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
    }
    .blog-coupon-popup-actions .btn-sm {
        padding: .32rem .55rem;
        font-size: .75rem;
    }
    .blog-coupon-popup-store-link {
        display: block;
        padding: .8rem 1rem;
        border-top: 1px solid var(--border);
        font-size: .85rem;
        font-weight: 600;
        text-align: center;
        color: var(--primary);
        background: #fff;
    }
    @media (min-width: 640px) {
        .blog-coupon-popup {
            align-items: center;
        }
        .blog-coupon-popup-panel {
            border-radius: 16px;
        }
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

@include('blog.partials.scroll-coupons')
@endsection

@push('scripts')
<script>
(() => {
    const popup = document.getElementById('blog-coupon-popup');
    if (!popup) return;

    const storageKey = popup.dataset.storageKey;
    if (sessionStorage.getItem(storageKey)) return;

    let shown = false;

    const scrollThreshold = () => (
        window.matchMedia('(max-width: 768px)').matches
            ? Math.max(560, Math.round(window.innerHeight * 0.62))
            : 220
    );

    const showPopup = () => {
        if (shown || window.scrollY < scrollThreshold()) return;
        shown = true;
        sessionStorage.setItem(storageKey, '1');
        popup.hidden = false;
        requestAnimationFrame(() => popup.classList.add('is-visible'));
    };

    const closePopup = () => {
        popup.classList.remove('is-visible');
        window.setTimeout(() => {
            popup.hidden = true;
        }, 250);

        const affiliateUrl = popup.dataset.affiliateUrl;
        if (affiliateUrl) {
            window.open(affiliateUrl, '_blank', 'noopener,noreferrer');
        }
    };

    window.addEventListener('scroll', showPopup, { passive: true });

    popup.querySelector('.blog-coupon-popup-close')?.addEventListener('click', closePopup);
    popup.querySelector('.blog-coupon-popup-backdrop')?.addEventListener('click', closePopup);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !popup.hidden) {
            closePopup();
        }
    });
})();
</script>
@endpush
