@php
    $trending = ($trendingStores ?? $stores ?? collect())->values();
    $mid = (int) ceil($trending->count() / 2);
    $rowOne = $trending->slice(0, max(1, $mid))->values();
    $rowTwo = $trending->slice($mid)->values();
    if ($rowTwo->isEmpty()) {
        $rowTwo = $rowOne->reverse()->values();
    }
    // Even copy counts keep translateX(-50%) seamless while filling wide screens.
    $copiesOne = max(2, (int) ceil(10 / max(1, $rowOne->count())));
    $copiesTwo = max(2, (int) ceil(10 / max(1, $rowTwo->count())));
    if ($copiesOne % 2 !== 0) {
        $copiesOne++;
    }
    if ($copiesTwo % 2 !== 0) {
        $copiesTwo++;
    }
@endphp

@if($trending->isNotEmpty())
<section class="gc-trending section">
    <div class="container">
        <div class="gc-trending-head">
            <div class="gc-trending-title">
                <span class="gc-trending-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                </span>
                <h2>Trending Stores</h2>
            </div>
            <a href="{{ route('stores.index') }}" class="gc-trending-all">View All <span aria-hidden="true">→</span></a>
        </div>

        <div class="gc-trending-rows">
            <div class="gc-trending-row" data-gc-marquee data-direction="left" data-duration="42">
                <div class="gc-trending-track">
                    @for($c = 0; $c < $copiesOne; $c++)
                        @foreach($rowOne as $store)
                            <a href="{{ route('stores.show', $store->slug) }}" class="gc-trend-card">
                                <span class="gc-trend-logo">
                                    @include('partials.store-logo', ['store' => $store, 'size' => 'md', 'showVerified' => false, 'linked' => false])
                                </span>
                                <strong>{{ $store->name }}</strong>
                            </a>
                        @endforeach
                    @endfor
                </div>
            </div>

            <div class="gc-trending-row" data-gc-marquee data-direction="right" data-duration="55">
                <div class="gc-trending-track">
                    @for($c = 0; $c < $copiesTwo; $c++)
                        @foreach($rowTwo as $store)
                            <a href="{{ route('stores.show', $store->slug) }}" class="gc-trend-card">
                                <span class="gc-trend-logo">
                                    @include('partials.store-logo', ['store' => $store, 'size' => 'md', 'showVerified' => false, 'linked' => false])
                                </span>
                                <strong>{{ $store->name }}</strong>
                            </a>
                        @endforeach
                    @endfor
                </div>
            </div>
        </div>
    </div>
</section>
@endif
