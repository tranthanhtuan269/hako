<?php

namespace Tests\Unit;

use App\Support\KeywordGenerationEngine;
use PHPUnit\Framework\TestCase;

class KeywordGenerationEngineTest extends TestCase
{
    public function test_generates_brand_and_product_keywords(): void
    {
        $engine = new KeywordGenerationEngine;

        $result = $engine->generate('MoveSpeed', ['SSD', 'power bank']);

        $this->assertCount(7, $result['brand']);
        $this->assertSame('movespeed coupon', $result['brand'][0]);
        $this->assertArrayHasKey('ssd', $result['by_product']);
        $this->assertSame('movespeed ssd coupon', $result['by_product']['ssd'][0]);
        $this->assertCount(21, $result['all']);
    }

    public function test_skips_empty_products(): void
    {
        $engine = new KeywordGenerationEngine;

        $result = $engine->generate('Acme', ['', '  ', 'widget']);

        $this->assertCount(14, $result['all']);
        $this->assertArrayHasKey('widget', $result['by_product']);
        $this->assertCount(1, $result['by_product']);
    }

}
