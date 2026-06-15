<?php

$domain = env('SITE_DOMAIN', 'thuoc360.com');
$url = rtrim(env('SITE_URL', 'https://'.$domain), '/');
$name = env('SITE_NAME') ?: $domain;
$tagline = env('SITE_TAGLINE', 'Top Hub of US Online Coupons');

return [

    'domain' => $domain,

    'url' => $url,

    'name' => $name,

    'tagline' => $tagline,

    'acronym' => env('SITE_ACRONYM') ?: strtoupper(explode('.', $domain)[0]),

    'contact_email' => env('SITE_CONTACT_EMAIL', 'contact@'.$domain),

    'privacy_email' => env('SITE_PRIVACY_EMAIL', 'privacy@'.$domain),

    'support_email' => env('SITE_SUPPORT_EMAIL', 'support@'.$domain),

    'legal_last_updated' => 'June 2, 2026',

    'default_description' => env('SITE_DESCRIPTION') ?: (
        "{$name} is the Top Hub of US Online Coupons — verified promo codes, discount deals, and savings tips for Amazon, Walmart, Target, and top U.S. retailers."
    ),

    'default_author' => [
        'name' => env('SITE_AUTHOR_NAME', $name.' Team'),
        'slug' => env('SITE_AUTHOR_SLUG', 'editorial-team'),
        'title' => env('SITE_AUTHOR_TITLE', 'Editorial Team'),
        'guest_title' => 'Contributing Writer',
        'member_title' => 'Savings Writer',
        'bio' => env('SITE_AUTHOR_BIO') ?: (
            "The {$name} editorial team researches coupon codes, tests promo links, and publishes savings guides to help U.S. shoppers find verified deals from trusted retailers."
        ),
        'avatar' => env('SITE_AUTHOR_AVATAR'),
    ],

    'og_image' => env('SITE_OG_IMAGE') ?: null,

    'twitter_handle' => env('SITE_TWITTER', '@'.str_replace('.', '', explode('.', $domain)[0])),

    'locale' => 'en_US',

    'bot_user_agent' => env('SITE_BOT_USER_AGENT') ?: (
        'Mozilla/5.0 (compatible; '.preg_replace('/[^a-zA-Z0-9]/', '', $domain).'Bot/1.0; +'.$url.')'
    ),

];
