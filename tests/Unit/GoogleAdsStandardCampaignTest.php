<?php

namespace Tests\Unit;

use App\Models\Store;
use App\Support\GoogleAdsStandardCampaign;
use PHPUnit\Framework\TestCase;

class GoogleAdsStandardCampaignTest extends TestCase
{
    public function test_generates_three_ad_groups_with_keywords_and_rsa(): void
    {
        $campaignBuilder = new GoogleAdsStandardCampaign;
        $store = new Store([
            'name' => 'Aruary',
            'slug' => 'aruary',
            'website' => 'https://example.com',
        ]);

        $campaign = $campaignBuilder->generate($store, [
            'discount_percent' => 40,
            'final_url' => 'https://hakoreview.com/stores/aruary',
        ]);

        $this->assertSame('Aruary '.now()->format('Y-m-d'), $campaign['campaign_name']);
        $this->assertSame('standard', $campaign['ad_group_mode']);
        $this->assertSame(40, $campaign['discount_percent']);
        $this->assertSame(22, $campaign['keyword_count']);
        $this->assertArrayHasKey('Coupons', $campaign['groups']);
        $this->assertArrayHasKey('Discounts', $campaign['groups']);
        $this->assertArrayHasKey('Promo', $campaign['groups']);

        $this->assertContains('Aruary coupon', $campaign['groups']['Coupons']['keywords']);
        $this->assertContains('Aruary Coupon Code', $campaign['groups']['Coupons']['headlines']);
        $this->assertTrue(
            collect($campaign['groups']['Coupons']['headlines'])->contains(
                fn (string $headline) => str_contains($headline, '40%')
            )
        );

        foreach ($campaign['groups'] as $group) {
            $this->assertGreaterThanOrEqual(8, count($group['headlines']));
            $this->assertSame(4, count($group['descriptions']));
            $this->assertSame(count($group['headlines']), count(array_unique($group['headlines'])));
            $this->assertSame(count($group['descriptions']), count(array_unique($group['descriptions'])));
            foreach ($group['headlines'] as $headline) {
                $this->assertLessThanOrEqual(30, mb_strlen($headline), $headline);
                $this->assertNotSame('', trim($headline));
            }
            foreach ($group['descriptions'] as $description) {
                $this->assertLessThanOrEqual(90, mb_strlen($description), $description);
                $this->assertGreaterThanOrEqual(80, mb_strlen($description), $description);
            }
        }
    }

    public function test_exports_keywords_and_ads_csv(): void
    {
        $campaignBuilder = new GoogleAdsStandardCampaign;
        $store = new Store([
            'name' => 'Aruary',
            'slug' => 'aruary',
            'website' => 'https://example.com',
        ]);

        $campaign = $campaignBuilder->generate($store, ['discount_percent' => 40]);
        $keywordsCsv = $campaignBuilder->toKeywordsCsv($campaign);
        $adsCsv = $campaignBuilder->toAdsCsv($campaign);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $keywordsCsv);
        $this->assertStringContainsString('Campaign', $keywordsCsv);
        $this->assertStringContainsString('Ad Group', $keywordsCsv);
        $this->assertStringContainsString('Keyword', $keywordsCsv);
        $this->assertStringContainsString('Phrase', $keywordsCsv);
        $this->assertStringContainsString('Enabled', $keywordsCsv);
        $this->assertStringContainsString('EU political ads', $keywordsCsv);
        $this->assertStringContainsString('No', $keywordsCsv);
        $this->assertStringContainsString('Coupons', $keywordsCsv);
        $this->assertStringContainsString('Discounts', $keywordsCsv);
        $this->assertStringContainsString('Promo', $keywordsCsv);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $adsCsv);
        $this->assertStringContainsString('Responsive search ad', $adsCsv);
        $this->assertStringContainsString('Headline 1', $adsCsv);
        $this->assertStringContainsString('Description 1', $adsCsv);
        $this->assertSame(3, substr_count($adsCsv, 'Responsive search ad'));
    }

    public function test_single_ad_group_mode_merges_all_keywords(): void
    {
        $campaignBuilder = new GoogleAdsStandardCampaign;
        $store = new Store([
            'name' => 'Aruary',
            'slug' => 'aruary',
            'website' => 'https://example.com',
        ]);

        $campaign = $campaignBuilder->generate($store, [
            'discount_percent' => 40,
            'ad_group_mode' => 'single',
            'ad_group_name' => 'All Keywords',
        ]);

        $this->assertSame('single', $campaign['ad_group_mode']);
        $this->assertCount(1, $campaign['groups']);
        $this->assertArrayHasKey('All Keywords', $campaign['groups']);
        $this->assertSame($campaign['keyword_count'], count($campaign['groups']['All Keywords']['keywords']));
        $this->assertContains('Aruary coupon', $campaign['groups']['All Keywords']['keywords']);
        $this->assertContains('Aruary discount', $campaign['groups']['All Keywords']['keywords']);
        $this->assertContains('Aruary promo code', $campaign['groups']['All Keywords']['keywords']);

        $rows = $campaignBuilder->keywordRows($campaign);
        $this->assertNotEmpty($rows);
        $this->assertSame(['All Keywords'], array_values(array_unique(array_column($rows, 'Ad Group'))));

        $adsCsv = $campaignBuilder->toAdsCsv($campaign);
        $this->assertSame(1, substr_count($adsCsv, 'Responsive search ad'));
    }

    public function test_clips_long_store_names_in_headlines(): void
    {
        $campaignBuilder = new GoogleAdsStandardCampaign;
        $store = new Store([
            'name' => 'Very Long Luxury Boutique Storefront Name',
            'slug' => 'long-store',
            'website' => 'https://example.com',
        ]);

        $campaign = $campaignBuilder->generate($store, ['discount_percent' => 25]);

        foreach ($campaign['groups']['Coupons']['headlines'] as $headline) {
            $this->assertLessThanOrEqual(30, mb_strlen($headline), $headline);
        }
    }
}
