<?php

return [
    'enabled' => filter_var(env('AFFILIATE_PROGRAM_ENABLED', false), FILTER_VALIDATE_BOOL),
    'cookie_name' => 'affiliate_ref',
    'cookie_days' => (int) env('AFFILIATE_COOKIE_DAYS', 30),
    'default_commission_rate' => (float) env('AFFILIATE_COMMISSION_RATE', 10),
    'min_payout_amount' => (float) env('AFFILIATE_MIN_PAYOUT', 50),
    'currency' => env('AFFILIATE_CURRENCY', 'USD'),
];
