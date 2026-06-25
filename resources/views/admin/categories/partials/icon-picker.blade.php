@php
    use App\Support\PublicImage;

    $selectedIcon = old('icon', $category->icon ?? '');
    $selectedIsImage = PublicImage::isStored($selectedIcon) && PublicImage::url($selectedIcon);
@endphp
<div class="form-group category-icon-field">
    <label>Category icon</label>
    <input type="hidden" name="icon" id="icon" value="{{ $selectedIsImage ? $selectedIcon : ($selectedIcon && !$selectedIsImage ? $selectedIcon : '') }}">
    <div class="category-icon-preview" id="category-icon-preview" @if(!$selectedIcon) hidden @endif>
        <span class="category-icon-preview-emoji" id="category-icon-preview-emoji" @if($selectedIsImage) hidden @endif>{{ $selectedIsImage ? '' : $selectedIcon }}</span>
        <img src="{{ $selectedIsImage ?: '' }}" alt="" class="category-icon-preview-img" id="category-icon-preview-img" @if(!$selectedIsImage) hidden @endif>
        <span class="form-hint" id="category-icon-preview-label">Selected icon</span>
        <button type="button" class="btn btn-outline btn-sm" id="category-icon-clear">Clear</button>
    </div>

    <div class="category-icon-upload">
        <label for="icon_file" class="category-icon-upload-label">Upload custom icon</label>
        <input type="file" id="icon_file" name="icon_file" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml,.ico">
        <p class="form-hint">PNG, JPG, WebP, GIF, SVG or ICO. Max 512 KB. Square images work best.</p>
        @error('icon_file')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <p class="form-hint" style="margin:.75rem 0;">Or choose a preset icon below. Optional — used on homepage and category chips.</p>
    <div class="category-icon-grid" role="listbox" aria-label="Choose category icon">
        @foreach($iconOptions as $option)
            <button
                type="button"
                class="category-icon-option @if(!$selectedIsImage && $selectedIcon === $option['value']) is-selected @endif"
                data-icon="{{ $option['value'] }}"
                title="{{ $option['label'] }}"
                aria-label="{{ $option['label'] }}"
                role="option"
                aria-selected="{{ !$selectedIsImage && $selectedIcon === $option['value'] ? 'true' : 'false' }}"
            >
                <span class="category-icon-option-emoji">{{ $option['value'] }}</span>
                <span class="category-icon-option-label">{{ $option['label'] }}</span>
            </button>
        @endforeach
    </div>
    @error('icon')<p class="form-error">{{ $message }}</p>@enderror
</div>

@push('styles')
<style>
.category-icon-preview {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: .75rem;
    padding: .65rem .85rem;
    background: #f8fafc;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
}
.category-icon-preview-emoji {
    font-size: 2rem;
    line-height: 1;
}
.category-icon-preview-img {
    width: 2.5rem;
    height: 2.5rem;
    object-fit: contain;
    border-radius: 6px;
    background: #fff;
}
.category-icon-upload {
    margin-bottom: .75rem;
    padding: .85rem;
    border: 1px dashed var(--border, #e5e7eb);
    border-radius: 8px;
    background: #fafafa;
}
.category-icon-upload-label {
    display: block;
    font-weight: 600;
    font-size: .9rem;
    margin-bottom: .45rem;
}
.category-icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(7.5rem, 1fr));
    gap: .5rem;
    max-height: 360px;
    overflow-y: auto;
    padding: .5rem;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
    background: #fafafa;
}
.category-icon-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .25rem;
    padding: .5rem .35rem;
    border: 2px solid transparent;
    border-radius: 8px;
    background: #fff;
    cursor: pointer;
    transition: border-color .15s, background .15s, box-shadow .15s;
    font: inherit;
    color: inherit;
}
.category-icon-option:hover {
    border-color: #cbd5e1;
    background: #f1f5f9;
}
.category-icon-option.is-selected {
    border-color: var(--primary, #2563eb);
    background: #eff6ff;
    box-shadow: 0 0 0 1px var(--primary, #2563eb);
}
.category-icon-option-emoji {
    font-size: 1.75rem;
    line-height: 1;
}
.category-icon-option-label {
    font-size: .65rem;
    color: var(--muted, #64748b);
    text-align: center;
    line-height: 1.2;
    max-width: 100%;
}
.btn-sm { padding: .35rem .65rem; font-size: .8rem; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const input = document.getElementById('icon');
    const fileInput = document.getElementById('icon_file');
    const preview = document.getElementById('category-icon-preview');
    const previewEmoji = document.getElementById('category-icon-preview-emoji');
    const previewImg = document.getElementById('category-icon-preview-img');
    const clearBtn = document.getElementById('category-icon-clear');
    const options = document.querySelectorAll('.category-icon-option');
    let uploadPreviewUrl = null;

    function clearUploadPreviewUrl() {
        if (uploadPreviewUrl) {
            URL.revokeObjectURL(uploadPreviewUrl);
            uploadPreviewUrl = null;
        }
    }

    function setEmojiIcon(value) {
        if (!input) return;
        input.value = value || '';
        if (fileInput) {
            fileInput.value = '';
        }
        clearUploadPreviewUrl();

        options.forEach(function (btn) {
            var selected = btn.dataset.icon === value && value !== '';
            btn.classList.toggle('is-selected', selected);
            btn.setAttribute('aria-selected', selected ? 'true' : 'false');
        });

        if (preview && previewEmoji && previewImg) {
            if (value) {
                preview.hidden = false;
                previewEmoji.hidden = false;
                previewEmoji.textContent = value;
                previewImg.hidden = true;
                previewImg.removeAttribute('src');
            } else {
                preview.hidden = true;
                previewEmoji.textContent = '';
            }
        }
    }

    function setUploadPreview(file) {
        if (!file || !preview || !previewEmoji || !previewImg) return;
        clearUploadPreviewUrl();
        uploadPreviewUrl = URL.createObjectURL(file);
        preview.hidden = false;
        previewEmoji.hidden = true;
        previewEmoji.textContent = '';
        previewImg.hidden = false;
        previewImg.src = uploadPreviewUrl;
        input.value = '';
        options.forEach(function (btn) {
            btn.classList.remove('is-selected');
            btn.setAttribute('aria-selected', 'false');
        });
    }

    options.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setEmojiIcon(btn.dataset.icon || '');
        });
    });

    clearBtn?.addEventListener('click', function () {
        setEmojiIcon('');
    });

    fileInput?.addEventListener('change', function () {
        const file = fileInput.files && fileInput.files[0];
        if (file) {
            setUploadPreview(file);
        }
    });
})();
</script>
@endpush
