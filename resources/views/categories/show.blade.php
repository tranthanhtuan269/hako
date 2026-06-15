@extends('layouts.app')

@section('title', $category->seoTitle())
@section('meta_description', $category->seoDescription())
@section('canonical', $coupons->currentPage() > 1 ? $coupons->url($coupons->currentPage()) : route('categories.show', $category->slug))

@push('head_links')
    @include('partials.pagination-seo', ['paginator' => $coupons])
@endpush

@push('structured_data')
@include('partials.breadcrumb-schema', ['breadcrumbs' => [
    ['name' => 'Home', 'url' => route('home')],
    ['name' => 'Categories', 'url' => route('categories.index')],
    ['name' => $category->name, 'url' => route('categories.show', $category->slug)],
]])
@endpush

@section('content')
<div class="container">
    <div class="page-header">
        <h1>{{ $category->icon }} {{ $category->name }}</h1>
        @if($category->description)<p>{{ $category->description }}</p>@endif
    </div>
    <div class="coupon-grid">
        @forelse($coupons as $coupon)
            @include('partials.coupon-card', ['coupon' => $coupon])
        @empty
            <p>No offers in this category yet.</p>
        @endforelse
    </div>
    <div class="pagination">{{ $coupons->links() }}</div>
</div>
@endsection
