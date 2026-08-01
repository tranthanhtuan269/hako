@php
    /** @var array{id?: string, enabled?: bool, headline?: string, subtitle?: string, cta_label?: string, cta_url?: string, image?: ?string} $slide */
    $slide = $slide ?? [];
    $imageUrl = \App\Support\SiteHeroSlides::imageUrl($slide['image'] ?? null);
@endphp
<div class="hero-slide-row" data-hero-slide-row>
    <input type="hidden" name="slides[{{ $index }}][id]" value="{{ $slide['id'] ?? '' }}">

    <div class="hero-slide-row-head">
        <strong class="hero-slide-row-title">Slide</strong>
        <div class="hero-slide-row-actions">
            <label class="form-check hero-slide-enabled">
                <input type="checkbox" name="slides[{{ $index }}][enabled]" value="1" @checked(!empty($slide['enabled']) || ! array_key_exists('enabled', $slide))>
                Enabled
            </label>
            <button type="button" class="btn btn-outline btn-sm" data-remove-hero-slide>Remove</button>
        </div>
    </div>

    <div class="hero-slide-grid">
        <div class="hero-slide-media-col">
            <div class="form-group">
                <label>Slide image</label>
                @if($imageUrl)
                    <div class="hero-slide-preview">
                        <img src="{{ $imageUrl }}" alt="">
                    </div>
                    <label class="form-check">
                        <input type="checkbox" name="slides[{{ $index }}][remove_image]" value="1">
                        Remove current image
                    </label>
                @endif
                <input type="file" name="slides[{{ $index }}][image_file]" accept="image/*">
                <p class="form-hint">JPG, PNG or WebP. Prefer a square promo image.</p>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label>Or image URL</label>
                <input type="url" name="slides[{{ $index }}][image_url]" value="" placeholder="https://…">
            </div>
        </div>

        <div class="hero-slide-fields-col">
            <div class="form-group">
                <label for="slide-headline-{{ $index }}">Headline</label>
                <input
                    type="text"
                    id="slide-headline-{{ $index }}"
                    name="slides[{{ $index }}][headline]"
                    value="{{ old('slides.'.$index.'.headline', $slide['headline'] ?? 'Exclusive Deals') }}"
                    maxlength="120"
                >
            </div>

            <div class="form-group">
                <label for="slide-subtitle-{{ $index }}">Subtitle</label>
                <textarea
                    id="slide-subtitle-{{ $index }}"
                    name="slides[{{ $index }}][subtitle]"
                    rows="3"
                    maxlength="320"
                >{{ old('slides.'.$index.'.subtitle', $slide['subtitle'] ?? 'Discover amazing savings with our top brands. Limited time offers available now!') }}</textarea>
            </div>

            <div class="hero-slide-cta-grid">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="slide-cta-label-{{ $index }}">Button label</label>
                    <input
                        type="text"
                        id="slide-cta-label-{{ $index }}"
                        name="slides[{{ $index }}][cta_label]"
                        value="{{ old('slides.'.$index.'.cta_label', $slide['cta_label'] ?? 'Shop Now') }}"
                        maxlength="40"
                    >
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="slide-cta-url-{{ $index }}">Button link</label>
                    <input
                        type="url"
                        id="slide-cta-url-{{ $index }}"
                        name="slides[{{ $index }}][cta_url]"
                        value="{{ old('slides.'.$index.'.cta_url', $slide['cta_url'] ?? '') }}"
                        placeholder="https://…"
                    >
                </div>
            </div>
        </div>
    </div>
</div>
