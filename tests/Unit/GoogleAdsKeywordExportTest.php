<?php

namespace Tests\Unit;

use App\Models\Store;
use App\Support\GoogleAdsKeywordExport;
use App\Support\GoogleAdsTargetingCatalog;
use App\Support\KeywordGenerationEngine;
use PHPUnit\Framework\TestCase;

class GoogleAdsKeywordExportTest extends TestCase
{
    public function test_builds_google_ads_editor_csv_rows(): void
    {
        $engine = new KeywordGenerationEngine;
        $exporter = new GoogleAdsKeywordExport;

        $store = new Store([
            'name' => 'MoveSpeed',
            'slug' => 'movespeed',
            'website' => 'https://movespeed.com',
        ]);

        $result = $engine->generate('MoveSpeed', ['ssd']);
        $settings = $exporter->defaultsForStore($store);
        $rows = $exporter->rows($result, $settings, $store);

        $this->assertNotEmpty($rows);
        $this->assertSame('MoveSpeed '.now()->format('Y-m-d'), $rows[0]['Campaign']);
        $this->assertSame('MoveSpeed - Brand', $rows[0]['Ad Group']);
        $this->assertSame('movespeed coupon', $rows[0]['Keyword']);
        $this->assertSame('Phrase', $rows[0]['Criterion Type']);
        $this->assertSame('Enabled', $rows[0]['Status']);
        $this->assertStringContainsString('movespeed.com', $rows[0]['Final URL']);

        $productRows = array_values(array_filter($rows, fn (array $row) => $row['Ad Group'] === 'MoveSpeed - Ssd'));
        $this->assertNotEmpty($productRows);
        $this->assertSame('movespeed ssd coupon', $productRows[0]['Keyword']);
    }

    public function test_exports_phrase_match_only(): void
    {
        $engine = new KeywordGenerationEngine;
        $exporter = new GoogleAdsKeywordExport;

        $store = new Store([
            'name' => 'Acme',
            'slug' => 'acme',
            'website' => 'https://acme.com',
        ]);
        $result = $engine->generate('Acme', []);
        $settings = array_merge($exporter->defaultsForStore($store), [
            'ad_group_mode' => 'single',
            'all_match_types' => true,
            'match_type' => 'Broad',
        ]);

        $rows = $exporter->rows($result, $settings, $store);

        $this->assertCount(count($result['brand']), $rows);
        $this->assertSame(['Phrase'], array_values(array_unique(array_column($rows, 'Criterion Type'))));
    }

    public function test_csv_includes_utf8_bom_and_headers(): void
    {
        $engine = new KeywordGenerationEngine;
        $exporter = new GoogleAdsKeywordExport;

        $store = new Store([
            'name' => 'Acme',
            'slug' => 'acme',
            'website' => 'https://acme.com',
        ]);
        $result = $engine->generate('Acme', []);
        $csv = $exporter->toCsv($result, $exporter->defaultsForStore($store), $store);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Campaign', $csv);
        $this->assertStringContainsString('Criterion Type', $csv);
        $this->assertStringContainsString('acme coupon', $csv);
    }

    public function test_exports_targeting_csv_with_locations_languages_and_networks(): void
    {
        $exporter = new GoogleAdsKeywordExport;

        $store = new Store([
            'name' => 'Acme DE',
            'slug' => 'acme-de',
            'website' => 'https://acme.de',
        ]);

        $settings = array_merge($exporter->defaultsForStore($store), [
            'target_locations' => ['Germany', 'Austria'],
            'excluded_locations' => ['Switzerland'],
            'languages' => ['German', 'English'],
            'network_search' => true,
            'network_search_partners' => true,
            'network_display' => false,
            'budget' => '20.00',
        ]);

        $csv = $exporter->toTargetingCsv($settings, $store);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Campaign,Location,Type', $csv);
        $this->assertStringContainsString('Germany', $csv);
        $this->assertStringContainsString('Austria', $csv);
        $this->assertStringContainsString('Switzerland', $csv);
        $this->assertStringContainsString('Negative', $csv);
        $this->assertStringNotContainsString('Language', $csv);
        $this->assertStringNotContainsString('Network', $csv);

        $campaignCsv = $exporter->toCampaignCsv($settings);
        $this->assertStringContainsString('Networks', $campaignCsv);
        $this->assertStringContainsString('Languages', $campaignCsv);
        $this->assertStringContainsString('Google search;Search partners', $campaignCsv);
        $this->assertStringContainsString('de;en', $campaignCsv);
        $this->assertStringNotContainsString('Display Network', $campaignCsv);
    }

    public function test_campaign_csv_declares_no_eu_political_ads(): void
    {
        $exporter = new GoogleAdsKeywordExport;
        $store = new Store([
            'name' => 'Acme',
            'slug' => 'acme',
            'website' => 'https://acme.com',
        ]);

        $settings = array_merge($exporter->defaultsForStore($store), [
            'budget' => '25.50',
        ]);
        $csv = $exporter->toCampaignCsv($settings);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Campaign', $csv);
        $this->assertStringContainsString('Campaign Type', $csv);
        $this->assertStringContainsString('Search', $csv);
        $this->assertStringContainsString('Budget', $csv);
        $this->assertStringContainsString('25.50', $csv);
        $this->assertStringContainsString('Daily', $csv);
        $this->assertStringContainsString('Manual CPC', $csv);
        $this->assertStringContainsString('EU political ads', $csv);
        $this->assertStringContainsString('No', $csv);
    }

    public function test_all_languages_expands_to_every_concrete_language(): void
    {
        $exporter = new GoogleAdsKeywordExport;

        $store = new Store([
            'name' => 'Global',
            'slug' => 'global',
            'website' => 'https://global.com',
        ]);

        $settings = $exporter->normalizeSettings([
            'languages' => ['All languages', 'English'],
        ], $store);

        $this->assertSame(GoogleAdsTargetingCatalog::concreteLanguages(), $settings['languages']);

        $campaignCsv = $exporter->toCampaignCsv($settings);
        $this->assertStringContainsString('Languages', $campaignCsv);
        // All concrete languages selected => empty Languages cell (Editor default).
        $this->assertSame('', GoogleAdsTargetingCatalog::languageCodesCsv($settings['languages']));
    }

    public function test_normalizes_max_cpc_by_currency(): void
    {
        $exporter = new GoogleAdsKeywordExport;

        $store = new Store([
            'name' => 'Acme',
            'slug' => 'acme',
            'website' => 'https://acme.com',
        ]);

        $usd = $exporter->normalizeSettings([
            'max_cpc' => '$1.5',
            'max_cpc_currency' => 'USD',
        ], $store);

        $this->assertSame('1.50', $usd['max_cpc']);
        $this->assertSame('USD', $usd['max_cpc_currency']);

        $vnd = $exporter->normalizeSettings([
            'max_cpc' => '25,000.75',
            'max_cpc_currency' => 'VND',
        ], $store);

        $this->assertSame('25001', $vnd['max_cpc']);
        $this->assertSame('VND', $vnd['max_cpc_currency']);
    }
}
