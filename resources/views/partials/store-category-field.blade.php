<div class="form-group">
    <label for="store-category">Category</label>
    <select name="category_id" id="store-category">
        <option value="">— None —</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $store->category_id) == $category->id)>
                {{ $category->icon ? $category->icon . ' ' : '' }}{{ $category->name }}
            </option>
        @endforeach
    </select>
    <p class="form-hint">Coupons from this store appear under the selected category on the site.</p>
</div>
