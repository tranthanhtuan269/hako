<?php

namespace Tests\Unit;

use App\Support\AffiliateImportContentBuilder;
use ReflectionMethod;
use Tests\TestCase;

class StoreDescriptionOutlineTest extends TestCase
{
    public function test_about_store_format_a_includes_seven_headings(): void
    {
        $html = $this->invoke('sectionAboutStore', [
            'BottleBuzz',
            'Food & Drink',
            'Buy liquor online | premium spirits delivered',
            [
                ['name' => "Blanton's Original Single Barrel", 'description' => 'Single barrel bourbon.', 'price' => '$69.99', 'features' => ['Aged in oak'], 'url' => null, 'image' => null],
                ['name' => 'W.L. Weller Special Reserve', 'description' => 'Wheated bourbon staple.', 'price' => '$29.99', 'features' => ['Smooth finish'], 'url' => null, 'image' => null],
            ],
            [
                ['title' => '10% Off', 'code' => 'BUZZ10', 'type' => 'coupon', 'description' => '10% off'],
            ],
            ['domain' => 'bottlebuzz.com'],
            [],
            'https://example.test/stores/bottlebuzz',
            'https://example.test/go/bottlebuzz',
        ]);

        $this->assertStringContainsString('<h2>About BottleBuzz</h2>', $html);
        $this->assertStringContainsString('Why American Shoppers Keep Coming Back', $html);
        $this->assertStringContainsString('What Makes This Store Different', $html);
        $this->assertStringContainsString('Product Categories Worth Exploring', $html);
        $this->assertStringContainsString('Quality That Justifies the Price', $html);
        $this->assertStringContainsString('Shipping, Returns and Customer Experience', $html);
        $this->assertStringContainsString('How to Maximize Your Savings', $html);
        $this->assertStringContainsString('Is This Store Worth Shopping?', $html);
        $this->assertStringContainsString('Blanton', $html);
        $this->assertStringNotContainsString('fashion', strtolower($html));
    }

    public function test_about_store_format_b_when_meta_is_rich_brand_story(): void
    {
        $meta = str_repeat('Casabrews is an American espresso machine brand established in 2020. Our mission is home coffee convenience. ', 3);

        $html = $this->invoke('sectionAboutStore', [
            'Casabrews',
            'Home & Kitchen',
            $meta,
            [],
            [],
            ['domain' => 'casabrews.com'],
            [],
            'https://example.test/stores/casabrews',
            null,
        ]);

        $this->assertStringContainsString('<h2>About Casabrews</h2>', $html);
        $this->assertStringContainsString('established in 2020', $html);
        $this->assertStringNotContainsString('Why American Shoppers Keep Coming Back', $html);
    }

    public function test_questions_answers_prefer_merchant_faqs_first(): void
    {
        $html = $this->invoke('sectionQuestionsAnswers', [
            'Blue Coolers',
            [
                ['question' => 'What is your return policy?', 'answer' => 'Call us and we will take care of it, no questions asked.'],
            ],
            'https://example.test/stores/blue-coolers',
            [],
            [
                ['title' => '10% OFF Select Items', 'code' => 'BLUE10', 'type' => 'coupon', 'description' => null],
            ],
            [],
        ]);

        $this->assertStringContainsString('<h2>Blue Coolers Questions &amp; Answers</h2>', $html);
        $this->assertStringContainsString('What is your return policy?', $html);
        $this->assertStringContainsString('no questions asked', $html);
        $this->assertStringContainsString('Why should I visit', $html);
        $this->assertStringContainsString('BLUE10', $html);
        $this->assertStringNotContainsString('half of their purchase', strtolower($html));
    }

    public function test_how_to_apply_has_three_steps(): void
    {
        $html = $this->invoke('sectionHowToApplyCouponCodes', [
            'Pins and Aces',
            'https://example.test/stores/pins-and-aces',
            'https://example.test/go/pins',
        ]);

        $this->assertStringContainsString('<h2>How to Apply Pins and Aces Coupon Codes</h2>', $html);
        $this->assertStringContainsString('Step 1', $html);
        $this->assertStringContainsString('Step 2', $html);
        $this->assertStringContainsString('Step 3', $html);
        $this->assertStringContainsString('Promo Code', $html);
    }

    /**
     * @param  list<mixed>  $args
     */
    private function invoke(string $method, array $args): string
    {
        $builder = new AffiliateImportContentBuilder();
        $ref = new ReflectionMethod(AffiliateImportContentBuilder::class, $method);
        $ref->setAccessible(true);

        return (string) $ref->invoke($builder, ...$args);
    }
}
