<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

class NavLink extends Model
{
    protected $fillable = [
        'type',
        'label',
        'url',
        'sort_order',
        'is_active',
        'open_in_new_tab',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'open_in_new_tab' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function headerItems()
    {
        try {
            if (! Schema::hasTable('nav_links')) {
                return collect();
            }

            return static::query()
                ->where('type', 'header')
                ->active()
                ->ordered()
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    public function resolvedUrl(): string
    {
        $url = trim((string) $this->url);

        if ($url === '' || $url === '#') {
            return '#';
        }

        if (preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) {
            return $url;
        }

        if (! str_starts_with($url, '/')) {
            $url = '/'.$url;
        }

        return url($url);
    }

    public function isCurrent(): bool
    {
        $target = $this->resolvedUrl();

        if ($target === '#') {
            return false;
        }

        $parts = parse_url($target);
        $targetPath = trim($parts['path'] ?? '/', '/') ?: '/';
        $currentPath = trim('/'.request()->path(), '/') ?: '/';

        if ($targetPath !== $currentPath) {
            return false;
        }

        parse_str($parts['query'] ?? '', $want);

        if ($want === []) {
            return request()->query() === [];
        }

        foreach ($want as $key => $value) {
            if ((string) request()->query($key) !== (string) $value) {
                return false;
            }
        }

        return true;
    }
}
