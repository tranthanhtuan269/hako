@php
    $fromSaved = $fromSaved ?? false;
    $savedAt = $savedAt ?? null;
@endphp
<div class="import-card keyword-results-card" style="margin-top:1.5rem;" id="keyword-results">
    <div class="import-card-header">
        <h2>Generated Keywords ({{ count($result['all']) }})</h2>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <button type="button" class="btn btn-outline btn-sm copy-all-btn">Copy all</button>
            <a href="#" class="btn btn-outline btn-sm download-txt-btn">Download .txt</a>
        </div>
    </div>
    @if($fromSaved && $savedAt)
        <p class="alert alert-success" style="margin-bottom:1rem;padding:.65rem .85rem;font-size:.9rem;">
            Loaded saved keyword set (last updated {{ $savedAt }}). Edit products and click Generate to replace.
        </p>
    @endif
    <p class="form-hint">
        Brand: <strong>{{ $brandLabel }}</strong> —
        {{ count($result['brand']) }} brand keywords
        @if(count($result['by_product']) > 0)
            + {{ count($result['by_product']) }} product(s) × {{ count($engine->productTemplates()) }} each
        @endif
    </p>

    <h3 style="font-size:1rem;margin:1.25rem 0 .5rem;">Brand keywords</h3>
    <ul class="keyword-result-list">
        @foreach($result['brand'] as $keyword)
            <li>{{ $keyword }}</li>
        @endforeach
    </ul>

    @foreach($result['by_product'] as $product => $keywords)
        <h3 style="font-size:1rem;margin:1.25rem 0 .5rem;">{{ $brandLabel }} — {{ $product }}</h3>
        <ul class="keyword-result-list">
            @foreach($keywords as $keyword)
                <li>{{ $keyword }}</li>
            @endforeach
        </ul>
    @endforeach

    <h3 style="font-size:1rem;margin:1.25rem 0 .5rem;">All keywords (one per line)</h3>
    <textarea class="keyword-export-area keywords-export" readonly rows="12">{{ collect($result['all'])->map(fn ($k) => '"' . str_replace('"', '\"', $k) . '"')->implode("\n") }}</textarea>
</div>
