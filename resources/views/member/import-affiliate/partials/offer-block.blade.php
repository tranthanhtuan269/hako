<div class="offer-block" data-index="{{ $index }}">
    <div class="offer-row">
        <span class="offer-num">#<span class="offer-block-number">{{ is_numeric($index) ? $index + 1 : 1 }}</span></span>
        <div class="offer-field offer-field-code">
            <label>Code</label>
            <input type="text" name="offers[{{ $index }}][code]" value="{{ old("offers.$index.code", $offer['code'] ?? '') }}" placeholder="Optional">
        </div>
        <div class="offer-field offer-field-title">
            <label>Offer *</label>
            <input type="text" name="offers[{{ $index }}][title]" value="{{ old("offers.$index.title", $offer['title'] ?? '') }}" required placeholder="e.g. 20% Off Sitewide">
        </div>
        <button type="button" class="btn btn-outline remove-offer-btn" title="Remove offer">&times;</button>
    </div>
    <div class="offer-field offer-field-desc">
        <label>Description</label>
        <textarea name="offers[{{ $index }}][description]" rows="2" placeholder="Terms, exclusions, expiration...">{{ old("offers.$index.description", $offer['description'] ?? '') }}</textarea>
    </div>
</div>
