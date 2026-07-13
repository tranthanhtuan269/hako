@extends('layouts.admin')

@section('title', 'Site Logo & Social')

@section('content')
<h1 style="margin-bottom:.5rem;">Site Logo &amp; Social</h1>
<p style="color:#64748b;margin-bottom:2rem;">
    Manage the public site logo, display name, slogan, and social profile links shown in the header, footer, and contact page.
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

    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="{{ route('home') }}" class="btn btn-outline" target="_blank" rel="noopener">View public site →</a>
    </div>
</form>
@endsection

@push('scripts')
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
})();
</script>
@endpush

@push('styles')
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
</style>
@endpush
