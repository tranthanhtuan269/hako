<?php

namespace Tests\Unit;

use App\Support\Seo;
use PHPUnit\Framework\TestCase;

class SeoTest extends TestCase
{
    public function test_description_decodes_html_entities(): void
    {
        $this->assertSame('Save 10% & more', Seo::description('Save 10% &amp; more'));
        $this->assertSame('Bed Bath & Beyond deals', Seo::description('Bed Bath &amp;amp; Beyond deals'));
    }
}
