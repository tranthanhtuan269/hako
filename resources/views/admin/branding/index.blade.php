@extends('layouts.admin')

@section('title', 'Site Logo & Social')

@section('content')
<h1 style="margin-bottom:.5rem;">Site Logo &amp; Social</h1>
<p style="color:#64748b;margin-bottom:2rem;">
    Manage the public site logo, display name, slogan, contact emails, and social profile links shown in the header, footer, About, and Contact pages.
</p>

<form action="{{ route('admin.branding.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="branding-section">
        <h2>Site logo</h2>
        <p class="form-hint" style="margin-bottom:1rem;">
            Replaces the default % icon in the header. Recommended: PNG or SVG with a transparent background.
            Leave site name and slogan blank to show logo only (fixed height, left-aligned — good for wide logos).
        </p>

        <div class="form-group">
            <label for="site_name">Site name</label>
            <input
                type="text"
                id="site_name"
                name="site_name"
                value="{{ old('site_name', $customName) }}"
                maxlength="120"
                placeholder="{{ config('site.name') }}"
            >
            <p class="form-hint">Shown next to the logo in the header when filled. Also used site-wide when saved.</p>
            @error('site_name')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label for="site_tagline">Slogan</label>
            <input
                type="text"
                id="site_tagline"
                name="site_tagline"
                value="{{ old('site_tagline', $customTagline) }}"
                maxlength="200"
                placeholder="{{ config('site.tagline') }}"
            >
            <p class="form-hint">Short tagline under the site name in the header. Leave both fields blank for logo-only display.</p>
            @error('site_tagline')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="branding-header-preview" id="branding-header-preview">
            <span class="form-hint branding-preview-label">Header preview</span>
            <div @class([
                'branding-preview-brand',
                'is-logo-only' => !$customName && !$customTagline,
            ]) id="branding-preview-brand">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="" class="branding-preview-logo" id="branding-preview-logo">
                @else
                    <span class="branding-preview-icon" id="branding-preview-icon">%</span>
                @endif
                <span class="branding-preview-text" id="branding-preview-text" @if(!$customName && !$customTagline) hidden @endif>
                    <strong id="branding-preview-name">{{ $customName }}</strong>
                    <small id="branding-preview-tagline">{{ $customTagline }}</small>
                </span>
            </div>
        </div>

        @if($logoUrl)
            <div class="branding-logo-preview">
                <img src="{{ $logoUrl }}" alt="{{ config('site.name') }} logo" class="admin-preview-img">
            </div>
        @endif

        <div class="form-group">
            <label for="logo_file">Upload logo</label>
            <input type="file" id="logo_file" name="logo_file" accept="image/*">
            @error('logo_file')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label for="logo_url">Or logo URL</label>
            <input type="url" id="logo_url" name="logo_url" value="{{ old('logo_url') }}" placeholder="https://example.com/logo.png">
            <p class="form-hint">We download and store the image on this server.</p>
            @error('logo_url')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        @if($logoUrl)
            <label class="form-check branding-remove-logo">
                <input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))>
                Remove current logo (revert to default icon)
            </label>
        @endif
    </div>

    <div class="branding-section">
        <h2>Favicon</h2>
        <p class="form-hint" style="margin-bottom:1rem;">
            Browser tab icon for the public site and admin. Upload any image, then crop to a square for a clean result.
            Best results: a simple mark on a transparent or solid background.
        </p>

        <div class="branding-favicon-row">
            <div class="branding-favicon-preview-wrap" id="favicon-preview-wrap" @if(!$faviconUrl) hidden @endif>
                <span class="form-hint branding-preview-label">Preview</span>
                <div class="branding-favicon-previews">
                    <div class="branding-favicon-chip branding-favicon-chip--light">
                        <img src="{{ $faviconUrl }}" alt="" id="favicon-preview-light" class="branding-favicon-img">
                    </div>
                    <div class="branding-favicon-chip branding-favicon-chip--dark">
                        <img src="{{ $faviconUrl }}" alt="" id="favicon-preview-dark" class="branding-favicon-img">
                    </div>
                    <div class="branding-favicon-tab" aria-hidden="true">
                        <img src="{{ $faviconUrl }}" alt="" id="favicon-preview-tab" class="branding-favicon-tab-img">
                        <span>{{ $siteName ?? config('site.name') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="favicon_picker">Upload &amp; crop favicon</label>
            <input type="file" id="favicon_picker" accept="image/png,image/jpeg,image/webp,image/gif" aria-describedby="favicon_hint">
            <input type="file" id="favicon_file" name="favicon_file" accept="image/png" hidden>
            <p class="form-hint" id="favicon_hint">Choose an image to open the crop tool (1:1 square). The cropped PNG is saved when you click Save.</p>
            @error('favicon_file')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        @if($faviconUrl)
            <label class="form-check branding-remove-logo">
                <input type="checkbox" name="remove_favicon" value="1" id="remove_favicon" @checked(old('remove_favicon'))>
                Remove current favicon
            </label>
        @endif
    </div>

    <div class="branding-favicon-modal" id="favicon-crop-modal" hidden>
        <div class="branding-favicon-modal__backdrop" data-favicon-modal-close></div>
        <div class="branding-favicon-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="favicon-crop-title">
            <div class="branding-favicon-modal__header">
                <h3 id="favicon-crop-title">Crop favicon</h3>
                <button type="button" class="branding-favicon-modal__close" data-favicon-modal-close aria-label="Close">&times;</button>
            </div>
            <div class="branding-favicon-modal__body">
                <div class="branding-favicon-crop-stage">
                    <img id="favicon-crop-image" alt="Image to crop" src="">
                </div>
                <div class="branding-favicon-crop-tools">
                    <button type="button" class="btn btn-outline" id="favicon-zoom-out" title="Zoom out">−</button>
                    <button type="button" class="btn btn-outline" id="favicon-zoom-in" title="Zoom in">+</button>
                    <button type="button" class="btn btn-outline" id="favicon-rotate" title="Rotate 90°">↻</button>
                    <button type="button" class="btn btn-outline" id="favicon-reset" title="Reset">Reset</button>
                </div>
                <p class="form-hint" style="margin:0;">Drag to reposition. Use the handles to resize the square crop.</p>
            </div>
            <div class="branding-favicon-modal__footer">
                <button type="button" class="btn btn-outline" data-favicon-modal-close>Cancel</button>
                <button type="button" class="btn btn-primary" id="favicon-crop-apply">Use this crop</button>
            </div>
        </div>
    </div>

    <div class="branding-section">
        <h2>Contact emails</h2>
        <p class="form-hint" style="margin-bottom:1rem;">
            Shown on About Us, Contact Us, and legal pages. Leave blank to use the default from site config.
        </p>

        <div class="form-group">
            <label for="contact_email">Contact email (About / Contact)</label>
            <input
                type="email"
                id="contact_email"
                name="contact_email"
                value="{{ old('contact_email', $contactEmail) }}"
                maxlength="255"
                placeholder="{{ $defaultContactEmail }}"
                autocomplete="off"
            >
            <p class="form-hint">Used for general inquiries on About Us and Contact Us.</p>
            @error('contact_email')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label for="privacy_email">Privacy email</label>
            <input
                type="email"
                id="privacy_email"
                name="privacy_email"
                value="{{ old('privacy_email', $privacyEmail) }}"
                maxlength="255"
                placeholder="{{ $defaultPrivacyEmail }}"
                autocomplete="off"
            >
            <p class="form-hint">Used for privacy requests on Contact Us and Privacy Policy.</p>
            @error('privacy_email')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="branding-section">
        <h2>Social links</h2>
        <p class="form-hint" style="margin-bottom:1rem;">
            Leave blank to hide a network. Links open in a new tab from the footer and contact page.
        </p>

        @foreach($networks as $key => $network)
            <div class="form-group">
                <label for="social_{{ $key }}">{{ $network['label'] }}</label>
                <input
                    type="url"
                    id="social_{{ $key }}"
                    name="social[{{ $key }}]"
                    value="{{ old('social.'.$key, $socialUrls[$key] ?? '') }}"
                    placeholder="{{ $network['placeholder'] }}"
                >
                @error('social.'.$key)<p class="form-error">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>

    <div class="branding-section">
        <h2>Homepage &amp; footer copy</h2>
        <p class="form-hint" style="margin-bottom:1rem;">
            Customize the main homepage headline, the hero subtitle below it, and the footer tagline text.
        </p>

        <div class="form-group">
            <label for="home_h1">Homepage H1</label>
            <input
                type="text"
                id="home_h1"
                name="home_h1"
                value="{{ old('home_h1', $homeH1) }}"
                maxlength="180"
                placeholder="{{ config('site.name') }}"
            >
            <p class="form-hint">Main H1 shown on the homepage hero.</p>
            @error('home_h1')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label for="home_sub_h1">Homepage sub H1</label>
            <input
                type="text"
                id="home_sub_h1"
                name="home_sub_h1"
                value="{{ old('home_sub_h1', $homeSubH1) }}"
                maxlength="200"
                placeholder="{{ config('site.tagline') }}"
            >
            <p class="form-hint">Sub H1 shown on the homepage hero.</p>
            @error('home_sub_h1')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label for="hero_subtitle">Hero subtitle</label>
            <textarea
                id="hero_subtitle"
                name="hero_subtitle"
                rows="3"
                maxlength="320"
                placeholder="Deals for brands like Amazon, Walmart, Target, and other U.S. retailers."
            >{{ old('hero_subtitle', $heroSubtitle) }}</textarea>
            <p class="form-hint">Text under the homepage hero heading.</p>
            @error('hero_subtitle')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label for="footer_tagline">Footer tagline</label>
            <input
                type="text"
                id="footer_tagline"
                name="footer_tagline"
                value="{{ old('footer_tagline', $footerTagline) }}"
                maxlength="200"
                placeholder="{{ config('site.tagline') }}"
            >
            <p class="form-hint">Replaces the “Top Hub of US Online Coupons” line in the footer.</p>
            @error('footer_tagline')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label for="footer_sub_tagline">Footer sub tagline</label>
            <input
                type="text"
                id="footer_sub_tagline"
                name="footer_sub_tagline"
                value="{{ old('footer_sub_tagline', $footerSubTagline) }}"
            >
            <p class="form-hint">Replaces the “Coupon codes and discount deals for U.S. shoppers. Updated daily at {{ config('site.domain') }}.” line in the footer.</p>
            @error('footer_sub_tagline')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label for="footer_description_tagline">Footer description tagline</label>
            <textarea
                rows="3"
                maxlength="200"
                id="footer_description_tagline"
                name="footer_description_tagline"
                value="{{ old('footer_description_tagline', $footerDescriptionTagline) }}"
            >{{ old('footer_description_tagline', $footerDescriptionTagline) }}</textarea>
            <p class="form-hint">Description shown below the footer tagline.</p>
            @error('footer_description_tagline')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="{{ route('home') }}" class="btn btn-outline" target="_blank" rel="noopener">View public site →</a>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js" crossorigin="anonymous"></script>
<script>
(() => {
    const nameInput = document.getElementById('site_name');
    const taglineInput = document.getElementById('site_tagline');
    const previewBrand = document.getElementById('branding-preview-brand');
    const previewText = document.getElementById('branding-preview-text');
    const previewName = document.getElementById('branding-preview-name');
    const previewTagline = document.getElementById('branding-preview-tagline');

    function syncBrandPreview() {
        const name = nameInput.value.trim();
        const tagline = taglineInput.value.trim();
        const showText = name !== '' || tagline !== '';

        previewBrand.classList.toggle('is-logo-only', !showText);
        previewText.hidden = !showText;
        previewName.textContent = name;
        previewName.hidden = name === '';
        previewTagline.textContent = tagline;
        previewTagline.hidden = tagline === '';
    }

    nameInput?.addEventListener('input', syncBrandPreview);
    taglineInput?.addEventListener('input', syncBrandPreview);

    const picker = document.getElementById('favicon_picker');
    const fileInput = document.getElementById('favicon_file');
    const removeFavicon = document.getElementById('remove_favicon');
    const modal = document.getElementById('favicon-crop-modal');
    const cropImage = document.getElementById('favicon-crop-image');
    const previewWrap = document.getElementById('favicon-preview-wrap');
    const previewImgs = [
        document.getElementById('favicon-preview-light'),
        document.getElementById('favicon-preview-dark'),
        document.getElementById('favicon-preview-tab'),
    ].filter(Boolean);

    let cropper = null;
    let objectUrl = null;

    function openModal() {
        if (!modal) return;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!modal) return;
        modal.hidden = true;
        document.body.style.overflow = '';
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
        cropImage.removeAttribute('src');
        if (picker) picker.value = '';
    }

    function setPreview(url) {
        previewImgs.forEach((img) => { img.src = url; });
        if (previewWrap) previewWrap.hidden = false;
    }

    function initCropper() {
        if (typeof Cropper === 'undefined') {
            alert('Crop tool failed to load. Please refresh and try again.');
            closeModal();
            return;
        }

        cropper = new Cropper(cropImage, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 1,
            responsive: true,
            background: false,
            movable: true,
            zoomable: true,
            rotatable: true,
            scalable: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            guides: true,
            center: true,
            highlight: false,
        });
    }

    picker?.addEventListener('change', () => {
        const file = picker.files?.[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            alert('Please choose an image file.');
            picker.value = '';
            return;
        }

        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);
        cropImage.src = objectUrl;
        openModal();

        cropImage.onload = () => {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            initCropper();
        };
    });

    document.querySelectorAll('[data-favicon-modal-close]').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    document.getElementById('favicon-zoom-in')?.addEventListener('click', () => cropper?.zoom(0.1));
    document.getElementById('favicon-zoom-out')?.addEventListener('click', () => cropper?.zoom(-0.1));
    document.getElementById('favicon-rotate')?.addEventListener('click', () => cropper?.rotate(90));
    document.getElementById('favicon-reset')?.addEventListener('click', () => cropper?.reset());

    document.getElementById('favicon-crop-apply')?.addEventListener('click', () => {
        if (!cropper || !fileInput) return;

        const canvas = cropper.getCroppedCanvas({
            width: 512,
            height: 512,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
            fillColor: 'transparent',
        });

        if (!canvas) {
            alert('Could not crop this image. Try another file.');
            return;
        }

        canvas.toBlob((blob) => {
            if (!blob) {
                alert('Could not create favicon. Try another image.');
                return;
            }

            const cropped = new File([blob], 'favicon.png', { type: 'image/png' });
            const dt = new DataTransfer();
            dt.items.add(cropped);
            fileInput.files = dt.files;

            if (removeFavicon) removeFavicon.checked = false;
            setPreview(URL.createObjectURL(blob));
            closeModal();
        }, 'image/png');
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal && !modal.hidden) {
            closeModal();
        }
    });
})();
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" crossorigin="anonymous">
<style>
.branding-section {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem 1.35rem;
    margin-bottom: 1.25rem;
}
.branding-section h2 {
    margin: 0 0 .35rem;
    font-size: 1.05rem;
}
.branding-logo-preview {
    margin-bottom: 1rem;
}
.branding-logo-preview .admin-preview-img {
    max-width: 120px;
    max-height: 120px;
    object-fit: contain;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: #fff;
    padding: .5rem;
}
.branding-remove-logo {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-top: .75rem;
    font-size: .92rem;
}
.branding-header-preview {
    margin: 1rem 0;
    padding: .85rem 1rem;
    border: 1px dashed var(--border);
    border-radius: 10px;
    background: #f8fafc;
}
.branding-preview-label {
    display: block;
    margin-bottom: .65rem;
}
.branding-preview-brand {
    display: flex;
    align-items: center;
    gap: .5rem;
    color: var(--secondary);
}
.branding-preview-brand.is-logo-only .branding-preview-text {
    display: none;
}
.branding-preview-icon {
    background: linear-gradient(135deg, var(--primary), var(--accent));
    color: #fff;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.branding-preview-logo {
    height: 36px;
    width: auto;
    max-width: 220px;
    object-fit: contain;
    object-position: left center;
    border-radius: 10px;
    background: #fff;
    border: 1px solid var(--border);
}
.branding-preview-text {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}
.branding-preview-text strong {
    font-size: 1.05rem;
}
.branding-preview-text small {
    font-size: .65rem;
    font-weight: 600;
    color: #64748b;
    letter-spacing: .04em;
}
.branding-favicon-row {
    margin-bottom: 1rem;
}
.branding-favicon-previews {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .75rem;
}
.branding-favicon-chip {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    border: 1px solid var(--border);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: .4rem;
}
.branding-favicon-chip--light {
    background: #fff;
}
.branding-favicon-chip--dark {
    background: #0f172a;
}
.branding-favicon-img {
    width: 32px;
    height: 32px;
    object-fit: contain;
}
.branding-favicon-tab {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .35rem .7rem .35rem .45rem;
    border-radius: 8px 8px 0 0;
    background: #e2e8f0;
    color: #334155;
    font-size: .78rem;
    font-weight: 600;
}
.branding-favicon-tab-img {
    width: 16px;
    height: 16px;
    object-fit: contain;
}
.branding-favicon-modal[hidden] {
    display: none !important;
}
.branding-favicon-modal {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.branding-favicon-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, .55);
}
.branding-favicon-modal__dialog {
    position: relative;
    width: min(640px, 100%);
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, .25);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 2rem);
}
.branding-favicon-modal__header,
.branding-favicon-modal__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .9rem 1.1rem;
    border-bottom: 1px solid var(--border);
}
.branding-favicon-modal__footer {
    border-bottom: 0;
    border-top: 1px solid var(--border);
    justify-content: flex-end;
}
.branding-favicon-modal__header h3 {
    margin: 0;
    font-size: 1.05rem;
}
.branding-favicon-modal__close {
    border: 0;
    background: transparent;
    font-size: 1.5rem;
    line-height: 1;
    cursor: pointer;
    color: #64748b;
}
.branding-favicon-modal__body {
    padding: 1rem 1.1rem;
    display: flex;
    flex-direction: column;
    gap: .75rem;
    overflow: auto;
}
.branding-favicon-crop-stage {
    width: 100%;
    height: min(360px, 55vh);
    background: #0f172a;
    border-radius: 10px;
    overflow: hidden;
}
.branding-favicon-crop-stage img {
    display: block;
    max-width: 100%;
}
.branding-favicon-crop-tools {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
}
.branding-favicon-crop-tools .btn {
    min-width: 2.5rem;
}
</style>
@endpush
