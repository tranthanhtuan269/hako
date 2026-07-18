<?php

namespace App\Support;

use Illuminate\Support\Str;

final class MerchantProductEnricher
{
    public function __construct(
        private readonly MerchantProductExtractor $extractor = new MerchantProductExtractor(),
    ) {}

    /**
     * @param  array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string, features?: list<string>}>  $products
     * @param  callable(string): ?string  $fetchHtml
     * @return array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string, features: list<string>}>
     */
    public function enrich(array $products, callable $fetchHtml, int $maxFetches = 5): array
    {
        $enriched = [];
        $fetches = 0;
        $usedImages = [];

        foreach ($products as $product) {
            $url = filled($product['url'] ?? null) ? (string) $product['url'] : null;

            if ($url && $fetches < $maxFetches) {
                $html = $fetchHtml($url);
                $fetches++;

                if ($html) {
                    $product = $this->extractor->enrichFromPage($html, $product, $url, $usedImages);
                }
            }

            // Drop listing/AI images that collide with another product already chosen.
            if (filled($product['image'] ?? null)
                && $this->extractor->imageUrlIsExcluded((string) $product['image'], $usedImages)) {
                $product['image'] = null;
            }

            if (filled($product['image'] ?? null)) {
                $usedImages[] = (string) $product['image'];
            }

            $product['features'] = is_array($product['features'] ?? null) ? $product['features'] : [];

            $enriched[] = $product;
        }

        return $this->dedupeSharedFeatures($enriched);
    }

    /**
     * Remove feature bullets that appear on 2+ products (almost always theme/nav USPs).
     * Prefer description-derived bullets when a product ends up with an empty unique set.
     *
     * @param  array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string, features: list<string>}>  $products
     * @return array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string, features: list<string>}>
     */
    private function dedupeSharedFeatures(array $products): array
    {
        $counts = [];

        foreach ($products as $product) {
            foreach ($product['features'] as $feature) {
                $key = Str::lower(trim((string) $feature));
                if ($key === '') {
                    continue;
                }
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        foreach ($products as &$product) {
            $unique = [];

            foreach ($product['features'] as $feature) {
                $key = Str::lower(trim((string) $feature));
                if ($key === '' || ($counts[$key] ?? 0) >= 2) {
                    continue;
                }
                $unique[] = (string) $feature;
            }

            if ($unique === [] && filled($product['description'] ?? null)) {
                $unique = $this->extractor->featuresFromDescription((string) $product['description']);
                // Still drop anything that was shared sitewide.
                $unique = array_values(array_filter(
                    $unique,
                    fn (string $feature) => ($counts[Str::lower(trim($feature))] ?? 0) < 2
                ));
            }

            $product['features'] = array_values(array_unique($unique));
        }
        unset($product);

        return $products;
    }
}
