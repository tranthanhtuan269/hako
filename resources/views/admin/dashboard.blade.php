@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<h1 style="margin-bottom:1.5rem;">Dashboard</h1>
<p style="margin-bottom:1.5rem;display:flex;flex-wrap:wrap;gap:.75rem;">
    <a href="{{ route('member.import-affiliate.create') }}" class="btn btn-primary">Import from Affiliate Link</a>
    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline">Manage Categories</a>
    <a href="{{ route('admin.stores.index') }}" class="btn btn-outline">Manage Stores</a>
    <a href="{{ route('admin.themes.index') }}" class="btn btn-outline">Frontend Theme</a>
    <a href="{{ route('admin.tracking.index') }}" class="btn btn-outline">Tracking Scripts</a>
    @if(config('affiliate.enabled') && $stats['pending_affiliate_payouts'] > 0)
        <a href="{{ route('admin.affiliate.payouts.index') }}" class="btn btn-primary">Affiliate Payouts ({{ $stats['pending_affiliate_payouts'] }} pending)</a>
    @endif
</p>
<div class="stats-grid">
    <div class="stat-card"><strong>{{ $stats['coupons'] }}</strong> Total Coupons</div>
    <div class="stat-card"><strong>{{ $stats['active_coupons'] }}</strong> Active</div>
    <div class="stat-card"><strong>{{ $stats['stores'] }}</strong> Stores</div>
    <div class="stat-card"><strong>{{ $stats['categories'] }}</strong> Categories</div>
    <div class="stat-card"><strong>{{ $stats['published_posts'] }}</strong> Blog Posts</div>
    <div class="stat-card"><strong>{{ number_format($stats['clicks']) }}</strong> Clicks</div>
    @if(config('affiliate.enabled') && $stats['pending_affiliate_payouts'] > 0)
    <div class="stat-card"><strong>{{ $stats['pending_affiliate_payouts'] }}</strong> Pending Payouts</div>
    @endif
</div>
<h2 style="margin-bottom:1rem;">
    @if(($sort ?? 'clicks') === 'latest')
        Latest Coupons
    @elseif(($sort ?? 'clicks') === 'title')
        Coupons by Title
    @else
        Top Coupons by Clicks
    @endif
</h2>
<table class="admin-table">
    <thead>
        <tr>
            @include('partials.table-sort-th', ['column' => 'title', 'label' => 'Title', 'currentSort' => $sort ?? 'clicks', 'currentDir' => $dir ?? 'desc'])
            <th>Store</th>
            <th>Type</th>
            @include('partials.table-sort-th', ['column' => 'clicks', 'label' => 'Clicks', 'currentSort' => $sort ?? 'clicks', 'currentDir' => $dir ?? 'desc'])
        </tr>
    </thead>
    <tbody>
        @foreach($recentCoupons as $c)
        <tr>
            <td><a href="{{ route('admin.coupons.edit', $c) }}">{{ $c->title }}</a></td>
            <td>{{ $c->store?->name }}</td>
            <td>{{ $c->typeLabel() }}</td>
            <td>{{ number_format($c->click_count) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
