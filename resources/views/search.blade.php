@extends('layouts.app')

@section('title', $q ? "Search: {$q}" : 'Search Coupons')
@section('meta_description', $q ? "Search results for \"{$q}\" — coupon codes and deals on " . config('site.name') . '.' : 'Search coupon codes, stores, and deals.')
@section('meta_robots', 'noindex, follow')
@section('canonical', route('search', array_filter(['q' => $q])))

@push('head_links')
    @include('partials.pagination-seo', ['paginator' => $coupons])
@endpush

@section('content')
<div class="container">
    <div class="page-header">
        <h1>
            Search results
            @if($q)
                : "{{ $q }}"
            @endif
        </h1>
        <p>{{ $coupons->total() }} {{ Str::plural('offer', $coupons->total()) }} found</p>
    </div>
    @if($coupons->isEmpty())
        <p>No matching coupons found. Try a different keyword.</p>
    @else
        <div class="coupon-grid">
            @foreach($coupons as $coupon)
                @include('partials.coupon-card', ['coupon' => $coupon])
            @endforeach
        </div>
        <div class="pagination">{{ $coupons->links() }}</div>
    @endif
</div>
@endsection
