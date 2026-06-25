@extends('layouts.app')

@section('title', $q ? "Search: {$q}" : 'Search')
@section('meta_description', $q ? "Search results for \"{$q}\" — coupons, stores, and blog articles on " . config('site.name') . '.' : 'Search coupon codes, stores, blog articles, and deals.')
@section('meta_robots', 'noindex, follow')
@section('canonical', route('search', array_filter(['q' => $q])))

@push('head_links')
    @include('partials.pagination-seo', ['paginator' => $coupons])
@endpush

@section('content')
@php($resultCount = $coupons->total() + $posts->count())
<div class="container">
    <div class="page-header">
        <h1>
            Search results
            @if($q)
                : "{{ $q }}"
            @endif
        </h1>
        @if($q)
            <p>{{ $resultCount }} {{ $resultCount === 1 ? 'result' : 'results' }} found</p>
        @endif
    </div>

    @if($posts->isNotEmpty())
        <section class="search-section">
            <h2 class="section-title">Blog articles <span>{{ $posts->count() }}</span></h2>
            <div class="blog-grid">
                @foreach($posts as $post)
                    @include('blog.partials.card', ['post' => $post])
                @endforeach
            </div>
        </section>
    @endif

    @if($coupons->isNotEmpty())
        <section class="search-section">
            <h2 class="section-title">Coupons &amp; deals <span>{{ $coupons->total() }}</span></h2>
            <div class="coupon-grid">
                @foreach($coupons as $coupon)
                    @include('partials.coupon-card', ['coupon' => $coupon])
                @endforeach
            </div>
            <div class="pagination">{{ $coupons->links() }}</div>
        </section>
    @endif

    @if($q && $posts->isEmpty() && $coupons->isEmpty())
        <p>No matching blog articles or coupons found. Try a different keyword.</p>
    @elseif(! $q)
        <p>Enter a keyword to search blog articles, coupon codes, and stores.</p>
    @endif
</div>
@endsection

@push('styles')
<style>
.search-section + .search-section {
    margin-top: 2.5rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border);
}
.search-section .section-title span {
    font-size: .9rem;
    font-weight: 600;
    color: var(--muted);
}
</style>
@endpush
