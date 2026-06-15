<?php

namespace App\Support;

use Illuminate\Support\Str;

final class KeywordGenerationEngine
{
    /** @var list<string> */
    private const BRAND_TEMPLATES = [
        '{brand} coupon',
        '{brand} coupon code',
        '{brand} promo code',
        '{brand} discount code',
        '{brand} discounts',
        '{brand} deals',
        '{brand} offers',
    ];

    /** @var list<string> */
    private const PRODUCT_TEMPLATES = [
        '{brand} {product} coupon',
        '{brand} {product} coupon code',
        '{brand} {product} promo code',
        '{brand} {product} discount',
        '{brand} {product} discount code',
        '{brand} {product} sale',
        '{brand} {product} deals',
    ];

    /**
     * @param  list<string>  $products
     * @return array{
     *     brand: list<string>,
     *     by_product: array<string, list<string>>,
     *     all: list<string>
     * }
     */
    public function generate(string $brand, array $products): array
    {
        $brand = $this->normalizeToken($brand);

        if ($brand === '') {
            return ['brand' => [], 'by_product' => [], 'all' => []];
        }

        $brandKeywords = $this->brandKeywords($brand);
        $byProduct = [];
        $all = $brandKeywords;

        foreach ($products as $rawProduct) {
            $product = $this->normalizeToken($rawProduct);

            if ($product === '') {
                continue;
            }

            $productKeywords = $this->productKeywords($brand, $product);
            $byProduct[$product] = $productKeywords;
            $all = array_merge($all, $productKeywords);
        }

        return [
            'brand' => $brandKeywords,
            'by_product' => $byProduct,
            'all' => array_values(array_unique($all)),
        ];
    }

    /**
     * @return list<string>
     */
    public function brandTemplates(): array
    {
        return self::BRAND_TEMPLATES;
    }

    /**
     * @return list<string>
     */
    public function productTemplates(): array
    {
        return self::PRODUCT_TEMPLATES;
    }

    /**
     * @return list<string>
     */
    private function brandKeywords(string $brand): array
    {
        return array_map(
            fn (string $template) => $this->fill($template, $brand, null),
            self::BRAND_TEMPLATES
        );
    }

    /**
     * @return list<string>
     */
    private function productKeywords(string $brand, string $product): array
    {
        return array_map(
            fn (string $template) => $this->fill($template, $brand, $product),
            self::PRODUCT_TEMPLATES
        );
    }

    private function fill(string $template, string $brand, ?string $product): string
    {
        $keyword = str_replace('{brand}', $brand, $template);

        if ($product !== null) {
            $keyword = str_replace('{product}', $product, $keyword);
        }

        return $keyword;
    }

    private function normalizeToken(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return Str::lower($value);
    }

}
