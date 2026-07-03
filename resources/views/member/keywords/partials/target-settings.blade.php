@php
    use App\Support\GoogleAdsTargetingCatalog;

    $ads = $adsSettings
        ?? ($selectedStore ? $adsExport->defaultsForStore($selectedStore) : $adsExport->emptyDefaults());
    $selectedTargets = old('target_locations', $ads['target_locations'] ?? ['United States']);
    $selectedExcluded = old('excluded_locations', $ads['excluded_locations'] ?? []);
    $selectedLanguages = old('languages', $ads['languages'] ?? ['English']);
@endphp
<div class="import-card" id="languages-target-settings">
    <h2>Languages and Target Settings</h2>
    <p class="form-hint" style="margin-bottom:1rem;">
        Per-store geo and language targeting for Google Ads Editor. Saved with each keyword set.
    </p>

    <div class="keyword-ads-grid">
        <div class="form-group">
            <div class="keyword-multi-toolbar">
                <label for="target_locations">Target locations</label>
                <span class="keyword-multi-actions">
                    <button type="button" class="keyword-multi-action" data-action="all" data-target="target_locations">Select all</button>
                    <button type="button" class="keyword-multi-action" data-action="clear" data-target="target_locations">Clear</button>
                </span>
            </div>
            <input type="search" class="keyword-multi-filter" data-target="target_locations" placeholder="Filter countries…" autocomplete="off">
            <select id="target_locations" name="target_locations[]" multiple size="10" class="keyword-multi-select">
                @foreach(GoogleAdsTargetingCatalog::LOCATIONS as $location)
                    <option value="{{ $location }}" @selected(in_array($location, $selectedTargets, true))>{{ $location }}</option>
                @endforeach
            </select>
            <p class="form-hint">Ctrl/Cmd + click for multiple. Use filter or Select all for long lists.</p>
        </div>

        <div class="form-group">
            <div class="keyword-multi-toolbar">
                <label for="excluded_locations">Excluded locations (optional)</label>
                <span class="keyword-multi-actions">
                    <button type="button" class="keyword-multi-action" data-action="all" data-target="excluded_locations">Select all</button>
                    <button type="button" class="keyword-multi-action" data-action="clear" data-target="excluded_locations">Clear</button>
                </span>
            </div>
            <input type="search" class="keyword-multi-filter" data-target="excluded_locations" placeholder="Filter countries…" autocomplete="off">
            <select id="excluded_locations" name="excluded_locations[]" multiple size="10" class="keyword-multi-select">
                @foreach(GoogleAdsTargetingCatalog::LOCATIONS as $location)
                    <option value="{{ $location }}" @selected(in_array($location, $selectedExcluded, true))>{{ $location }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <div class="keyword-multi-toolbar">
                <label for="languages">Languages</label>
                <span class="keyword-multi-actions">
                    <button type="button" class="keyword-multi-action" data-action="all-languages" data-target="languages">All languages</button>
                    <button type="button" class="keyword-multi-action" data-action="each-language" data-target="languages">Each language</button>
                    <button type="button" class="keyword-multi-action" data-action="clear" data-target="languages">Clear</button>
                </span>
            </div>
            <input type="search" class="keyword-multi-filter" data-target="languages" placeholder="Filter languages…" autocomplete="off">
            <select id="languages" name="languages[]" multiple size="10" class="keyword-multi-select">
                @foreach(GoogleAdsTargetingCatalog::LANGUAGES as $language)
                    <option value="{{ $language }}" @selected(in_array($language, $selectedLanguages, true))>{{ $language }}</option>
                @endforeach
            </select>
            <p class="form-hint"><strong>All languages</strong> = one Google Ads target for every language. <strong>Each language</strong> = one row per language in CSV.</p>
        </div>

        <div class="form-group">
            <label for="targeting_status">Targeting status</label>
            <select id="targeting_status" name="targeting_status">
                @foreach($adsExport::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('targeting_status', $ads['targeting_status'] ?? 'Enabled') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <fieldset class="keyword-network-fieldset">
        <legend>Networks</legend>
        <label class="form-check keyword-ads-check">
            <input type="hidden" name="network_search" value="0">
            <input type="checkbox" name="network_search" value="1" @checked(old('network_search', $ads['network_search'] ?? true))>
            Google Search
        </label>
        <label class="form-check keyword-ads-check">
            <input type="hidden" name="network_search_partners" value="0">
            <input type="checkbox" name="network_search_partners" value="1" @checked(old('network_search_partners', $ads['network_search_partners'] ?? false))>
            Search partners
        </label>
        <label class="form-check keyword-ads-check">
            <input type="hidden" name="network_display" value="0">
            <input type="checkbox" name="network_display" value="1" @checked(old('network_display', $ads['network_display'] ?? false))>
            Display Network
        </label>
    </fieldset>
</div>
