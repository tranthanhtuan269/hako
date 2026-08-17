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

    public function test_strip_affiliate_notices_removes_english_and_vietnamese_disclaimers(): void
    {
        $html = '<p>HonestReviews24h is an independent coupon and deals site. We are not affiliated with, endorsed by, or sponsored by retailers mentioned on this site unless stated otherwise. Trademarks belong to their respective owners. <a href="/disclaimer">Affiliate disclosure</a></p>'
            .'<figure><img src="https://example.com/banner.jpg" alt="Store banner"></figure>'
            .'<p>Shop current Hairsoflyshop deals below.</p>';

        $cleaned = HtmlCleaner::stripAffiliateNotices($html);

        $this->assertStringNotContainsString('independent coupon and deals site', $cleaned);
        $this->assertStringNotContainsString('Affiliate disclosure', $cleaned);
        $this->assertStringContainsString('Shop current Hairsoflyshop deals below.', $cleaned);

        $vietnamese = '<p>honestreviews24h.com là một trang web cung cấp mã giảm giá và ưu đãi độc lập. Chúng tôi không liên kết, không được chứng thực hoặc tài trợ bởi các nhà bán lẻ được đề cập trên trang web này trừ khi có tuyên bố khác. Thương hiệu thuộc sở hữu của chủ sở hữu tương ứng. Thông báo về chương trình liên kết</p>'
            .'<p>Keep this paragraph.</p>';

        $this->assertSame('<p>Keep this paragraph.</p>', HtmlCleaner::stripAffiliateNotices($vietnamese));
    }
}
