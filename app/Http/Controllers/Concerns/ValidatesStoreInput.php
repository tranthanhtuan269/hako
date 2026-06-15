<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Store;
use App\Support\HtmlCleaner;
use App\Support\StoreSlug;
use Illuminate\Http\Request;
trait ValidatesStoreInput
{
    protected function validatedStore(Request $request, ?Store $store = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'logo' => ['nullable', 'string', 'max:500'],
            'logo_file' => ['nullable', 'image', 'max:2048'],
            'website' => ['nullable', 'url', 'max:500'],
            'affiliate_url' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ], [
            'slug.regex' => 'Slug may only contain lowercase letters, numbers, and hyphens.',
        ]);

        $data['slug'] = StoreSlug::make($data['name'], $request->input('slug'), $store?->id);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['category_id'] = filled($data['category_id'] ?? null) ? $data['category_id'] : null;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['description'] = HtmlCleaner::clean($data['description'] ?? null);

        unset($data['logo_file']);

        return $data;
    }
}
