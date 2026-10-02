<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Models\Store;
use Illuminate\Support\Collection;

final class SiteEditorsPicks
{
    public const SETTING_KEY = 'editors_picks_store_ids';
    public const DEFAULT_LIMIT = 5;

    /**
     * @return list<int>
     */
    public static function configuredStoreIds(): array
    {
        $raw = SiteSetting::get(self::SETTING_KEY);
        if (!$raw) {
            return [];
        }

        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $decoded)));
    }

    /**
     * @param list<int> $ids
     */
    public static function setStoreIds(array $ids): void
    {
        $clean = array_values(array_unique(array_filter(array_map('intval', $ids))));
        SiteSetting::set(self::SETTING_KEY, json_encode($clean));
    }

    /**
     * Selected stores in the exact order configured by admin.
     *
     * @return Collection<int, Store>
     */
    public static function selectedStores(): Collection
    {
        $ids = self::configuredStoreIds();
        if (empty($ids)) {
            return collect();
        }

        $stores = Store::query()
            ->whereIn('id', $ids)
            ->withCount(['coupons as active_coupons_count' => function ($query) {
                $query->valid();
            }])
            ->get()
            ->keyBy('id');

        $ordered = collect();
        foreach ($ids as $id) {
            if ($stores->has($id)) {
                $ordered->push($stores->get($id));
            }
        }

        return $ordered;
    }

    /**
     * Stores for the homepage Editor's Picks section.
     * Fallback to homeFeaturedQuery if nothing configured.
     *
     * @return Collection<int, Store>
     */
    public static function forHome(int $limit = 5): Collection
    {
        $selected = self::selectedStores();
        if ($selected->isNotEmpty()) {
            return $selected->take($limit);
        }

        return Store::homeFeaturedQuery($limit)
            ->withCount(['coupons as active_coupons_count' => function ($query) {
                $query->valid();
            }])
            ->get();
    }
}
