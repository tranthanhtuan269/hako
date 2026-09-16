<?php

namespace Tests\Unit;

use App\Models\Store;
use App\Support\PostAffiliateContent;
use PHPUnit\Framework\TestCase;

class PostAffiliateContentTest extends TestCase
{
    public function test_rewrites_merchant_product_links_to_affiliate_url(): void
    {
        $store = new Store([
            'name' => 'Nike',
            'slug' => 'nike',
            'website' => 'https://www.nike.com',
            'affiliate_url' => 'https://go.example.com/click?id=123',
        ]);

        $html = '<p>See the <a href="https://www.nike.com/t/air-max">Air Max</a> on Nike.</p>'
            .'<p>Browse coupons on our <a href="/stores/nike">Nike deals page</a>.</p>';

        $out = PostAffiliateContent::embed($html, $store);

        $this->assertStringContainsString('https://go.example.com/click?id=123', $out);
        $this->assertStringNotContainsString('https://www.nike.com/t/air-max', $out);
        $this->assertStringContainsString('/stores/nike', $out);
        $this->assertStringContainsString('rel="nofollow sponsored"', $out);
        $this->assertStringContainsString('target="_blank"', $out);
    }

    public function test_wraps_product_url_inside_affiliate_destination_param(): void
    {
        $store = new Store([
            'name' => 'Nike',
            'slug' => 'nike',
            'website' => 'https://www.nike.com',
            'affiliate_url' => 'https://go.skimresources.com/?id=abc&url='.rawurlencode('https://www.nike.com'),
        ]);

        $tracked = PostAffiliateContent::trackedHref($store, 'https://www.nike.com/t/air-max');

        $this->assertStringContainsString('go.skimresources.com', $tracked);
        $this->assertStringContainsString('air-max', urldecode($tracked));
    }

    public function test_appends_ref_param_to_every_outbound_article_link(): void
    {
        $store = new Store([
            'name' => 'Ssimder',
            'slug' => 'ssimder',
            'website' => 'https://www.ssimder.com',
            'affiliate_url' => 'https://www.ssimder.com/?ref=ihwiqspt',
        ]);

        $html = '<p>Open the <a href="https://www.ssimder.com/products/sd-4050">SD-4050</a>.</p>'
            .'<p>Also see <a href="https://ssimder.com/collections/welders?color=red">welders</a>.</p>'
            .'<p>Browse coupons on our <a href="/stores/ssimder">Ssimder deals page</a>.</p>';

        $out = PostAffiliateContent::embed($html, $store);

        $this->assertStringContainsString('https://www.ssimder.com/products/sd-4050?ref=ihwiqspt', $out);
        $this->assertStringContainsString('collections/welders?color=red&amp;ref=ihwiqspt', $out);
        $this->assertStringContainsString('/stores/ssimder', $out);
        $this->assertStringNotContainsString('href="https://www.ssimder.com/products/sd-4050"', $out);
    }

    public function test_leaves_content_unchanged_without_affiliate_url(): void
    {
        $store = new Store([
            'name' => 'Nike',
            'slug' => 'nike',
            'website' => 'https://www.nike.com',
            'affiliate_url' => '',
        ]);

        $html = '<p><a href="https://www.nike.com/t/air-max">Air Max</a></p>';

        $this->assertSame($html, PostAffiliateContent::embed($html, $store));
    }
}
