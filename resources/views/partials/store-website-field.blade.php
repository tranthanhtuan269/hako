<div class="form-group">
    <label for="store-website">Store website (public)</label>
    <input type="url" id="store-website" name="website" value="{{ old('website', $store->website ?? '') }}" placeholder="https://example.com">
    <p class="form-hint">Shown on your public store page. Use the merchant homepage, not your affiliate tracking link.</p>
</div>
<div class="form-group">
    <label for="store-affiliate-url">Affiliate / tracking link</label>
    <input type="url" id="store-affiliate-url" name="affiliate_url" value="{{ old('affiliate_url', $store->affiliate_url ?? '') }}" placeholder="https://example.com/?ref=your-affiliate-id">
    <p class="form-hint">Used when visitors click <strong>Shop Now</strong> on coupons and deals. Not displayed publicly on the store page.</p>
</div>
