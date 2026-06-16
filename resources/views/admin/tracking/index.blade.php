@extends('layouts.admin')

@section('title', 'Tracking Scripts')

@section('content')
<h1 style="margin-bottom:.5rem;">Tracking Scripts</h1>
<p style="color:#64748b;margin-bottom:2rem;">
    Manage Google tag and per-page conversion tracking for the public site.
</p>

<form action="{{ route('admin.tracking.update') }}" method="POST" id="tracking-form">
    @csrf
    @method('PUT')

    <div class="tracking-section">
        <h2>Global tag (all public pages)</h2>
        <p class="form-hint" style="margin-bottom:.75rem;">
            Paste your Google tag (gtag.js) snippet. Injected immediately after <code>&lt;head&gt;</code> on every public page.
        </p>
        <div class="form-group">
            <label for="tracking_head">Google tag snippet</label>
            <textarea
                id="tracking_head"
                name="tracking_head"
                rows="12"
                class="tracking-code-input"
                placeholder="<!-- Google tag (gtag.js) -->&#10;&lt;script async src=&quot;https://www.googletagmanager.com/gtag/js?id=AW-XXXXXXXX&quot;&gt;&lt;/script&gt;&#10;..."
            >{{ old('tracking_head', $trackingHead) }}</textarea>
            @error('tracking_head')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="tracking-section">
        <div class="tracking-section-head">
            <div>
                <h2>Conversion events (per page)</h2>
                <p class="form-hint" style="margin:.35rem 0 0;">
                    Add one row per page or section. Each row has its own page link and conversion snippet.
                    Use <code>/</code> for homepage, <code>/coupons/*</code> for all coupon pages, or an exact path like <code>/stores/jennibag</code>.
                </p>
            </div>
            <button type="button" class="btn btn-outline" id="add-conversion-rule">+ Add page</button>
        </div>

        <div id="conversion-rules-list" class="conversion-rules-list">
            @php($rules = old('conversion_rules', $conversionRules))
            @foreach($rules as $index => $rule)
                @include('admin.tracking.partials.conversion-rule-row', ['index' => $index, 'rule' => $rule])
            @endforeach
        </div>
        @error('conversion_rules')<p class="form-error">{{ $message }}</p>@enderror
        @error('conversion_rules.*.path')<p class="form-error">{{ $message }}</p>@enderror
        @error('conversion_rules.*.html')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary">Save Scripts</button>
        <a href="{{ route('home') }}" class="btn btn-outline" target="_blank" rel="noopener">View public site →</a>
    </div>
</form>

<template id="conversion-rule-template">
    @include('admin.tracking.partials.conversion-rule-row', ['index' => '__INDEX__', 'rule' => ['path' => '', 'html' => '']])
</template>
@endsection

@push('styles')
<style>
.tracking-section {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem 1.35rem;
    margin-bottom: 1.25rem;
}
.tracking-section h2 {
    margin: 0 0 .35rem;
    font-size: 1.05rem;
}
.tracking-section-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}
.tracking-code-input {
    width: 100%;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: .85rem;
    line-height: 1.45;
    padding: .85rem 1rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: #0f172a;
    color: #e2e8f0;
    resize: vertical;
    min-height: 180px;
}
.tracking-code-input--compact {
    min-height: 140px;
}
.tracking-code-input:focus {
    outline: 2px solid var(--primary);
    outline-offset: 1px;
}
.conversion-rules-list {
    display: grid;
    gap: 1rem;
}
.conversion-rule-row {
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 1rem;
    background: #f8fafc;
}
.conversion-rule-row-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .75rem;
    margin-bottom: .75rem;
}
.conversion-rule-row-head strong {
    font-size: .92rem;
}
.conversion-rule-path {
    width: 100%;
    padding: .55rem .7rem;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: .88rem;
    margin-bottom: .65rem;
}
.form-hint code {
    font-size: .82em;
}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const list = document.getElementById('conversion-rules-list');
    const template = document.getElementById('conversion-rule-template');
    const addBtn = document.getElementById('add-conversion-rule');
    let nextIndex = list.querySelectorAll('.conversion-rule-row').length;

    addBtn.addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
        list.insertAdjacentHTML('beforeend', html);
        renumberRows();
    });

    list.addEventListener('click', (event) => {
        if (!event.target.classList.contains('remove-conversion-rule')) {
            return;
        }

        const rows = list.querySelectorAll('.conversion-rule-row');
        if (rows.length <= 1) {
            const row = rows[0];
            row.querySelector('.conversion-rule-path').value = '';
            row.querySelector('.tracking-code-input').value = '';
            return;
        }

        event.target.closest('.conversion-rule-row').remove();
        renumberRows();
    });

    function renumberRows() {
        list.querySelectorAll('.conversion-rule-row').forEach((row, index) => {
            row.querySelector('.conversion-rule-number').textContent = index + 1;
            row.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace(/conversion_rules\[\d+\]/, `conversion_rules[${index}]`);
            });
        });
        nextIndex = list.querySelectorAll('.conversion-rule-row').length;
    }
})();
</script>
@endpush
