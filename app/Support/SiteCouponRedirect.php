<?php

namespace App\Support;

use App\Models\SiteSetting;

class SiteCouponRedirect
{
    public const KEY = 'coupon_redirect_flow';

    public const FLOW_CLOSE = 'close';

    public const FLOW_COPY = 'copy';

    public const FLOW_BOTH = 'both';

    public const FLOW_POPUP = 'popup';

    public const AFFILIATE_URL_KEY = 'coupon_redirect_affiliate_url';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::FLOW_CLOSE => 'Flow 1 — Show coupon popup; open destination when closing the popup',
            self::FLOW_COPY => 'Flow 2 — Copy code and open destination immediately (no coupon popup)',
            self::FLOW_BOTH => 'Flow 3 — Copy code and open destination immediately (no coupon popup; open only once)',
            self::FLOW_POPUP => 'Flow 4 — Show coupon popup and open the saved affiliate URL in a new tab',
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::options());
    }

    public static function flow(): string
    {
        $value = trim((string) SiteSetting::get(self::KEY, self::FLOW_CLOSE));

        return array_key_exists($value, self::options()) ? $value : self::FLOW_CLOSE;
    }

    public static function setFlow(string $flow): void
    {
        if (! array_key_exists($flow, self::options())) {
            $flow = self::FLOW_CLOSE;
        }

        SiteSetting::set(self::KEY, $flow);
    }

    public static function redirectsOnCopy(): bool
    {
        $flow = self::flow();

        return $flow === self::FLOW_COPY || $flow === self::FLOW_BOTH;
    }

    public static function redirectsOnClose(): bool
    {
        $flow = self::flow();

        return $flow === self::FLOW_CLOSE || $flow === self::FLOW_BOTH;
    }

    public static function redirectsOnPopupOpen(): bool
    {
        return self::flow() === self::FLOW_POPUP;
    }

    public static function affiliateUrl(): string
    {
        return trim((string) SiteSetting::get(self::AFFILIATE_URL_KEY, ''));
    }

    public static function setAffiliateUrl(?string $url): void
    {
        $url = trim((string) $url);

        SiteSetting::set(self::AFFILIATE_URL_KEY, $url);
    }
}
