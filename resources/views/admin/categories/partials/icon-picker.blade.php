@php
    $selectedIcon = old('icon', $category->icon ?? '');
    $iconOptions = $iconOptions ?? [];
@endphp
<div class="form-group category-icon-field">
    <label>Category icon</label>
    <input type="hidden" name="icon" id="icon" value="{{ $selectedIcon }}">
    <div class="category-icon-preview" id="category-icon-preview" @if(!$selectedIcon) hidden @endif>
        <span class="category-icon-preview-emoji" id="category-icon-preview-emoji">{{ $selectedIcon }}</span>
        <span class="form-hint" id="category-icon-preview-label">Selected icon</span>
        <button type="button" class="btn btn-outline btn-sm" id="category-icon-clear">Clear</button>
    </div>
    <p class="form-hint" style="margin:.5rem 0 .75rem;">Click an icon below. Optional — used on homepage and category chips.</p>
    <div class="category-icon-grid" role="listbox" aria-label="Choose category icon">
        @foreach($iconOptions as $option)
            <button
                type="button"
                class="category-icon-option @if($selectedIcon === $option['value']) is-selected @endif"
                data-icon="{{ $option['value'] }}"
                title="{{ $option['label'] }}"
                aria-label="{{ $option['label'] }}"
                role="option"
                aria-selected="{{ $selectedIcon === $option['value'] ? 'true' : 'false' }}"
            >
                <span class="category-icon-option-emoji">{{ $option['value'] }}</span>
                <span class="category-icon-option-label">{{ $option['label'] }}</span>
            </button>
        @endforeach
    </div>
    @error('icon')<p class="form-error">{{ $message }}</p>@enderror
</div>
