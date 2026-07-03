<div class="ads-settings-row callout-row">
    <div class="ads-settings-row-head">
        <strong>Callout <span class="ads-settings-row-number">{{ (int) $index + 1 }}</span></strong>
        <button type="button" class="btn btn-outline btn-sm remove-callout-row">Remove</button>
    </div>
    <div class="ads-settings-row-grid">
        <div class="form-group">
            <label>Callout text</label>
            <input type="text" name="callouts[{{ $index }}][text]" maxlength="25"
                value="{{ old('callouts.'.$index.'.text', $callout['text'] ?? '') }}" placeholder="Verified promo codes">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="callouts[{{ $index }}][status]">
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(old('callouts.'.$index.'.status', $callout['status'] ?? 'Enabled') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
