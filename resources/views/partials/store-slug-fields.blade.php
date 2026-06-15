@php
    $slugValue = old('slug', $store->slug ?? '');
    $nameValue = old('name', $store->name ?? '');
    $storesPath = rtrim(config('site.url'), '/') . '/stores/';
@endphp
<div class="form-group">
    <label for="store-name">Name *</label>
    <input
        type="text"
        id="store-name"
        name="name"
        value="{{ $nameValue }}"
        required
        autocomplete="organization"
        data-store-name-input
    >
</div>
<div class="form-group">
    <label for="store-slug">URL slug</label>
    <div class="slug-field-row">
        <span class="slug-field-prefix">{{ $storesPath }}</span>
        <input
            type="text"
            id="store-slug"
            name="slug"
            value="{{ $slugValue }}"
            pattern="[a-z0-9]+(-[a-z0-9]+)*"
            maxlength="255"
            placeholder="auto-from-name"
            data-store-slug-input
            @if($store->exists && $slugValue) data-initial-slug="{{ $slugValue }}" @endif
        >
    </div>
    <p class="form-hint">Generated automatically when you change the name. You can edit this for a custom store URL.</p>
</div>
@once
    @push('styles')
        <style>
            .slug-field-row {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: .35rem;
                max-width: 100%;
            }
            .slug-field-prefix {
                font-size: .9rem;
                color: #64748b;
                word-break: break-all;
            }
            .slug-field-row input {
                flex: 1;
                min-width: 12rem;
            }
        </style>
    @endpush
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var nameInput = document.querySelector('[data-store-name-input]');
                var slugInput = document.querySelector('[data-store-slug-input]');
                if (!nameInput || !slugInput) return;

                function slugify(text) {
                    return text
                        .toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '')
                        .replace(/-{2,}/g, '-');
                }

                var slugManual = false;
                var initialSlug = slugInput.getAttribute('data-initial-slug') || slugInput.value;
                var initialName = nameInput.value;

                if (initialSlug && slugify(initialName) !== initialSlug) {
                    slugManual = true;
                }

                slugInput.addEventListener('input', function () {
                    slugManual = true;
                });

                nameInput.addEventListener('input', function () {
                    if (slugManual) return;
                    slugInput.value = slugify(nameInput.value);
                });

                nameInput.addEventListener('blur', function () {
                    if (!slugInput.value.trim() && nameInput.value.trim()) {
                        slugInput.value = slugify(nameInput.value);
                    }
                });
            });
        </script>
    @endpush
@endonce
