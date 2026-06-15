<?php

namespace Database\Seeders;

use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $site = config('site.name');

        $articles = [
            [
                'title' => 'How to Use Coupon Codes Online: A Complete Guide for 2026',
                'excerpt' => 'Learn where to find promo codes, how to apply them at checkout, and proven tips to maximize your savings on U.S. e-commerce sites.',
                'meta_title' => "How to Use Coupon Codes Online (2026 Guide) | {$site}",
                'meta_description' => "Step-by-step guide to finding and using coupon codes at Amazon, Walmart, Target, and more. Save money with verified promo codes from {$site}.",
                'content' => <<<HTML
<h2>What Is a Coupon Code?</h2>
<p>A coupon code (also called a promo code or discount code) is a combination of letters and numbers you enter at checkout to receive a discount, free shipping, or another special offer. Retailers use them to attract new customers and reward loyal shoppers.</p>

<h2>Where to Find Legitimate Coupon Codes</h2>
<p>Trusted coupon hubs like <strong>{$site}</strong> aggregate verified offers from major U.S. retailers. Always copy the code exactly and check the expiration date before you shop.</p>
<ul>
<li>Visit the retailer’s official promotions page</li>
<li>Subscribe to store newsletters for exclusive codes</li>
<li>Browse category pages on {$site} for store-specific deals</li>
</ul>

<h2>How to Apply a Code at Checkout</h2>
<p>Most online stores have a field labeled “Promo code,” “Discount code,” or “Gift card/promo code” during checkout. Paste your code, click Apply, and confirm the discount appears before you pay.</p>

<h2>Common Reasons a Code Doesn’t Work</h2>
<ul>
<li>The offer has expired or reached its usage limit</li>
<li>Your cart doesn’t meet the minimum purchase requirement</li>
<li>Excluded brands or sale items are in your cart</li>
<li>The code is valid only for new customers or specific payment methods</li>
</ul>

<h2>Pro Tips to Save More</h2>
<p>Stack store sales with coupon codes when allowed, compare prices across retailers, and set a budget before you shop. Bookmark {$site} for daily updates on the best U.S. online coupons.</p>
HTML,
            ],
            [
                'title' => 'Amazon Coupon Codes: Best Ways to Save in 2026',
                'excerpt' => 'Discover how Amazon promo codes, clip coupons, and seasonal sales work—and how to combine them for maximum savings.',
                'meta_title' => "Amazon Coupon Codes & Promo Tips 2026 | {$site}",
                'meta_description' => "Save on Amazon with coupon codes, Lightning Deals, and Prime offers. Expert tips from {$site}, your Top Hub of US Online Coupons.",
                'content' => <<<HTML
<h2>Types of Amazon Savings</h2>
<p>Amazon offers several ways to save: clip-on coupons on product pages, promo codes at checkout, Lightning Deals, and Prime-exclusive discounts. Understanding each type helps you pick the best deal.</p>

<h2>Finding Amazon Promo Codes</h2>
<p>Third-party coupon sites like {$site} list publicly available Amazon codes when retailers share them. Verify the terms—many codes apply only to specific categories or first-time buyers.</p>

<h2>Prime Day and Seasonal Sales</h2>
<p>Prime Day, Black Friday, and Cyber Monday often feature deeper discounts than everyday coupon codes. Plan big purchases around these events and check {$site} for pre-sale code roundups.</p>

<h2>Stacking Rules</h2>
<p>Amazon typically allows one promo code per order. You can often combine clip coupons with sale prices, but read the fine print on each offer before checkout.</p>
HTML,
            ],
            [
                'title' => 'Walmart vs Target Coupons: Which Store Saves You More?',
                'excerpt' => 'Compare Walmart and Target coupon policies, loyalty programs, and the best strategies for grocery and household savings.',
                'meta_title' => "Walmart vs Target Coupons Compared | {$site}",
                'meta_description' => "Walmart and Target coupon guide: promo codes, app deals, and loyalty perks compared. Find the best deals at {$site}.",
                'content' => <<<HTML
<h2>Walmart Savings Overview</h2>
<p>Walmart frequently offers rollback prices, online promo codes, and free pickup discounts. Their app sometimes includes exclusive digital coupons for in-store and online use.</p>

<h2>Target Savings Overview</h2>
<p>Target Circle offers member discounts, weekly ad deals, and occasional promo codes on top categories like home, apparel, and electronics.</p>

<h2>Which Is Better for Your Cart?</h2>
<p>For groceries and bulk household items, Walmart often wins on base price. Target excels when Circle offers and seasonal promotions align with your shopping list. Compare both before large purchases.</p>

<h2>Using {$site} for Both Retailers</h2>
<p>Browse our <a href="/stores">store pages</a> for the latest Walmart and Target coupon codes, updated regularly so you never miss a valid deal.</p>
HTML,
            ],
            [
                'title' => '10 Black Friday Coupon Strategies That Actually Work',
                'excerpt' => 'Prepare for Black Friday with these proven coupon and deal-stacking strategies used by savvy U.S. shoppers.',
                'meta_title' => "Black Friday Coupon Strategies 2026 | {$site}",
                'meta_description' => "Master Black Friday shopping with coupon tips, price tracking, and deal timing. {$site} brings you the best US online coupons.",
                'content' => <<<HTML
<h2>Start Early</h2>
<p>Many retailers release preview deals weeks before Black Friday. Follow {$site}’s blog and coupon listings to catch early-bird codes before they expire.</p>

<h2>Make a List and Set a Budget</h2>
<p>Impulse buying destroys savings. List what you need, set a maximum spend, and stick to items with verified discounts.</p>

<h2>Compare Doorbusters vs Online Deals</h2>
<p>In-store doorbusters aren’t always the lowest price. Check online listings—many stores price-match or offer better web-exclusive codes.</p>

<h2>Use Price History Tools</h2>
<p>A “50% off” tag means little if the price was inflated last week. Use browser extensions or price trackers to confirm you’re getting a real deal.</p>

<h2>Check Return Policies</h2>
<p>Holiday return windows vary. Know the policy before you buy electronics, apparel, or gifts you might exchange.</p>

<h2>Bookmark {$site}</h2>
<p>We update coupon codes throughout Black Friday weekend so you have one trusted hub for US online savings.</p>
HTML,
            ],
        ];

        foreach ($articles as $i => $article) {
            $slug = Str::slug($article['title']);

            Post::updateOrCreate(
                ['slug' => $slug],
                array_merge($article, [
                    'slug' => $slug,
                    'author_name' => $site.' Team',
                    'published_at' => Carbon::now()->subDays(count($articles) - $i),
                    'is_published' => true,
                    'view_count' => rand(50, 500),
                ])
            );
        }
    }
}
