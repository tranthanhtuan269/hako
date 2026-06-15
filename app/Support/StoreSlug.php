<?php

namespace App\Support;

use App\Models\Store;
use Illuminate\Support\Str;

final class StoreSlug
{
    public static function make(string $name, ?string $preferred = null, ?int $ignoreId = null): string
    {
        $slug = Str::slug($preferred ?: $name);

        if ($slug === '') {
            $slug = Str::slug($name) ?: 'store';
        }

        $original = $slug;
        $suffix = 1;

        while (Store::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $original.'-'.$suffix++;
        }

        return $slug;
    }
}
