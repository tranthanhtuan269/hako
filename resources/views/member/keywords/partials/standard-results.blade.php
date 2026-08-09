@php
    $standard = $standardCampaign ?? null;
    $exportCampaignUrl = $exportCampaignUrl ?? null;
    $exportKeywordsUrl = $exportKeywordsUrl ?? null;
    $exportAdsUrl = $exportAdsUrl ?? null;
    $exportAssetsCsvUrl = $exportAssetsCsvUrl ?? null;
    $exportTargetingCsvUrl = $exportTargetingCsvUrl ?? null;
    $groupMode = is_array($standard) ? ($standard['ad_group_mode'] ?? 'standard') : 'standard';
    $title = $groupMode === 'single'
        ? 'Standard Campaign — Single ad group'
        : 'Standard Campaign — Coupons / Discounts / Promo';
@endphp
@if(is_array($standard) && ! empty($standard['groups']))
<div class="import-card keyword-results-card" style="margin-top:1.5rem;" id="standard-campaign-results">
    <div class="import-card-header">
        <h2>{{ $title }}</h2>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            @if($exportCampaignUrl)
                <a href="{{ $exportCampaignUrl }}" class="btn btn-primary btn-sm">Download Campaign CSV</a>
            @endif
            @if($exportKeywordsUrl)
                <a href="{{ $exportKeywordsUrl }}" class="btn btn-primary btn-sm">Download Keywords CSV</a>
            @endif
            @if($exportAdsUrl)
                <a href="{{ $exportAdsUrl }}" class="btn btn-primary btn-sm">Download RSA Ads CSV</a>
            @endif
            @if($exportAssetsCsvUrl)
                <a href="{{ $exportAssetsCsvUrl }}" class="btn btn-outline btn-sm">Assets CSV</a>
            @endif
            @if($exportTargetingCsvUrl)
                <a href="{{ $exportTargetingCsvUrl }}" class="btn btn-outline btn-sm">Targeting CSV</a>
            @endif
        </div>
    </div>

    <p class="form-hint" style="margin-bottom:1rem;">
        Campaign: <strong>{{ $standard['campaign_name'] }}</strong> ·
        Discount: <strong>{{ $standard['discount_percent'] }}%</strong> ·
        Keywords: <strong>{{ $standard['keyword_count'] }}</strong> ·
        Final URL: <code>{{ $standard['final_url'] }}</code>
    </p>
    <p class="form-hint" style="margin-bottom:1rem;">
        Import order in Google Ads Editor:
        <strong>Campaign CSV</strong> → Keywords → RSA Ads → Assets → Targeting.
        Campaign CSV includes budget, networks, languages, Manual CPC, and <code>EU political ads = No</code>.
        Targeting CSV is locations only (<code>Location</code> + <code>Negative</code> for exclusions).
    </p>

    @foreach($standard['groups'] as $groupName => $group)
        @php
            $group = is_array($group) ? $group : ['keywords' => [], 'headlines' => [], 'descriptions' => []];
            $group['keywords'] = $group['keywords'] ?? [];
            $group['headlines'] = $group['headlines'] ?? [];
            $group['descriptions'] = $group['descriptions'] ?? [];
        @endphp
        <div class="standard-group-block" style="margin-bottom:1.25rem;padding:1rem;border:1px solid var(--border);border-radius:8px;background:#f8fafc;">
            <h3 style="margin:0 0 .75rem;font-size:1.05rem;">Ad group: {{ $groupName }}</h3>
            <div class="keyword-templates-grid">
                <div>
                    <h4 style="margin:0 0 .4rem;font-size:.9rem;">Keywords ({{ count($group['keywords']) }})</h4>
                    <ul class="keyword-result-list">
                        @foreach($group['keywords'] as $keyword)
                            <li>"{{ $keyword }}"</li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h4 style="margin:0 0 .4rem;font-size:.9rem;">RSA Headlines ({{ count($group['headlines']) }})</h4>
                    <ul class="keyword-result-list">
                        @foreach($group['headlines'] as $headline)
                            <li>{{ $headline }} <span class="form-hint">({{ mb_strlen($headline) }}/30)</span></li>
                        @endforeach
                    </ul>
                    <h4 style="margin:1rem 0 .4rem;font-size:.9rem;">RSA Descriptions ({{ count($group['descriptions']) }})</h4>
                    <ul class="keyword-result-list">
                        @foreach($group['descriptions'] as $description)
                            <li>{{ $description }} <span class="form-hint">({{ mb_strlen($description) }}/90)</span></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif
