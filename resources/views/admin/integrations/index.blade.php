@extends('layouts.admin')

@section('title', 'Integrations')

@section('content')
<h1>Integrations</h1>
<p class="form-hint" style="margin-bottom:1.25rem;">
    Configure Scan API and Gemini AI. Settings are stored in the database per site.
</p>

<form action="{{ route('admin.integrations.update') }}" method="POST">
    @csrf
    @method('PUT')

    <div class="integrations-section">
        <h2>Scan API</h2>
        <div class="form-group">
            <label for="scan_api_url">Coupons API URL</label>
            <input type="url" id="scan_api_url" name="scan_api_url" value="{{ old('scan_api_url', $scanApiUrl) }}" maxlength="500" placeholder="https://scan.example.com/api/coupons">
            @error('scan_api_url')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label for="scan_sync_url">Import / sync URL</label>
            <input type="url" id="scan_sync_url" name="scan_sync_url" value="{{ old('scan_sync_url', $scanSyncUrl) }}" maxlength="500" placeholder="https://scan.example.com/api/coupons/import">
            @error('scan_sync_url')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label for="scan_api_limit">API limit per request</label>
            <input type="number" id="scan_api_limit" name="scan_api_limit" value="{{ old('scan_api_limit', $scanApiLimit) }}" min="1" max="200">
            @error('scan_api_limit')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label for="scan_site">Scan site slug</label>
            <input type="text" id="scan_site" name="scan_site" value="{{ old('scan_site', $scanSite) }}" maxlength="80" placeholder="e.g. hako" pattern="[A-Za-z0-9_-]+">
            <p class="form-hint">Site identifier sent to Scan when importing coupons. Leave empty to derive from <code>SITE_DOMAIN</code>.</p>
            @error('scan_site')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="integrations-section">
        <h2>Gemini AI</h2>
        <div class="form-group">
            <label for="gemini_api_key">Gemini API key</label>
            @if($geminiConfigured)
                <p class="form-hint" style="margin-bottom:.5rem;">Current key: <code>{{ $geminiMasked }}</code></p>
            @endif
            <input type="password" id="gemini_api_key" name="gemini_api_key" value="" maxlength="500" autocomplete="new-password" placeholder="{{ $geminiConfigured ? 'Leave blank to keep current key' : 'Paste API key' }}">
            <p class="form-hint">Used for AI blog generation on affiliate import.</p>
            @error('gemini_api_key')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        @if($geminiConfigured)
            <label class="form-check integrations-clear-key">
                <input type="hidden" name="clear_gemini_api_key" value="0">
                <input type="checkbox" name="clear_gemini_api_key" value="1">
                Remove saved Gemini API key
            </label>
        @endif
    </div>

    <button type="submit" class="btn btn-primary">Save settings</button>
</form>
@endsection

@push('styles')
<style>
.integrations-section {
    background: #fff;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
}
.integrations-section h2 {
    margin: 0 0 1rem;
    font-size: 1.05rem;
}
.integrations-clear-key {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-top: .75rem;
    font-size: .92rem;
}
</style>
@endpush
