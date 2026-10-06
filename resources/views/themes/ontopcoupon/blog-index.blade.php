@extends('layouts.app')

@section('title', $q ? "Blog Search: {$q}" : 'Blog — Coupon Tips & Savings Guides')
@section('meta_description', 'Expert guides on coupon codes, online deals, and smart shopping tips for U.S. consumers. ' . config('site.tagline'))
@section('canonical', $posts->currentPage() > 1 ? $posts->url($posts->currentPage()) : route('blog.index'))

@push('head_links')
    @include('partials.pagination-seo', ['paginator' => $posts])
@endpush

@section('content')
@php
    use App\Support\OntopcouponTheme as Otc;

    $stats = Otc::stats();
    $total = $posts->total();
@endphp

@include('themes.ontopcoupon.partials.page-hero', [
    'kicker' => 'FROM THE BLOG',
    'title' => Otc::accent('Saving Tips & Guides'),
    'subtitle' => number_format($total).' '.\Illuminate\Support\Str::plural('article', $total).' · shopping tips, coupon guides and sale previews',
    'stats' => [
        [$stats['posts'], 'Articles'],
        [$stats['coupons'], 'Active Coupons'],
        [$stats['stores'], 'Partner Stores'],
        [$stats['categories'], 'Categories'],
    ],
])

<section class="ot-section ot-section--tight">
    <div class="ot-container">
        <form action="{{ route('blog.index') }}" method="GET" class="ot-pill-search ot-pill-search--left" role="search">
            {!! Otc::icon('search', 18) !!}
            <input type="search" name="q" value="{{ $q }}" placeholder="Search articles..." aria-label="Search articles">
        </form>

        @if($posts->isEmpty())
            <div class="ot-empty">
                {!! Otc::icon('book-open', 28) !!}
                <p>No articles found.@if($q) Try a different keyword.@endif</p>
            </div>
        @else
            <div class="ot-blog-grid">
                @foreach($posts as $post)
                    @include('themes.ontopcoupon.partials.blog-card', ['post' => $post, 'index' => $loop->index])
                @endforeach
            </div>
            {{ $posts->links('themes.ontopcoupon.pagination', ['label' => 'Blog pages']) }}
        @endif
    </div>
</section>
@endsection
