<?php

namespace App\Support;

use App\Models\SiteSetting;

class SiteCouponRedirect
{
    public const KEY = 'coupon_redirect_flow';

    public const FLOW_CLOSE = 'close';

    public const FLOW_COPY = 'copy';

    public const FLOW_BOTH = 'both';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::FLOW_CLOSE => 'Flow 1 — Show coupon popup; open destination when closing the popup',
            self::FLOW_COPY => 'Flow 2 — Copy code and open destination immediately (no coupon popup)',
            self::FLOW_BOTH => 'Flow 3 — Copy code and open destination immediately (no coupon popup; open only once)',
        ];
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
}
