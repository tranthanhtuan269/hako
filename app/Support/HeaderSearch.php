<?php

namespace App\Support;

use App\Models\SiteSetting;
use Throwable;

final class HeaderSearch
{
    public const KEY = 'header_search_visible';

    public static function visible(): bool
    {
        try {
            $value = SiteSetting::get(self::KEY, '1');
        } catch (Throwable) {
            return true;
        }

        if ($value === null || $value === '') {
            return true;
        }

        return $value === '1' || $value === 1 || $value === true || $value === 'true';
    }

    public static function toggle(): bool
    {
        $next = ! self::visible();
        SiteSetting::set(self::KEY, $next ? '1' : '0');

        return $next;
    }
}
