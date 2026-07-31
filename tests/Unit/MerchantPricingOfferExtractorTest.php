<?php

namespace Tests\Unit;

use App\Support\MerchantPricingOfferExtractor;
use PHPUnit\Framework\TestCase;

class MerchantPricingOfferExtractorTest extends TestCase
{
    public function test_extracts_free_trial_and_annual_save_percent(): void
    {
        $html = <<<'HTML'
            <html><body>
                <button>Monthly</button>
                <button>AnnuallySave 50%</button>
                <h3>Free Trial</h3>
                <p>Why 7-Day Free Trial. No credit card required.</p>
                <p>Try it Free</p>
            </body></html>
        HTML;

        $offers = (new MerchantPricingOfferExtractor())->extract($html, 'https://krisp.ai/pricing/');

        $this->assertSame(['Free Trial', 'Save 50%'], array_column($offers, 'title'));
        $this->assertSame('pricing', $offers[0]['source']);
        $this->assertNull($offers[0]['code']);
    }

    public function test_candidate_urls_include_pricing_and_homepage_links(): void
    {
        $urls = (new MerchantPricingOfferExtractor())->candidateUrls(
            'https://krisp.ai/',
            '<a href="/pricing/">Pricing</a><a href="https://krisp.ai/plans">Plans</a>'
        );

        $this->assertContains('https://krisp.ai/pricing', $urls);
        $this->assertContains('https://krisp.ai/plans', $urls);
    }

    public function test_ignores_pages_without_pricing_deals(): void
    {
        $offers = (new MerchantPricingOfferExtractor())->extract('<html><body><h1>About us</h1></body></html>');

        $this->assertSame([], $offers);
    }
}
