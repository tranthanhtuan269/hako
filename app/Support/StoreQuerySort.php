<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StoreQuerySort
{
    /**
     * @return array{query: Builder, sort: string, dir: string}
     */
    public static function apply(Builder $query, Request $request, string $default = 'order'): array
    {
        $sort = (string) $request->query('sort', $default);
        $dir = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = $query
            ->withCount('coupons')
            ->withSum('coupons as coupons_click_sum', 'click_count');

        return match ($sort) {
            'name' => [
                'query' => $query->orderBy('name', $dir)->orderBy('id'),
                'sort' => 'name',
                'dir' => $dir,
            ],
            'coupons' => [
                'query' => $query->orderBy('coupons_count', $dir)->orderBy('name'),
                'sort' => 'coupons',
                'dir' => $dir,
            ],
            'created_at' => [
                'query' => $query->orderBy('created_at', $dir)->orderBy('name'),
                'sort' => 'created_at',
                'dir' => $dir,
            ],
            'clicks' => [
                'query' => $query->orderBy('coupons_click_sum', $dir)->orderBy('name'),
                'sort' => 'clicks',
                'dir' => $dir,
            ],
            default => [
                'query' => $query
                    ->orderByDesc('stores_list_sort_order')
                    ->orderBy('sort_order')
                    ->orderBy('name'),
                'sort' => 'order',
                'dir' => 'desc',
            ],
        };
    }
}
