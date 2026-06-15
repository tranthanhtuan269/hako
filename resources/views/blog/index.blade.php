@extends('layouts.app')

@section('title', $q ? "Blog Search: {$q}" : 'Blog — Coupon Tips & Savings Guides')
@section('meta_description', 'Expert guides on coupon codes, online deals, and smart shopping tips for U.S. consumers. ' . config('site.tagline'))
@section('canonical', $posts->currentPage() > 1 ? $posts->url($posts->currentPage()) : route('blog.index'))

@push('head_links')
    @include('partials.pagination-seo', ['paginator' => $posts])
@endpush

@section('content')
<section class="page-hero">
    <div class="container">
        <h1>Savings Blog</h1>
        <p class="page-subtitle">Coupon strategies, deal guides, and shopping tips to help you save more online.</p>
        <p class="page-subtitle"><a href="{{ route('authors.index') }}">Meet our authors →</a></p>
    </div>
</section>

<div class="container page-body">
    <form action="{{ route('blog.index') }}" method="GET" class="blog-search">
        <input type="search" name="q" placeholder="Search articles..." value="{{ $q }}">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    @if($posts->isEmpty())
        <p class="blog-empty">No articles found.@if($q) Try a different keyword.@endif</p>
    @else
        <div class="blog-grid">
            @foreach($posts as $post)
                @include('blog.partials.card', ['post' => $post])
            @endforeach
        </div>
        <div class="pagination">{{ $posts->links() }}</div>
    @endif
</div>
@endsection
