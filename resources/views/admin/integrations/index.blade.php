@extends('layouts.admin')

@section('title', 'Integrations')

@section('content')
<h1>Integrations</h1>
<p class="form-hint" style="margin-bottom:1.25rem;">
    Connect one or more AI providers, then choose which one writes blog posts and store descriptions. Keys are stored in the database per site.
</p>

<form action="{{ route('admin.integrations.update') }}" method="POST">
    @csrf
    @method('PUT')

    <div class="integrations-section">
        <h2>Active AI model</h2>
        <p class="form-hint" style="margin-bottom:.85rem;">
            Affiliate import uses this provider when its API key is saved. If the key is missing, import falls back to the template article.
        </p>
        <div class="form-group">
            <label for="ai_provider">Provider</label>
            <select id="ai_provider" name="ai_provider">
                @foreach($aiProviders as $provider)
                    <option value="{{ $provider['id'] }}" @selected(old('ai_provider', $aiProvider) === $provider['id'])>
                        {{ $provider['label'] }}{{ $provider['configured'] ? '' : ' (no key yet)' }}
                    </option>
                @endforeach
            </select>
            @error('ai_provider')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>

    @foreach($aiProviders as $provider)
        <div class="integrations-section">
            <h2>{{ $provider['label'] }}</h2>
            <p class="form-hint" style="margin-bottom:.85rem;">
                {{ $provider['hint'] }}
                <a href="{{ $provider['docs_url'] }}" target="_blank" rel="noopener">Get an API key</a>
            </p>
            <div class="form-group">
                <label for="ai_keys_{{ $provider['id'] }}">API key</label>
                @if($provider['configured'])
                    <p class="form-hint" style="margin-bottom:.5rem;">Current key: <code>{{ $provider['masked'] }}</code></p>
                @endif
                <input
                    type="password"
                    id="ai_keys_{{ $provider['id'] }}"
                    name="ai_keys[{{ $provider['id'] }}]"
                    value=""
                    maxlength="500"
                    autocomplete="new-password"
                    placeholder="{{ $provider['configured'] ? 'Leave blank to keep current key' : 'Paste API key' }}"
                >
                @error("ai_keys.{$provider['id']}")<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="ai_models_{{ $provider['id'] }}">Model</label>
                <input
                    type="text"
                    id="ai_models_{{ $provider['id'] }}"
                    name="ai_models[{{ $provider['id'] }}]"
                    value="{{ old('ai_models.'.$provider['id'], $provider['model']) }}"
                    maxlength="120"
                    placeholder="{{ $provider['default_model'] }}"
                >
                <p class="form-hint">{{ $provider['model_hint'] }}</p>
                @error("ai_models.{$provider['id']}")<p class="form-error">{{ $message }}</p>@enderror
            </div>
            @if($provider['configured'])
                <label class="form-check integrations-clear-key">
                    <input type="hidden" name="clear_ai_keys[{{ $provider['id'] }}]" value="0">
                    <input type="checkbox" name="clear_ai_keys[{{ $provider['id'] }}]" value="1">
                    Remove saved {{ $provider['label'] }} API key
                </label>
            @endif
        </div>
    @endforeach

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
