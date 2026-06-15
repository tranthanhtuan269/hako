@extends('layouts.app')

@section('title', 'Coupon Categories')
@section('meta_description', 'Browse coupon categories — fashion, electronics, travel, and more. Find deals by topic on ' . config('site.name') . '.')
@section('canonical', route('categories.index'))

@section('content')
<div class="container">
    <div class="page-header"><h1>Deal Categories</h1></div>
    <div class="category-grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));">
        @foreach($categories as $category)
            <a href="{{ route('categories.show', $category->slug) }}" class="category-chip">
                <span class="icon">{{ $category->icon ?? '🏷️' }}</span>
                <strong>{{ $category->name }}</strong>
                <small>{{ $category->coupons_count }} {{ Str::plural('offer', $category->coupons_count) }}</small>
            </a>
        @endforeach
    </div>
</div>
@endsection
