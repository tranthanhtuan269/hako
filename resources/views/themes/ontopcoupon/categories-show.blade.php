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

@php
    use App\Support\OntopcouponTheme as Otc;

    $isAiTool = ($category->slug === 'ai-tool');
    $allCategories = \App\Models\Category::active()->orderBy('sort_order')->get();
    $style = Otc::categoryStyle($category, max(0, $allCategories->search(fn ($c) => $c->id === $category->id)));
    $type = $type ?? null;
    $total = $coupons->total();
    $q = $q ?? '';
@endphp

@if($isAiTool)
    @push('styles')
    <style>
        .ai-tools-page {
            max-width: 1040px;
            margin: 0 auto;
            padding: 2.5rem 1.25rem 4.5rem;
        }
        .ai-tools-search-box {
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 2px solid #fed7aa;
            border-radius: 9999px;
            padding: 12px 24px;
            box-shadow: 0 4px 14px rgba(249, 115, 22, 0.08);
            transition: all 0.2s ease;
            margin-bottom: 1.5rem;
        }
        .ai-tools-search-box:focus-within {
            border-color: #f97316;
            box-shadow: 0 6px 20px rgba(249, 115, 22, 0.16);
        }
        .ai-tools-search-box svg {
            color: #f97316;
            margin-right: 14px;
            flex-shrink: 0;
        }
        .ai-tools-search-box input {
            border: none;
            outline: none;
            font-size: 15px;
            width: 100%;
            background: transparent;
            color: #1f2937;
        }
        .ai-tools-search-box input::placeholder {
            color: #9ca3af;
        }
        .ai-tools-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding: 0 6px;
        }
        .ai-tools-count {
            font-size: 14px;
            font-weight: 600;
            color: #4b5563;
        }
        .ai-tool-card {
            background: #ffffff;
            border: 1px solid #fed7aa;
            border-radius: 16px;
            padding: 16px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        .ai-tool-card:hover {
            border-color: #f97316;
            box-shadow: 0 8px 24px rgba(249, 115, 22, 0.14);
            transform: translateY(-2px);
        }
        .ai-tool-card-left {
            display: flex;
            align-items: center;
            gap: 18px;
            flex: 1;
            min-width: 0;
        }
        .ai-tool-card-logo {
            width: 100px;
            height: 60px;
            background: #0f172a;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            flex-shrink: 0;
            overflow: hidden;
        }
        .ai-tool-card-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .ai-tool-logo-fallback {
            color: #f97316;
            font-weight: 700;
            font-size: 20px;
            text-transform: uppercase;
        }
        .ai-tool-card-info {
            flex: 1;
            min-width: 0;
        }
        .ai-tool-card-head {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
            flex-wrap: wrap;
        }
        .ai-tool-card-title {
            font-size: 1.05rem !important;
            font-weight: 700 !important;
            color: #9a3412 !important;
            margin: 0 !important;
            text-decoration: none !important;
            line-height: 1.3 !important;
        }
        .ai-tool-card:hover .ai-tool-card-title {
            color: #ea580c !important;
        }
        .ai-tool-card-badge {
            background: #ea580c;
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 9999px;
            line-height: 1.3;
            white-space: nowrap;
        }
        .ai-tool-card-desc {
            font-size: 13px;
            color: #6b7280;
            margin: 0;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .ai-tool-card-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        .ai-tool-discount-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff7ed;
            border-radius: 9999px;
            padding: 6px 12px;
            border: 1px solid rgba(254, 215, 170, 0.6);
        }
        .ai-tool-discount-icon {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #ea580c;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        .ai-tool-discount-text {
            font-size: 13px;
            font-weight: 600;
            color: #9a3412;
            white-space: nowrap;
        }
        .ai-tool-share-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .ai-tool-share-btn:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #1e293b;
        }
        .ai-tool-cta-btn {
            background: linear-gradient(90deg, #ec4899 0%, #f97316 100%);
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            padding: 9px 20px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(236, 72, 153, 0.25);
        }
        .ai-tool-card:hover .ai-tool-cta-btn {
            opacity: 0.95;
            box-shadow: 0 4px 14px rgba(236, 72, 153, 0.35);
        }
        @media (max-width: 768px) {
            .ai-tool-card {
                flex-direction: column;
                align-items: flex-start;
                padding: 14px 16px;
            }
            .ai-tool-card-left {
                width: 100%;
            }
            .ai-tool-card-right {
                width: 100%;
                justify-content: space-between;
                margin-top: 8px;
                padding-top: 10px;
                border-top: 1px dashed #fed7aa;
            }
        }
    </style>
    @endpush
@endif

@section('content')
<section class="ot-detail-hero">
    <div class="ot-container ot-container--wide">
        <nav class="ot-crumbs" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><span>/</span>
            <a href="{{ route('categories.index') }}">Categories</a><span>/</span>
            <span class="is-current">{{ $category->name }}</span>
        </nav>
        <div class="ot-detail-hero-brand">
            <span class="ot-detail-hero-icon">
                @if($style['icon'])
                    {!! Otc::icon($style['icon'], 30) !!}
                @else
                    @include('partials.category-icon', ['category' => $category, 'size' => 'lg'])
                @endif
            </span>
            <div>
                <h1>{{ $category->name }}</h1>
                @if($category->description)
                    <p>{{ $category->description }}</p>
                @endif
            </div>
        </div>
        <div class="ot-detail-stats">
            <div><strong>{{ number_format($stores->count()) }}</strong><span>{{ $isAiTool ? 'AI TOOLS' : 'PARTNER STORES' }}</span></div>
            <div><strong>{{ number_format($total) }}</strong><span>ACTIVE OFFERS</span></div>
            <div><strong>{{ Otc::categoryTopPercent($category) }}%</strong><span>TOP DISCOUNT</span></div>
        </div>
    </div>
</section>

@if($isAiTool)
    {{-- Dedicated AI Tool Layout without left sidebar --}}
    <section class="ai-tools-page">
        <form action="{{ route('categories.show', $category->slug) }}" method="GET" class="ai-tools-search-form" onsubmit="return true;">
            <div class="ai-tools-search-box">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="search" name="q" id="aiToolSearchInput" value="{{ $q }}" placeholder="Search AI tools, platforms, or promo codes..." autocomplete="off">
            </div>
        </form>

        <div class="ai-tools-meta">
            <span class="ai-tools-count" id="aiToolCountText">
                Showing <strong>{{ $stores->count() }}</strong> {{ \Illuminate\Support\Str::plural('AI Tool', $stores->count()) }}
            </span>
        </div>

        <div class="ai-tools-list" id="aiToolsList">
            @forelse($stores as $store)
                @php
                    $dealCount = $store->coupons->count();
                    $topOffer = $store->bestOfferLabel() ?: 'Special Deals';
                    $storeUrl = route('stores.show', $store->slug);
                    
                    // Filter out figure/caption markup for a clean excerpt
                    $rawDesc = preg_replace('/<figure\b[^>]*>.*?<\/figure>/is', '', $store->description ?? '');
                    $cleanDesc = trim(strip_tags($rawDesc));
                    if ($cleanDesc === '') {
                        $cleanDesc = 'Explore official deals, promo codes, and verified discounts for ' . $store->name . '.';
                    }
                @endphp
                <div class="ai-tool-card" data-name="{{ strtolower($store->name) }}" data-desc="{{ strtolower($cleanDesc) }}" onclick="window.location.href='{{ $storeUrl }}'">
                    <div class="ai-tool-card-left">
                        <div class="ai-tool-card-logo">
                            @if($store->logoUrl())
                                <img src="{{ $store->logoUrl() }}" alt="{{ $store->name }}" loading="lazy">
                            @else
                                <span class="ai-tool-logo-fallback">{{ mb_substr($store->name, 0, 2) }}</span>
                            @endif
                        </div>
                        <div class="ai-tool-card-info">
                            <div class="ai-tool-card-head">
                                <a href="{{ $storeUrl }}" class="ai-tool-card-title">{{ $store->name }}</a>
                                @if($dealCount > 0)
                                    <span class="ai-tool-card-badge">{{ $dealCount }} {{ \Illuminate\Support\Str::plural('deal', $dealCount) }}</span>
                                @endif
                            </div>
                            <p class="ai-tool-card-desc">{{ Str::limit($cleanDesc, 140) }}</p>
                        </div>
                    </div>

                    <div class="ai-tool-card-right" onclick="event.stopPropagation();">
                        @if($topOffer)
                            <div class="ai-tool-discount-tag">
                                <span class="ai-tool-discount-icon">%</span>
                                <span class="ai-tool-discount-text">{{ $topOffer }}</span>
                            </div>
                        @endif

                        <button type="button" class="ai-tool-share-btn" title="Share {{ $store->name }}" onclick="copyStoreLink(event, '{{ $storeUrl }}', '{{ addslashes($store->name) }}')">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="18" cy="5" r="3"></circle>
                                <circle cx="6" cy="12" r="3"></circle>
                                <circle cx="18" cy="19" r="3"></circle>
                                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                            </svg>
                        </button>

                        <a href="{{ $storeUrl }}" class="ai-tool-cta-btn">
                            View Coupons &rarr;
                        </a>
                    </div>
                </div>
            @empty
                <div class="ot-empty ot-empty--dashed">
                    <p>No AI tools found{{ $q !== '' ? ' matching “'.$q.'”' : '' }}.</p>
                    @if($q !== '')
                        <a href="{{ route('categories.show', $category->slug) }}" class="ot-btn-solid ot-btn-sm">Clear search</a>
                    @endif
                </div>
            @endforelse
        </div>
    </section>

    @push('scripts')
    <script>
        function copyStoreLink(e, url, name) {
            e.preventDefault();
            e.stopPropagation();
            if (navigator.share) {
                navigator.share({ title: name, url: url }).catch(function() {});
                return;
            }
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    alert('Copied link for ' + name + '!');
                }).catch(function() {
                    prompt('Copy link:', url);
                });
            } else {
                prompt('Copy link:', url);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            var searchInput = document.getElementById('aiToolSearchInput');
            var list = document.getElementById('aiToolsList');
            var countText = document.getElementById('aiToolCountText');
            if (!searchInput || !list) return;

            searchInput.addEventListener('input', function() {
                var val = this.value.trim().toLowerCase();
                var cards = list.querySelectorAll('.ai-tool-card');
                var visible = 0;
                cards.forEach(function(card) {
                    var name = card.getAttribute('data-name') || '';
                    var desc = card.getAttribute('data-desc') || '';
                    if (val === '' || name.indexOf(val) !== -1 || desc.indexOf(val) !== -1) {
                        card.style.display = 'flex';
                        visible++;
                    } else {
                        card.style.display = 'none';
                    }
                });
                if (countText) {
                    countText.innerHTML = 'Showing <strong>' + visible + '</strong> AI ' + (visible === 1 ? 'Tool' : 'Tools');
                }
            });
        });
    </script>
    @endpush
@else
    {{-- Standard Category Layout with left sidebar --}}
    <section class="ot-detail-body">
        <div class="ot-container ot-container--wide ot-detail-layout ot-detail-layout--category">
            <aside class="ot-side-nav">
                <div class="ot-panel">
                    <p class="ot-side-nav-title">Categories</p>
                    <nav>
                        @foreach($allCategories as $index => $item)
                            @php $itemStyle = Otc::categoryStyle($item, $index); @endphp
                            <a href="{{ route('categories.show', $item->slug) }}" @class(['is-active' => $item->id === $category->id])>
                                <span class="ot-side-nav-icon">
                                    @if($itemStyle['icon'])
                                        {!! Otc::icon($itemStyle['icon'], 16) !!}
                                    @else
                                        @include('partials.category-icon', ['category' => $item, 'size' => 'sm'])
                                    @endif
                                </span>
                                <span>{{ $item->name }}</span>
                                @if($item->id === $category->id)
                                    {!! Otc::icon('arrow-right', 14, 'ot-side-nav-arrow') !!}
                                @endif
                            </a>
                        @endforeach
                    </nav>
                </div>
            </aside>

            <div class="ot-detail-main">
                <div class="ot-chip-scroll">
                    @foreach($allCategories as $index => $item)
                        @php $itemStyle = Otc::categoryStyle($item, $index); @endphp
                        <a href="{{ route('categories.show', $item->slug) }}" @class(['ot-chip', 'is-active' => $item->id === $category->id])>
                            @if($itemStyle['icon'])
                                {!! Otc::icon($itemStyle['icon'], 14) !!}
                            @endif
                            {{ $item->name }}
                        </a>
                    @endforeach
                </div>

                @php
                    $filters = [
                        [null, 'All'],
                        ['coupon', 'Codes'],
                        ['discount', 'Deals'],
                    ];
                @endphp
                <div class="ot-filter-pills">
                    @foreach($filters as [$value, $label])
                        <a href="{{ route('categories.show', array_filter(['slug' => $category->slug, 'type' => $value])) }}" @class(['ot-filter-pill', 'is-active' => $type === $value])>{{ $label }}</a>
                    @endforeach
                </div>
                <p class="ot-result-count">{{ number_format($total) }} {{ \Illuminate\Support\Str::plural('offer', $total) }}</p>

                @if($stores->isNotEmpty())
                    <div class="ot-cat-panel-head">
                        <h2>{{ $category->name }} stores</h2>
                    </div>
                    <div class="ot-cat-stores ot-cat-stores--page">
                        @foreach($stores as $store)
                            @php $storeOffer = $store->bestOfferLabel(); @endphp
                            <a href="{{ route('stores.show', $store->slug) }}" class="ot-cat-store">
                                @include('themes.ontopcoupon.partials.logo', ['store' => $store, 'class' => 'ot-logo--sm'])
                                <span>
                                    <strong>{{ $store->name }}</strong>
                                    <small>{{ $storeOffer ?: 'View deals' }}</small>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if($coupons->isEmpty())
                    <div class="ot-empty ot-empty--dashed">
                        @if($style['icon'])
                            {!! Otc::icon($style['icon'], 32) !!}
                        @endif
                        <p>No offers yet in this category</p>
                        <a href="{{ route('coupons.index') }}" class="ot-btn-solid ot-btn-sm">Browse All Coupons {!! Otc::icon('arrow-right', 14) !!}</a>
                    </div>
                @else
                    <div class="ot-coupon-list ot-coupon-list--compact">
                        @foreach($coupons as $coupon)
                            @include('themes.ontopcoupon.coupon-row', ['coupon' => $coupon, 'variant' => 'store'])
                        @endforeach
                    </div>
                    {{ $coupons->links('themes.ontopcoupon.pagination', ['label' => 'Offer pages']) }}
                @endif
            </div>
        </div>
    </section>
@endif
@endsection
