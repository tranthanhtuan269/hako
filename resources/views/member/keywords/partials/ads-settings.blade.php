@php
    $ads = $adsSettings
        ?? ($selectedStore ? $adsExport->defaultsForStore($selectedStore) : $adsExport->emptyDefaults());
    $singleMode = ($ads['ad_group_mode'] ?? 'brand_and_products') === 'single';
@endphp
<div class="import-card" id="google-ads-settings">
    <h2>Google Ads export</h2>
    <p class="form-hint" style="margin-bottom:1rem;">
        Configure standard Search keyword columns for Google Ads Editor CSV import:
        Campaign, Ad Group, Keyword, Criterion Type, Max CPC, Final URL, Status.
    </p>

    <div class="keyword-ads-grid">
        <div class="form-group">
            <label for="campaign_name">Campaign name</label>
            <input type="text" id="campaign_name" name="campaign_name" maxlength="120"
                value="{{ old('campaign_name', $ads['campaign_name'] ?? '') }}"
                placeholder="MoveSpeed - Search Coupons">
        </div>

        <div class="form-group">
            <label for="final_url">Final URL</label>
            <input type="url" id="final_url" name="final_url" maxlength="500"
                value="{{ old('final_url', $ads['final_url'] ?? '') }}"
                placeholder="https://example.com/store/movespeed">
            <p class="form-hint">Landing page for all keywords. Defaults to the store affiliate or public page.</p>
        </div>

        <div class="form-group">
            <label for="ad_group_mode">Ad group structure</label>
            <select id="ad_group_mode" name="ad_group_mode">
                <option value="brand_and_products" @selected(old('ad_group_mode', $ads['ad_group_mode'] ?? '') === 'brand_and_products')>
                    Brand ad group + one ad group per product
                </option>
                <option value="single" @selected(old('ad_group_mode', $ads['ad_group_mode'] ?? '') === 'single')>
                    Single ad group for all keywords
                </option>
            </select>
        </div>

        <div class="form-group" id="single-ad-group-wrap" @if(! $singleMode) hidden @endif>
            <label for="ad_group_name">Ad group name</label>
            <input type="text" id="ad_group_name" name="ad_group_name" maxlength="120"
                value="{{ old('ad_group_name', $ads['ad_group_name'] ?? 'All Keywords') }}">
        </div>

        <div class="form-group" id="brand-ad-group-wrap" @if($singleMode) hidden @endif>
            <label for="brand_ad_group_suffix">Brand ad group suffix</label>
            <input type="text" id="brand_ad_group_suffix" name="brand_ad_group_suffix" maxlength="80"
                value="{{ old('brand_ad_group_suffix', $ads['brand_ad_group_suffix'] ?? 'Brand') }}">
            <p class="form-hint">Example: <code>MoveSpeed - Brand</code></p>
        </div>

        <div class="form-group">
            <label for="match_type">Match type</label>
            <select id="match_type" name="match_type">
                @foreach($adsExport::MATCH_TYPES as $type)
                    <option value="{{ $type }}" @selected(old('match_type', $ads['match_type'] ?? 'Phrase') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="keyword_status">Keyword status</label>
            <select id="keyword_status" name="keyword_status">
                @foreach($adsExport::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('keyword_status', $ads['keyword_status'] ?? 'Enabled') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="max_cpc">Max CPC (optional)</label>
            <input type="text" id="max_cpc" name="max_cpc" maxlength="20"
                value="{{ old('max_cpc', $ads['max_cpc'] ?? '') }}"
                placeholder="1.50">
            <p class="form-hint">Leave blank to use the ad group default bid in Google Ads.</p>
        </div>
    </div>

    <label class="form-check keyword-ads-check">
        <input type="checkbox" name="all_match_types" value="1" @checked(old('all_match_types', $ads['all_match_types'] ?? false))>
        Export Broad, Phrase, and Exact rows for each keyword (3× rows)
    </label>
</div>
