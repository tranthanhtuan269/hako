<?php

namespace App\Support;

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
    public function enrich(array $products, callable $fetchHtml, int $maxFetches = 3): array
    {
        $enriched = [];
        $fetches = 0;

        foreach ($products as $product) {
            $url = filled($product['url'] ?? null) ? (string) $product['url'] : null;

            if ($url && $fetches < $maxFetches) {
                $html = $fetchHtml($url);
                $fetches++;

                if ($html) {
                    $product = $this->extractor->enrichFromPage($html, $product, $url);
                }
            }

            $product['features'] = is_array($product['features'] ?? null) ? $product['features'] : [];

            $enriched[] = $product;
        }

        return $enriched;
    }
}
