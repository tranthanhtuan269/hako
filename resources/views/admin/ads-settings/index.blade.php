@extends('layouts.admin')

@section('title', 'Ads Settings')

@section('content')
<h1 style="margin-bottom:.5rem;">Ads Settings</h1>
<p style="color:#64748b;margin-bottom:2rem;">
    Global sitelinks and callouts for Google Ads Editor. These apply to every campaign when you export assets from
    <a href="{{ route('member.keywords.create') }}">Keyword Generator</a>.
</p>

<form action="{{ route('admin.ads-settings.update') }}" method="POST" id="ads-settings-form">
    @csrf
    @method('PUT')

    <div class="ads-settings-section">
        <div class="ads-settings-section-head">
            <div>
                <h2>Sitelinks</h2>
                <p class="form-hint" style="margin:.35rem 0 0;">
                    Link text max 25 characters. Description lines max 35 characters each (Google Ads limits).
                </p>
            </div>
            <button type="button" class="btn btn-outline" id="add-sitelink-row">+ Add sitelink</button>
        </div>

        <div id="sitelinks-list" class="ads-settings-rows">
            @php($sitelinks = old('sitelinks', $settings['sitelinks']))
            @foreach($sitelinks as $index => $sitelink)
                @include('admin.ads-settings.partials.sitelink-row', ['index' => $index, 'sitelink' => $sitelink, 'statuses' => $statuses])
            @endforeach
        </div>
    </div>

    <div class="ads-settings-section">
        <div class="ads-settings-section-head">
            <div>
                <h2>Callouts</h2>
                <p class="form-hint" style="margin:.35rem 0 0;">
                    Short annotations shown below your ads (max 25 characters each).
                </p>
            </div>
            <button type="button" class="btn btn-outline" id="add-callout-row">+ Add callout</button>
        </div>

        <div id="callouts-list" class="ads-settings-rows">
            @php($callouts = old('callouts', $settings['callouts']))
            @foreach($callouts as $index => $callout)
                @include('admin.ads-settings.partials.callout-row', ['index' => $index, 'callout' => $callout, 'statuses' => $statuses])
            @endforeach
        </div>
    </div>

    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary">Save settings</button>
        <button type="submit" class="btn btn-outline" formaction="{{ route('admin.ads-settings.reset') }}" formmethod="POST" onclick="return confirm('Reset to default site links and callouts?')">
            Reset defaults
        </button>
        <a href="{{ route('member.keywords.create') }}" class="btn btn-outline">Open Keyword Generator →</a>
    </div>
</form>

<template id="sitelink-row-template">
    @include('admin.ads-settings.partials.sitelink-row', ['index' => '__INDEX__', 'sitelink' => ['link_text' => '', 'final_url' => '', 'description_1' => '', 'description_2' => '', 'status' => 'Enabled'], 'statuses' => $statuses])
</template>

<template id="callout-row-template">
    @include('admin.ads-settings.partials.callout-row', ['index' => '__INDEX__', 'callout' => ['text' => '', 'status' => 'Enabled'], 'statuses' => $statuses])
</template>
@endsection

@push('styles')
<style>
.ads-settings-section {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem 1.35rem;
    margin-bottom: 1.25rem;
}
.ads-settings-section h2 {
    margin: 0;
    font-size: 1.05rem;
}
.ads-settings-section-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}
.ads-settings-rows {
    display: grid;
    gap: 1rem;
}
.ads-settings-row {
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 1rem;
    background: #f8fafc;
}
.ads-settings-row-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: .75rem;
}
.ads-settings-row-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: .75rem;
}
</style>
@endpush

@push('scripts')
<script>
(() => {
    function bindRepeater(listId, templateId, addBtnId, rowClass, removeClass) {
        const list = document.getElementById(listId);
        const template = document.getElementById(templateId);
        const addBtn = document.getElementById(addBtnId);
        let nextIndex = list.querySelectorAll('.' + rowClass).length;

        addBtn?.addEventListener('click', () => {
            list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(nextIndex++)));
            renumber(list, rowClass);
        });

        list.addEventListener('click', (event) => {
            if (!event.target.classList.contains(removeClass)) return;
            const rows = list.querySelectorAll('.' + rowClass);
            if (rows.length <= 1) {
                rows[0].querySelectorAll('input, select').forEach((el) => {
                    if (el.type === 'checkbox') el.checked = false;
                    else el.value = el.tagName === 'SELECT' ? 'Enabled' : '';
                });
                return;
            }
            event.target.closest('.' + rowClass).remove();
            renumber(list, rowClass);
        });

        function renumber(container, cls) {
            container.querySelectorAll('.' + cls).forEach((row, index) => {
                const label = row.querySelector('.ads-settings-row-number');
                if (label) label.textContent = index + 1;
                row.querySelectorAll('[name]').forEach((input) => {
                    input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
                });
            });
            nextIndex = container.querySelectorAll('.' + cls).length;
        }
    }

    bindRepeater('sitelinks-list', 'sitelink-row-template', 'add-sitelink-row', 'sitelink-row', 'remove-sitelink-row');
    bindRepeater('callouts-list', 'callout-row-template', 'add-callout-row', 'callout-row', 'remove-callout-row');
})();
</script>
@endpush
