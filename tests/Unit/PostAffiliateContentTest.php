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
