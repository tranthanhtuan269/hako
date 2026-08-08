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
            <input type="url" id="scan_api_url" name="scan_api_url" value="{{ old('scan_api_url', $scanApiUrl) }}" maxlength="500" placeholder="https://scan.thuoc360.com/api/coupons">
            @error('scan_api_url')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label for="scan_affiliate_signups_api_url">Affiliate signups API URL</label>
            <input type="url" id="scan_affiliate_signups_api_url" name="scan_affiliate_signups_api_url" value="{{ old('scan_affiliate_signups_api_url', $scanAffiliateSignupsApiUrl) }}" maxlength="500" placeholder="https://scan.thuoc360.com/api/affiliate-signups">
            <p class="form-hint">Used by Admin → Affiliate Signups. Must end with <code>/api/affiliate-signups</code> (not <code>/api/coupons</code>). Leave empty for default.</p>
            @error('scan_affiliate_signups_api_url')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <p class="form-hint">Coupon import/sync always uses <code>https://scan.thuoc360.com/api/coupons/import</code>.</p>
        <div class="form-group">
            <label for="scan_api_limit">API limit per request</label>
            <input type="number" id="scan_api_limit" name="scan_api_limit" value="{{ old('scan_api_limit', $scanApiLimit) }}" min="1" max="200">
            @error('scan_api_limit')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label for="scan_site">Scan site slug</label>
            <input type="text" id="scan_site" name="scan_site" value="{{ old('scan_site', $scanSite !== '' ? $scanSite : $defaultScanSite) }}" maxlength="80" placeholder="{{ $defaultScanSite }}" pattern="[A-Za-z0-9_-]+">
            <p class="form-hint">Site identifier sent to Scan API. Auto-detected from domain: <code>{{ $defaultScanSite }}</code> (current effective: <code>{{ $effectiveScanSite }}</code>).</p>
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

    <div class="integrations-section">
        <h2>Affiliate Import</h2>
        <label class="form-check integrations-clear-key">
            <input type="hidden" name="import_allow_reimport_existing_stores" value="0">
            <input type="checkbox" name="import_allow_reimport_existing_stores" value="1" @checked(old('import_allow_reimport_existing_stores', $allowReimportExistingStores))>
            Allow re-importing existing stores
        </label>
        <p class="form-hint" style="margin-top:.5rem;">
            When enabled, importing the same merchant again updates the store, replaces offers, and refreshes the blog post.
            When disabled, the import button is blocked if that store already exists on your site.
        </p>
    </div>

    <div class="integrations-section">
        <h2>Coupon redirect flow</h2>
        <p class="form-hint" style="margin-bottom:.85rem;">
            Flow 1 keeps the coupon popup (destination opens when it closes).
            Flow 2/3 copy the code first, then open the merchant tab immediately — no coupon popup.
            On mobile, Flow 2/3 open the merchant right away (same-tab fallback if the browser blocks a new tab).
        </p>
        <div class="form-group coupon-redirect-options">
            @foreach($couponRedirectOptions as $value => $label)
                <label class="form-check integrations-clear-key">
                    <input
                        type="radio"
                        name="coupon_redirect_flow"
                        value="{{ $value }}"
                        @checked(old('coupon_redirect_flow', $couponRedirectFlow) === $value)
                    >
                    {{ $label }}
                </label>
            @endforeach
            @error('coupon_redirect_flow')<p class="form-error">{{ $message }}</p>@enderror
        </div>
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
.coupon-redirect-options .integrations-clear-key {
    align-items: flex-start;
    margin-top: .55rem;
}
.coupon-redirect-options .integrations-clear-key input {
    margin-top: .2rem;
}
</style>
@endpush
