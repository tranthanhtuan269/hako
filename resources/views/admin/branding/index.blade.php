@extends('layouts.admin')

@section('title', 'Site Logo & Social')

@section('content')
<h1 style="margin-bottom:.5rem;">Site Logo &amp; Social</h1>
<p style="color:#64748b;margin-bottom:2rem;">
    Manage the public site logo and social profile links shown in the header, footer, and contact page.
</p>

<form action="{{ route('admin.branding.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="branding-section">
        <h2>Site logo</h2>
        <p class="form-hint" style="margin-bottom:1rem;">
            Replaces the default % icon in the header. Recommended: square PNG or SVG, at least 128×128px.
        </p>

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
</style>
@endpush
