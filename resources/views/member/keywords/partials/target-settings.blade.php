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
        Tick multiple items in each list.
    </p>

    <div class="keyword-ads-grid">
        <div class="form-group">
            <div class="keyword-multi-toolbar">
                <label for="target_locations_filter">Target locations</label>
                <span class="keyword-multi-actions">
                    <button type="button" class="keyword-multi-action" data-action="all" data-target="target_locations">Select all</button>
                    <button type="button" class="keyword-multi-action" data-action="clear" data-target="target_locations">Clear</button>
                </span>
            </div>
            <input type="search" id="target_locations_filter" class="keyword-multi-filter" data-target="target_locations" placeholder="Filter countries…" autocomplete="off">
            <div id="target_locations" class="keyword-multi-box" data-multi-name="target_locations" role="group" aria-label="Target locations">
                @foreach(GoogleAdsTargetingCatalog::LOCATIONS as $location)
                    <label class="keyword-multi-option" data-label="{{ strtolower($location) }}">
                        <input type="checkbox" name="target_locations[]" value="{{ $location }}"
                            @checked(in_array($location, $selectedTargets, true))>
                        <span>{{ $location }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="form-group">
            <div class="keyword-multi-toolbar">
                <label for="excluded_locations_filter">Excluded locations (optional)</label>
                <span class="keyword-multi-actions">
                    <button type="button" class="keyword-multi-action" data-action="all" data-target="excluded_locations">Select all</button>
                    <button type="button" class="keyword-multi-action" data-action="clear" data-target="excluded_locations">Clear</button>
                </span>
            </div>
            <input type="search" id="excluded_locations_filter" class="keyword-multi-filter" data-target="excluded_locations" placeholder="Filter countries…" autocomplete="off">
            <div id="excluded_locations" class="keyword-multi-box" data-multi-name="excluded_locations" role="group" aria-label="Excluded locations">
                @foreach(GoogleAdsTargetingCatalog::LOCATIONS as $location)
                    <label class="keyword-multi-option" data-label="{{ strtolower($location) }}">
                        <input type="checkbox" name="excluded_locations[]" value="{{ $location }}"
                            @checked(in_array($location, $selectedExcluded, true))>
                        <span>{{ $location }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="form-group">
            <div class="keyword-multi-toolbar">
                <label for="languages_filter">Languages</label>
                <span class="keyword-multi-actions">
                    <button type="button" class="keyword-multi-action" data-action="all-languages" data-target="languages">All languages</button>
                    <button type="button" class="keyword-multi-action" data-action="clear" data-target="languages">Clear</button>
                </span>
            </div>
            <input type="search" id="languages_filter" class="keyword-multi-filter" data-target="languages" placeholder="Filter languages…" autocomplete="off">
            <div id="languages" class="keyword-multi-box" data-multi-name="languages" role="group" aria-label="Languages">
                @foreach(GoogleAdsTargetingCatalog::concreteLanguages() as $language)
                    <label class="keyword-multi-option" data-label="{{ strtolower($language) }}">
                        <input type="checkbox" name="languages[]" value="{{ $language }}"
                            @checked(in_array($language, $selectedLanguages, true) || in_array('All languages', $selectedLanguages, true))>
                        <span>{{ $language }}</span>
                    </label>
                @endforeach
            </div>
            <p class="form-hint"><strong>All languages</strong> selects every language in the list. Clear to reset.</p>
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
