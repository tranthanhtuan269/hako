<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CouponQuerySort
{
    /**
     * @return array{query: Builder, sort: string, dir: string}
     */
    public static function apply(Builder $query, Request $request, string $default = 'clicks'): array
    {
        $sort = (string) $request->query('sort', $default);
        $dir = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        return match ($sort) {
            'clicks' => [
                'query' => $query->orderBy('click_count', $dir)->orderByDesc('id'),
                'sort' => 'clicks',
                'dir' => $dir,
            ],
            'title' => [
                'query' => $query->orderBy('title', $dir)->orderByDesc('id'),
                'sort' => 'title',
                'dir' => $dir,
            ],
            default => [
                'query' => $query->latest(),
                'sort' => 'latest',
                'dir' => 'desc',
            ],
        };
    }
}
