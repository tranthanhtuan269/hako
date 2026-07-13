<?php

namespace App\Support;

use App\Models\SiteSetting;

final class SiteImportSettings
{
    private const ALLOW_REIMPORT_KEY = 'import_allow_reimport_existing_stores';

    public static function allowReimportExistingStores(): bool
    {
        return filter_var(SiteSetting::get(self::ALLOW_REIMPORT_KEY, '1'), FILTER_VALIDATE_BOOL);
    }

    public static function setAllowReimportExistingStores(bool $allow): void
    {
        SiteSetting::set(self::ALLOW_REIMPORT_KEY, $allow ? '1' : '0');
    }
}
