@extends('layouts.admin')

@section('title', 'Homepage Slider')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
    <div>
        <h1 style="margin:0 0 .35rem;">Homepage Slider</h1>
        <p class="form-hint" style="margin:0;max-width:42rem;">
            Manage the Exclusive Deals carousel on the homepage (GooglyCodes theme).
            Upload a square/promo image for each slide — it shows centered with the same image as a blurred background.
        </p>
    </div>
    <button type="button" class="btn btn-outline" id="add-hero-slide">+ Add slide</button>
</div>

@if($errors->any())
    <div class="alert alert-error">
        <ul style="margin:0;padding-left:1.1rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.hero-slider.update') }}" method="POST" enctype="multipart/form-data" id="hero-slider-form">
    @csrf
    @method('PUT')

    <div id="hero-slides-list" class="hero-slides-list">
        @php($rows = old('slides', $slides))
        @forelse($rows as $index => $slide)
            @include('admin.hero-slider.partials.slide-row', ['index' => $index, 'slide' => $slide])
        @empty
            @include('admin.hero-slider.partials.slide-row', [
                'index' => 0,
                'slide' => [
                    'id' => '',
                    'enabled' => true,
                    'headline' => 'Exclusive Deals',
                    'subtitle' => 'Discover amazing savings with our top brands. Limited time offers available now!',
                    'cta_label' => 'Shop Now',
                    'cta_url' => url('/coupons'),
                    'image' => null,
                ],
            ])
        @endforelse
    </div>

    <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1rem;">
        <button type="submit" class="btn btn-primary">Save slider</button>
        <a href="{{ route('home') }}" class="btn btn-outline" target="_blank" rel="noopener">Preview homepage →</a>
    </div>
</form>

<template id="hero-slide-row-template">
    @include('admin.hero-slider.partials.slide-row', [
        'index' => '__INDEX__',
        'slide' => [
            'id' => '',
            'enabled' => true,
            'headline' => 'Exclusive Deals',
            'subtitle' => 'Discover amazing savings with our top brands. Limited time offers available now!',
            'cta_label' => 'Shop Now',
            'cta_url' => '',
            'image' => null,
        ],
    ])
</template>
@endsection

@push('styles')
<style>
.hero-slides-list {
    display: grid;
    gap: 1.25rem;
}

.hero-slide-row {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem 1.35rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.hero-slide-row-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.1rem;
    padding-bottom: .85rem;
    border-bottom: 1px solid var(--border);
}

.hero-slide-row-title {
    font-size: 1rem;
}

.hero-slide-row-actions {
    display: flex;
    align-items: center;
    gap: .85rem;
}

.hero-slide-enabled {
    margin: 0;
    white-space: nowrap;
}

.hero-slide-grid {
    display: grid;
    gap: 1.25rem;
}

@media (min-width: 960px) {
    .hero-slide-grid {
        grid-template-columns: 220px minmax(0, 1fr);
        align-items: start;
    }
}

.hero-slide-media-col,
.hero-slide-fields-col {
    min-width: 0;
}

.hero-slide-row .form-group {
    margin-bottom: 1rem;
}

.hero-slide-row .form-group label {
    display: block;
    width: 100%;
    font-weight: 600;
    margin-bottom: .4rem;
}

.hero-slide-row .form-group input[type="text"],
.hero-slide-row .form-group input[type="url"],
.hero-slide-row .form-group input[type="file"],
.hero-slide-row .form-group textarea {
    display: block;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.hero-slide-row .form-group textarea {
    min-height: 5.5rem;
    resize: vertical;
}

.hero-slide-row .form-hint {
    margin: .4rem 0 0;
}

.hero-slide-preview {
    width: 100%;
    max-width: 220px;
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--border);
    background: #0f1430;
    margin: 0 0 .65rem;
}

.hero-slide-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.hero-slide-cta-grid {
    display: grid;
    gap: 1rem;
}

@media (min-width: 720px) {
    .hero-slide-cta-grid {
        grid-template-columns: minmax(140px, 200px) minmax(0, 1fr);
    }
}

.btn-sm {
    padding: .35rem .75rem;
    font-size: .85rem;
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const list = document.getElementById('hero-slides-list');
    const template = document.getElementById('hero-slide-row-template');
    const addBtn = document.getElementById('add-hero-slide');

    if (!list || !template || !addBtn) {
        return;
    }

    function nextIndex() {
        return list.querySelectorAll('[data-hero-slide-row]').length;
    }

    function renumber() {
        list.querySelectorAll('[data-hero-slide-row]').forEach(function (row, index) {
            row.querySelectorAll('[name]').forEach(function (input) {
                input.name = input.name.replace(/slides\[(?:\d+|__INDEX__)\]/, 'slides[' + index + ']');
            });
        });
    }

    addBtn.addEventListener('click', function () {
        const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex()));
        list.insertAdjacentHTML('beforeend', html);
        renumber();
    });

    list.addEventListener('click', function (event) {
        const btn = event.target.closest('[data-remove-hero-slide]');
        if (!btn) {
            return;
        }

        const row = btn.closest('[data-hero-slide-row]');
        if (!row) {
            return;
        }

        if (list.querySelectorAll('[data-hero-slide-row]').length <= 1) {
            alert('Keep at least one slide row (you can disable it).');
            return;
        }

        row.remove();
        renumber();
    });
})();
</script>
@endpush
