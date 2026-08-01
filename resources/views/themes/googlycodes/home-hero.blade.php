@php
    $slides = $heroSlides ?? collect();
    if ($slides->isEmpty()) {
        $slides = collect([[
            'headline' => 'Exclusive Deals',
            'subtitle' => 'Discover amazing savings with our top brands. Limited time offers available now!',
            'cta_url' => route('coupons.index'),
            'cta_label' => 'Shop Now',
            'image' => null,
        ]]);
    }
@endphp

<section class="gc-hero">
    <div class="container">
        <div class="gc-slider" data-gc-slider data-autoplay="5000" aria-roledescription="carousel" aria-label="Exclusive deals">
            <button type="button" class="gc-slider-nav gc-slider-prev" data-gc-slider-prev aria-label="Previous slide">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <button type="button" class="gc-slider-nav gc-slider-next" data-gc-slider-next aria-label="Next slide">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>

            <div class="gc-slider-viewport">
                <div class="gc-slider-track" data-gc-slider-track>
                    @foreach($slides as $index => $slide)
                        <article
                            class="gc-slide{{ $index === 0 ? ' is-active' : '' }}"
                            data-gc-slide
                            aria-hidden="{{ $index === 0 ? 'false' : 'true' }}"
                            aria-label="Slide {{ $index + 1 }} of {{ $slides->count() }}"
                        >
                            <div class="gc-slide-copy">
                                <h1>{{ $slide['headline'] }}</h1>
                                <p>{{ $slide['subtitle'] }}</p>
                                <a href="{{ $slide['cta_url'] }}" class="gc-slide-cta">{{ $slide['cta_label'] }}</a>
                            </div>

                            <div class="gc-slide-media">
                                @if(!empty($slide['image']))
                                    <div
                                        class="gc-slide-media-bg"
                                        style="background-image:url('{{ $slide['image'] }}')"
                                        aria-hidden="true"
                                    ></div>
                                    <div class="gc-slide-media-overlay" aria-hidden="true"></div>
                                    <a href="{{ $slide['cta_url'] }}" class="gc-slide-image-link">
                                        <img
                                            src="{{ $slide['image'] }}"
                                            alt="{{ $slide['headline'] }}"
                                            class="gc-slide-image"
                                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                        >
                                    </a>
                                @else
                                    <div class="gc-slide-media-bg gc-slide-media-bg--tone-0" aria-hidden="true"></div>
                                    <div class="gc-slide-media-overlay" aria-hidden="true"></div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            <div class="gc-slider-dots" data-gc-slider-dots role="tablist" aria-label="Slide indicators">
                @foreach($slides as $index => $slide)
                    <button
                        type="button"
                        class="gc-slider-dot{{ $index === 0 ? ' is-active' : '' }}"
                        data-gc-slider-dot="{{ $index }}"
                        role="tab"
                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                        aria-label="Go to slide {{ $index + 1 }}"
                    ></button>
                @endforeach
            </div>
        </div>
    </div>
</section>
