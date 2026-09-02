<?php

namespace App\Support;

use App\Models\SiteSetting;

final class OntopcouponHomepage
{
    public const KEY = 'ontopcoupon_homepage';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'sections' => [
                'hero' => true,
                'trust' => true,
                'featured_coupons' => true,
                'categories' => true,
                'stores' => true,
                'how_it_works' => true,
                'featured_deal' => true,
                'blog' => true,
                'cta' => true,
                'newsletter' => true,
            ],
            'search_placeholder' => 'Search coupons, stores, categories, blog…',
            'trust_label' => 'Trusted by Top Stores',
            'featured_coupons' => [
                'kicker' => "Today's picks",
                'title' => 'Featured Coupons',
                'link' => 'View all',
            ],
            'categories' => [
                'kicker' => 'Shop by',
                'title' => 'Browse by Category',
                'link' => 'All categories',
                'explore' => 'Explore',
            ],
            'stores' => [
                'kicker' => 'Handpicked',
                'title' => 'Top Stores',
                'link' => 'All stores',
            ],
            'how_it_works' => [
                'kicker' => 'How it works',
                'title' => "Save More in\n3 Simple Steps",
                'cta' => 'Browse All Coupons',
                'steps' => [
                    [
                        'title' => 'Find Your Store',
                        'body' => 'Browse {stores}+ top stores or use the search bar to find your favourite brand instantly.',
                    ],
                    [
                        'title' => 'Grab the Code',
                        'body' => 'Click to reveal the promo code — or go straight to the deal with one tap.',
                    ],
                    [
                        'title' => 'Enjoy the Savings',
                        'body' => 'Apply at checkout and watch the discount drop. Every deal is listed before it goes live.',
                    ],
                ],
            ],
            'featured_deal' => [
                'kicker' => 'Featured deal',
                'offers_label' => 'Active offers',
                'stores_label' => 'Active stores',
            ],
            'blog' => [
                'kicker' => 'From the blog',
                'title' => 'Saving Tips & Guides',
                'link' => 'All articles',
            ],
            'cta_shoppers' => [
                'kicker' => 'For shoppers',
                'title' => 'Get Sales-Ready Deals Without The Wait',
                'body' => '{coupons}+ coupons updated daily. Never pay full price again.',
                'button' => 'Browse All Deals',
                'stat_listed' => 'Listed',
            ],
            'cta_owners' => [
                'kicker' => 'For store owners',
                'title' => 'List Your Store & Reach More Shoppers',
                'body' => 'Get your coupons in front of deal-hunters every day.',
                'button' => 'Submit Your Store',
                'checks' => [
                    'Free listing, no sign-up fees',
                    'Reach shoppers actively searching',
                    'Submit coupons in minutes',
                ],
            ],
            'newsletter' => [
                'kicker' => 'Exclusive deals in your inbox',
                'title' => 'Never Miss a Deal',
                'body' => 'Get the best coupons and deals delivered to your inbox every week.',
                'placeholder' => 'Enter your email',
                'button' => 'Subscribe',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function resolved(): array
    {
        return self::mergeDefaults(self::defaults(), self::saved());
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function save(array $input): void
    {
        $merged = self::mergeDefaults(self::defaults(), $input);

        SiteSetting::set(self::KEY, json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param  array<string, int|string>  $stats
     */
    public static function interpolate(string $text, array $stats): string
    {
        return strtr($text, [
            '{coupons}' => number_format((int) ($stats['coupons'] ?? 0)),
            '{stores}' => number_format((int) ($stats['stores'] ?? 0)),
            '{categories}' => number_format((int) ($stats['categories'] ?? 0)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function saved(): array
    {
        $raw = SiteSetting::get(self::KEY, '');

        if (is_array($raw)) {
            return $raw;
        }

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $saved
     * @return array<string, mixed>
     */
    private static function mergeDefaults(array $defaults, array $saved): array
    {
        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $saved)) {
                continue;
            }

            $incoming = $saved[$key];

            if (is_bool($value)) {
                $defaults[$key] = (bool) $incoming;

                continue;
            }

            if (is_array($value) && self::isList($value) && is_array($incoming)) {
                foreach ($value as $index => $item) {
                    if (! array_key_exists($index, $incoming)) {
                        continue;
                    }

                    $defaults[$key][$index] = is_array($item) && is_array($incoming[$index])
                        ? self::mergeDefaults($item, $incoming[$index])
                        : (is_string($incoming[$index]) ? $incoming[$index] : $item);
                }

                continue;
            }

            if (is_array($value) && is_array($incoming)) {
                $defaults[$key] = self::mergeDefaults($value, $incoming);

                continue;
            }

            if (is_string($value)) {
                $defaults[$key] = is_string($incoming) ? $incoming : $value;
            }
        }

        return $defaults;
    }

    /**
     * @param  array<mixed>  $value
     */
    private static function isList(array $value): bool
    {
        return $value === [] || array_is_list($value);
    }
}
