@php
    $ads = $adsSettings
        ?? ($selectedStore ? $adsExport->defaultsForStore($selectedStore) : $adsExport->emptyDefaults());
    $singleMode = ($ads['ad_group_mode'] ?? 'standard') === 'single';
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
                placeholder="StoreName 2026-08-09"
                readonly
                title="Auto: store name + today’s date">
            <p class="form-hint">Auto-filled as <code>Store name + today’s date</code> (YYYY-MM-DD).</p>
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
                <option value="single" @selected(old('ad_group_mode', $ads['ad_group_mode'] ?? '') === 'single')>
                    Single ad group for all keywords
                </option>
                <option value="standard" @selected(old('ad_group_mode', $ads['ad_group_mode'] ?? 'standard') === 'standard')>
                    3 ad groups: Coupons / Discounts / Promo
                </option>
            </select>
        </div>

        <div class="form-group" id="single-ad-group-wrap" @if(! $singleMode) hidden @endif>
            <label for="ad_group_name">Ad group name</label>
            <input type="text" id="ad_group_name" name="ad_group_name" maxlength="120"
                value="{{ old('ad_group_name', $ads['ad_group_name'] ?? 'All Keywords') }}">
        </div>

        <div class="form-group">
            <label for="match_type">Match type</label>
            <input type="text" id="match_type_display" value="Phrase" readonly>
            <input type="hidden" id="match_type" name="match_type" value="Phrase">
            <p class="form-hint">Always Phrase match for keyword export.</p>
        </div>

        <div class="form-group">
            <label for="keyword_status">Keyword status</label>
            <select id="keyword_status" name="keyword_status">
                @foreach($adsExport::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('keyword_status', $ads['keyword_status'] ?? 'Enabled') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group keyword-money-row">
            <div class="keyword-money-fields">
                <div class="keyword-money-field">
                    <label for="budget">Daily budget *</label>
                    <div class="keyword-cpc-field">
                        <span class="keyword-cpc-symbol" id="budget_symbol" aria-hidden="true">₫</span>
                        <input type="text" id="budget" name="budget" maxlength="20"
                            value="{{ old('budget', $ads['budget'] ?? '250000') }}"
                            placeholder="250000"
                            inputmode="numeric"
                            autocomplete="off"
                            required>
                    </div>
                    @error('budget')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="keyword-money-field">
                    <label for="max_cpc">Max CPC (optional)</label>
                    <div class="keyword-cpc-field">
                        <span class="keyword-cpc-symbol" id="max_cpc_symbol" aria-hidden="true">₫</span>
                        <input type="text" id="max_cpc" name="max_cpc" maxlength="20"
                            value="{{ old('max_cpc', $ads['max_cpc'] ?? '') }}"
                            placeholder="25000"
                            inputmode="numeric"
                            autocomplete="off">
                    </div>
                </div>

                <div class="keyword-money-currency">
                    <label for="max_cpc_currency">Currency</label>
                    <select id="max_cpc_currency" name="max_cpc_currency" class="keyword-cpc-currency" aria-label="Currency">
                        @foreach($adsExport::CPC_CURRENCIES as $currency)
                            <option value="{{ $currency }}" @selected(old('max_cpc_currency', $ads['max_cpc_currency'] ?? 'VND') === $currency)>
                                {{ $currency === 'USD' ? 'USD ($)' : 'VND (₫)' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p class="form-hint">Budget and Max CPC use VND by default. Bid strategy: Manual CPC. Leave Max CPC blank for ad group default bid.</p>
        </div>
    </div>
</div>
