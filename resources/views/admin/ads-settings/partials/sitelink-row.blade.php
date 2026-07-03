<div class="ads-settings-row sitelink-row">
    <div class="ads-settings-row-head">
        <strong>Sitelink <span class="ads-settings-row-number">{{ (int) $index + 1 }}</span></strong>
        <button type="button" class="btn btn-outline btn-sm remove-sitelink-row">Remove</button>
    </div>
    <div class="ads-settings-row-grid">
        <div class="form-group">
            <label>Link text</label>
            <input type="text" name="sitelinks[{{ $index }}][link_text]" maxlength="25"
                value="{{ old('sitelinks.'.$index.'.link_text', $sitelink['link_text'] ?? '') }}" placeholder="About Us">
        </div>
        <div class="form-group">
            <label>Final URL</label>
            <input type="url" name="sitelinks[{{ $index }}][final_url]" maxlength="500"
                value="{{ old('sitelinks.'.$index.'.final_url', $sitelink['final_url'] ?? '') }}" placeholder="https://example.com/about-us">
        </div>
        <div class="form-group">
            <label>Description line 1</label>
            <input type="text" name="sitelinks[{{ $index }}][description_1]" maxlength="35"
                value="{{ old('sitelinks.'.$index.'.description_1', $sitelink['description_1'] ?? '') }}">
        </div>
        <div class="form-group">
            <label>Description line 2</label>
            <input type="text" name="sitelinks[{{ $index }}][description_2]" maxlength="35"
                value="{{ old('sitelinks.'.$index.'.description_2', $sitelink['description_2'] ?? '') }}">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="sitelinks[{{ $index }}][status]">
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(old('sitelinks.'.$index.'.status', $sitelink['status'] ?? 'Enabled') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
