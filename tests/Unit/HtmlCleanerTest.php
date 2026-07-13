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
}
