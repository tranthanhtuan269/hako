<?php

namespace App\Support;

use Illuminate\Support\Str;

final class MerchantProductExtractor
{
    /**
     * @return array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>
     */
    public function extract(?string $html, string $baseUrl): array
    {
        if (! $html) {
            return [];
        }

        $products = [];

        $products = array_merge($products, $this->fromJsonLd($html, $baseUrl));
        $products = array_merge($products, $this->fromProductLinks($html, $baseUrl));

        return $this->uniqueTake($products, 3);
    }

    /**
     * @param  array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>  $products
     * @return array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>
     */
    public function uniqueTake(array $products, int $limit = 3): array
    {
        $seen = [];
        $unique = [];

        foreach ($products as $product) {
            $name = trim($product['name'] ?? '');

            if ($name === '' || strlen($name) < 3 || $this->isJunkProductName($name)) {
                continue;
            }

            $key = Str::lower($name);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = [
                'name' => Str::limit(HtmlCleaner::decodeEntities($name), 120),
                'description' => filled($product['description'] ?? null)
                    ? Str::limit(HtmlCleaner::textFromHtml((string) $product['description']), 500)
                    : null,
                'price' => filled($product['price'] ?? null)
                    ? Str::limit(strip_tags((string) $product['price']), 40)
                    : null,
                'image' => $product['image'] ?? null,
                'url' => $product['url'] ?? null,
                'features' => is_array($product['features'] ?? null) ? $product['features'] : [],
            ];

            if (count($unique) >= $limit) {
                break;
            }
        }

        return $unique;
    }

    /**
     * @param  array{name: string, description: ?string, price: ?string, image: ?string, url: ?string, features?: list<string>}  $product
     * @return array{name: string, description: ?string, price: ?string, image: ?string, url: ?string, features: list<string>}
     */
    public function enrichFromPage(string $html, array $product, string $baseUrl): array
    {
        $extracted = $this->extract($html, $baseUrl);
        $detail = $extracted[0] ?? null;

        if (is_array($detail)) {
            if (empty($product['description']) && filled($detail['description'] ?? null)) {
                $product['description'] = Str::limit(strip_tags((string) $detail['description']), 500);
            }

            if (empty($product['price']) && filled($detail['price'] ?? null)) {
                $product['price'] = $detail['price'];
            }

            if (empty($product['image']) && filled($detail['image'] ?? null)) {
                $product['image'] = $detail['image'];
            }

            if (empty($product['url']) && filled($detail['url'] ?? null)) {
                $product['url'] = $detail['url'];
            }
        }

        $features = $this->extractFeatures($html);

        if ($features !== []) {
            $product['features'] = $features;
        } elseif (! isset($product['features'])) {
            $product['features'] = [];
        }

        return $product;
    }

    /** @return list<string> */
    private function extractFeatures(string $html): array
    {
        $features = [];

        if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $html, $matches)) {
            foreach ($matches[1] as $item) {
                $text = HtmlCleaner::textFromHtml($item);

                if ($text === '' || strlen($text) < 8 || strlen($text) > 180) {
                    continue;
                }

                if (preg_match('/^(home|shop|cart|login|sign in|subscribe|menu|search)$/i', $text)) {
                    continue;
                }

                $features[] = $text;

                if (count($features) >= 8) {
                    break;
                }
            }
        }

        return array_values(array_unique($features));
    }

    private function isJunkProductName(string $name): bool
    {
        return (bool) preg_match(
            '/^(shop|products|product|buy now|learn more|view all|add to cart|sale|new arrivals?|best sellers?|collections?)$/i',
            trim($name)
        );
    }

    /**
     * @return array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>
     */
    private function fromJsonLd(string $html, string $baseUrl): array
    {
        $products = [];

        if (! preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $blocks)) {
            return [];
        }

        foreach ($blocks[1] as $json) {
            $decoded = json_decode(html_entity_decode(trim($json)), true);

            if (is_array($decoded)) {
                $products = array_merge($products, $this->productsFromSchema($decoded, $baseUrl));
            }
        }

        return $products;
    }

    /**
     * @return array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>
     */
    private function productsFromSchema(mixed $data, string $baseUrl): array
    {
        if (! is_array($data)) {
            return [];
        }

        if (isset($data['@graph']) && is_array($data['@graph'])) {
            $merged = [];

            foreach ($data['@graph'] as $node) {
                $merged = array_merge($merged, $this->productsFromSchema($node, $baseUrl));
            }

            return $merged;
        }

        if (isset($data['@type']) && is_array($data)) {
            $types = is_array($data['@type']) ? $data['@type'] : [$data['@type']];

            if (in_array('ItemList', $types, true) && ! empty($data['itemListElement']) && is_array($data['itemListElement'])) {
                $merged = [];

                foreach ($data['itemListElement'] as $item) {
                    if (is_array($item) && isset($item['item'])) {
                        $merged = array_merge($merged, $this->productsFromSchema($item['item'], $baseUrl));
                    } else {
                        $merged = array_merge($merged, $this->productsFromSchema($item, $baseUrl));
                    }
                }

                return $merged;
            }

            if (in_array('Product', $types, true) || in_array('ProductGroup', $types, true)) {
                $product = $this->normalizeProductNode($data, $baseUrl);

                return $product ? [$product] : [];
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $nested = $this->productsFromSchema($value, $baseUrl);

                if ($nested !== []) {
                    return $nested;
                }
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}|null
     */
    private function normalizeProductNode(array $node, string $baseUrl): ?array
    {
        $name = $node['name'] ?? $node['alternateName'] ?? null;

        if (! is_string($name) || trim($name) === '') {
            return null;
        }

        $description = is_string($node['description'] ?? null) ? $node['description'] : null;
        $url = is_string($node['url'] ?? null) ? $this->absolutizeUrl($baseUrl, $node['url']) : null;

        $image = null;
        if (is_string($node['image'] ?? null)) {
            $image = $this->absolutizeUrl($baseUrl, $node['image']);
        } elseif (is_array($node['image'] ?? null)) {
            $firstImage = $node['image'][0] ?? null;
            if (is_string($firstImage)) {
                $image = $this->absolutizeUrl($baseUrl, $firstImage);
            } elseif (is_array($firstImage) && is_string($firstImage['url'] ?? null)) {
                $image = $this->absolutizeUrl($baseUrl, $firstImage['url']);
            }
        }

        $price = null;
        $offers = $node['offers'] ?? null;

        if (is_array($offers)) {
            if (isset($offers['price'])) {
                $currency = is_string($offers['priceCurrency'] ?? null) ? $offers['priceCurrency'] : 'USD';
                $price = $currency . ' ' . $offers['price'];
            } elseif (isset($offers[0]) && is_array($offers[0]) && isset($offers[0]['price'])) {
                $currency = is_string($offers[0]['priceCurrency'] ?? null) ? $offers[0]['priceCurrency'] : 'USD';
                $price = $currency . ' ' . $offers[0]['price'];
            }
        }

        return [
            'name' => HtmlCleaner::decodeEntities(trim($name)),
            'description' => is_string($description) ? HtmlCleaner::decodeEntities($description) : null,
            'price' => $price,
            'image' => $image,
            'url' => $url,
        ];
    }

    /**
     * @return array<int, array{name: string, description: ?string, price: ?string, image: ?string, url: ?string}>
     */
    private function fromProductLinks(string $html, string $baseUrl): array
    {
        $products = [];

        if (! preg_match_all('/<a[^>]+href=["\']([^"\']*(?:\/products\/|\/product\/|\/p\/)[^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($matches as $match) {
            $href = html_entity_decode($match[1]);
            $text = HtmlCleaner::textFromHtml($match[2]);

            if ($text === '' || strlen($text) < 3 || strlen($text) > 120) {
                continue;
            }

            if (preg_match('/^(shop|products|buy|learn more|view all|add to cart|sale)$/i', $text)) {
                continue;
            }

            if ($this->isJunkProductName($text)) {
                continue;
            }

            $products[] = [
                'name' => $text,
                'description' => null,
                'price' => null,
                'image' => null,
                'url' => $this->absolutizeUrl($baseUrl, $href),
            ];
        }

        return $products;
    }

    private function absolutizeUrl(string $baseUrl, string $path): string
    {
        $path = trim($path);

        if ($path === '' || preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $parts = parse_url($baseUrl);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';

        if (str_starts_with($path, '//')) {
            return $scheme . ':' . $path;
        }

        if (str_starts_with($path, '/')) {
            return "{$scheme}://{$host}{$path}";
        }

        $dir = rtrim(dirname($parts['path'] ?? '/'), '/');

        return "{$scheme}://{$host}{$dir}/{$path}";
    }
}
