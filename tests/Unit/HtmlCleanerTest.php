<?php

namespace Tests\Unit;

use App\Support\HtmlCleaner;
use PHPUnit\Framework\TestCase;

class HtmlCleanerTest extends TestCase
{
    public function test_decode_entities_handles_double_encoding(): void
    {
        $this->assertSame('Save 10% & More', HtmlCleaner::decodeEntities('Save 10% &amp; More'));
        $this->assertSame('Save 10% & More', HtmlCleaner::decodeEntities('Save 10% &amp;amp; More'));
    }

    public function test_normalize_plain_text_decodes_and_trims(): void
    {
        $this->assertSame('Bed Bath & Beyond', HtmlCleaner::normalizePlainText('  Bed Bath &amp; Beyond  '));
    }

    public function test_ensure_full_width_images_sets_width_on_content_images(): void
    {
        $html = '<p>Hello</p><figure class="article-media article-media--product"><img src="https://cdn.example.com/product.jpg" alt="Serum" width="320" height="240"></figure>';

        $result = HtmlCleaner::ensureFullWidthImages($html);

        $this->assertNotNull($result);
        $this->assertStringContainsString('width="100%"', $result);
        $this->assertStringContainsString('width: 100%', $result);
        $this->assertStringContainsString('height: auto', $result);
        $this->assertStringNotContainsString('width="320"', $result);
        $this->assertStringNotContainsString('height="240"', $result);
    }

    public function test_ensure_full_width_images_skips_logo_figures(): void
    {
        $html = '<figure class="article-media article-media--logo"><img src="https://cdn.example.com/logo.png" alt="Logo" width="160"></figure>';

        $result = HtmlCleaner::ensureFullWidthImages($html);

        $this->assertNotNull($result);
        $this->assertStringContainsString('width="160"', $result);
        $this->assertStringNotContainsString('width="100%"', $result);
    }

    public function test_clean_also_forces_full_width_images(): void
    {
        $html = '<p><img src="https://cdn.example.com/banner.jpg" alt="Banner" width="800"></p>';

        $result = HtmlCleaner::clean($html);

        $this->assertNotNull($result);
        $this->assertStringContainsString('width="100%"', $result);
    }
}
