<?php

namespace Tests\Unit;

use App\Support\MerchantProductEnricher;
use App\Support\MerchantProductExtractor;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

class MerchantProductQualityTest extends TestCase
{
    public function test_unique_take_drops_junk_and_seo_blob_names(): void
    {
        $extractor = new MerchantProductExtractor();

        $products = $extractor->uniqueTake([
            ['name' => 'Wrinkle Reset Serum™', 'url' => 'https://example.com/products/wrinkle-reset-serum', 'image' => null, 'description' => null, 'price' => null],
            ['name' => 'View Ritual', 'url' => 'https://example.com/products/view', 'image' => null, 'description' => null, 'price' => null],
            ['name' => 'peptide skin care product', 'url' => 'https://example.com/products/peptide', 'image' => null, 'description' => null, 'price' => null],
            ['name' => 'best peptides for women', 'url' => 'https://example.com/products/best', 'image' => null, 'description' => null, 'price' => null],
            ['name' => 'hyaluronic acid and peptides', 'url' => 'https://example.com/products/ha', 'image' => null, 'description' => null, 'price' => null],
        ], 5);

        $this->assertSame(['Wrinkle Reset Serum™'], array_column($products, 'name'));
    }

    public function test_name_from_product_url_recovers_slug_title(): void
    {
        $extractor = new MerchantProductExtractor();

        $this->assertSame(
            'Body Firming Peptide Concentrate',
            $extractor->nameFromProductUrl('https://peptide-ritual.com/products/body-firming-peptide-concentrate')
        );
    }

    public function test_features_from_description_skips_sitewide_usps(): void
    {
        $extractor = new MerchantProductExtractor();

        $features = $extractor->featuresFromDescription(
            'Clinically Studied Peptide Formulas Free shipping on over $200+. This overnight mask firms skin while you sleep. Individual Products. Fast Delivery.'
        );

        $joined = Str::lower(implode(' | ', $features));

        $this->assertStringContainsString('overnight mask', $joined);
        $this->assertStringNotContainsString('clinically studied', $joined);
        $this->assertStringNotContainsString('individual products', $joined);
        $this->assertStringNotContainsString('fast delivery', $joined);
    }

    public function test_enricher_removes_features_shared_across_products(): void
    {
        $enricher = new MerchantProductEnricher();

        $products = $enricher->enrich([
            [
                'name' => 'Serum A',
                'description' => 'A peptide serum that targets fine lines around the eyes.',
                'price' => null,
                'image' => null,
                'url' => null,
                'features' => [
                    'Clinically Studied Peptide Formulas Free shipping on over $200+',
                    'Targets fine lines around the eyes',
                ],
            ],
            [
                'name' => 'Mask B',
                'description' => 'An overnight firming mask for denser-feeling skin.',
                'price' => null,
                'image' => null,
                'url' => null,
                'features' => [
                    'Clinically Studied Peptide Formulas Free shipping on over $200+',
                    'Overnight firming mask texture',
                ],
            ],
        ], fn () => null);

        $this->assertSame(['Targets fine lines around the eyes'], $products[0]['features']);
        $this->assertSame(['Overnight firming mask texture'], $products[1]['features']);
    }
}
