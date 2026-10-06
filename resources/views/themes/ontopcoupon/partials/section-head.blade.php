<div class="ot-section-head">
    <div>
        @if(!empty($kicker))
            <p class="ot-kicker">{{ $kicker }}</p>
        @endif
        <h2>{{ \App\Support\OntopcouponTheme::accent($title, $accentWords ?? 1) }}</h2>
    </div>
    @if(!empty($link) && !empty($url))
        <a href="{{ $url }}" @class(['ot-section-link', 'ot-section-link--caps' => $caps ?? false])>{{ $link }} <span aria-hidden="true">→</span></a>
    @endif
</div>
