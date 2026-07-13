<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\Post;
use App\Models\Store;
use Illuminate\Support\Collection;

final class SiteDomainMigrator
{
    /** @var list<string> */
    private const POST_FIELDS = [
        'title',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
        'featured_image',
    ];

    /** @var list<string> */
    private const STORE_FIELDS = [
        'name',
        'logo',
        'website',
        'affiliate_url',
        'description',
    ];

    /** @var list<string> */
    private const COUPON_FIELDS = [
        'title',
        'description',
        'affiliate_url',
    ];

    /**
     * @return array{
     *     posts: array{scanned: int, updated: int, replacements: int},
     *     stores: array{scanned: int, updated: int, replacements: int},
     *     coupons: array{scanned: int, updated: int, replacements: int},
     *     total_replacements: int
     * }
     */
    public function migrate(string $oldDomain, string $newDomain): array
    {
        $summary = [
            'posts' => ['scanned' => 0, 'updated' => 0, 'replacements' => 0],
            'stores' => ['scanned' => 0, 'updated' => 0, 'replacements' => 0],
            'coupons' => ['scanned' => 0, 'updated' => 0, 'replacements' => 0],
            'total_replacements' => 0,
        ];

        Post::query()
            ->select(array_merge(['id'], self::POST_FIELDS))
            ->orderBy('id')
            ->chunkById(100, function (Collection $posts) use ($oldDomain, $newDomain, &$summary): void {
                foreach ($posts as $post) {
                    $summary['posts']['scanned']++;
                    $result = $this->applyToModel($post, self::POST_FIELDS, $oldDomain, $newDomain);

                    if ($result['updated']) {
                        $summary['posts']['updated']++;
                    }

                    $summary['posts']['replacements'] += $result['replacements'];
                }
            });

        Store::query()
            ->select(array_merge(['id'], self::STORE_FIELDS))
            ->orderBy('id')
            ->chunkById(100, function (Collection $stores) use ($oldDomain, $newDomain, &$summary): void {
                foreach ($stores as $store) {
                    $summary['stores']['scanned']++;
                    $result = $this->applyToModel($store, self::STORE_FIELDS, $oldDomain, $newDomain);

                    if ($result['updated']) {
                        $summary['stores']['updated']++;
                    }

                    $summary['stores']['replacements'] += $result['replacements'];
                }
            });

        Coupon::query()
            ->select(array_merge(['id'], self::COUPON_FIELDS))
            ->orderBy('id')
            ->chunkById(100, function (Collection $coupons) use ($oldDomain, $newDomain, &$summary): void {
                foreach ($coupons as $coupon) {
                    $summary['coupons']['scanned']++;
                    $result = $this->applyToModel($coupon, self::COUPON_FIELDS, $oldDomain, $newDomain);

                    if ($result['updated']) {
                        $summary['coupons']['updated']++;
                    }

                    $summary['coupons']['replacements'] += $result['replacements'];
                }
            });

        $summary['total_replacements'] = $summary['posts']['replacements']
            + $summary['stores']['replacements']
            + $summary['coupons']['replacements'];

        return $summary;
    }

    /**
     * @param  list<string>  $fields
     * @return array{updated: bool, replacements: int}
     */
    private function applyToModel(Post|Store|Coupon $model, array $fields, string $oldDomain, string $newDomain): array
    {
        $changes = [];
        $replacements = 0;

        foreach ($fields as $field) {
            $value = $model->{$field};

            if (! is_string($value) || $value === '') {
                continue;
            }

            $result = DomainContentReplacer::replace($value, $oldDomain, $newDomain);

            if ($result['count'] === 0 || $result['text'] === $value) {
                continue;
            }

            $changes[$field] = $result['text'];
            $replacements += $result['count'];
        }

        if ($changes === []) {
            return ['updated' => false, 'replacements' => 0];
        }

        $model->update($changes);

        return ['updated' => true, 'replacements' => $replacements];
    }
}
