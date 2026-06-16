<div class="conversion-rule-row">
    <div class="conversion-rule-row-head">
        <strong>Page #<span class="conversion-rule-number">{{ is_numeric($index) ? $index + 1 : 1 }}</span></strong>
        <button type="button" class="btn btn-outline remove-conversion-rule">Remove</button>
    </div>
    <label class="form-hint" style="display:block;margin-bottom:.35rem;">Page link / path</label>
    <input
        type="text"
        class="conversion-rule-path"
        name="conversion_rules[{{ $index }}][path]"
        value="{{ old("conversion_rules.$index.path", $rule['path'] ?? '') }}"
        placeholder="/coupons/* or /stores/jennibag or /"
    >
    <label class="form-hint" style="display:block;margin:.35rem 0;">Conversion snippet for this page</label>
    <textarea
        name="conversion_rules[{{ $index }}][html]"
        rows="10"
        class="tracking-code-input tracking-code-input--compact"
        placeholder="<!-- Event snippet -->&#10;&lt;script&gt;&#10;function gtag_report_conversion(url) { ... }&#10;&lt;/script&gt;"
    >{{ old("conversion_rules.$index.html", $rule['html'] ?? '') }}</textarea>
</div>
