<section class="ot-page-hero">
    <div class="ot-container">
        <p class="ot-page-hero-kicker"><span aria-hidden="true">—</span> {{ $kicker }}</p>
        <h1>{{ $title }}</h1>
        @if(!empty($subtitle))
            <p class="ot-page-hero-sub">{{ $subtitle }}</p>
        @endif
        @if(!empty($stats))
            <div class="ot-stat-strip">
                @foreach($stats as [$value, $label])
                    <div class="ot-stat-strip-item">
                        <strong>{{ is_numeric($value) ? number_format($value) : $value }}<span>+</span></strong>
                        <span>{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
