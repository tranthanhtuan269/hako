<?php

namespace App\Support;

use App\Models\SiteSetting;

final class SiteIntegrations
{
    private const GEMINI_KEY = 'gemini_api_key';

    private const SCAN_SITE_KEY = 'scan_site';

    private const SCAN_API_URL_KEY = 'scan_api_url';

    private const SCAN_SYNC_URL_KEY = 'scan_sync_url';

    private const SCAN_API_LIMIT_KEY = 'scan_api_limit';

    public static function geminiApiKey(): string
    {
        $stored = trim((string) SiteSetting::get(self::GEMINI_KEY, ''));

        if ($stored !== '') {
            return $stored;
        }

        return trim((string) env('GEMINI_API_KEY', ''));
    }

    public static function scanSite(): string
    {
        $stored = trim((string) SiteSetting::get(self::SCAN_SITE_KEY, ''));

        if ($stored !== '') {
            return $stored;
        }

        $domain = (string) config('site.domain', '');

        return strtolower(explode('.', $domain)[0] ?? $domain);
    }

    public static function scanApiUrl(): string
    {
        return trim((string) SiteSetting::get(self::SCAN_API_URL_KEY, ''));
    }

    public static function scanSyncUrl(): string
    {
        return trim((string) SiteSetting::get(self::SCAN_SYNC_URL_KEY, ''));
    }

    public static function scanApiLimit(): int
    {
        $stored = trim((string) SiteSetting::get(self::SCAN_API_LIMIT_KEY, ''));

        if ($stored !== '' && is_numeric($stored)) {
            return max(1, min(200, (int) $stored));
        }

        return 20;
    }

    public static function setScanApiUrl(?string $value): void
    {
        SiteSetting::set(self::SCAN_API_URL_KEY, trim((string) $value));
    }

    public static function setScanSyncUrl(?string $value): void
    {
        SiteSetting::set(self::SCAN_SYNC_URL_KEY, trim((string) $value));
    }

    public static function setScanApiLimit(?int $value): void
    {
        if ($value === null) {
            return;
        }

        SiteSetting::set(self::SCAN_API_LIMIT_KEY, (string) max(1, min(200, $value)));
    }

    public static function maskedGeminiApiKey(): string
    {
        $key = self::geminiApiKey();

        if ($key === '') {
            return '';
        }

        if (strlen($key) <= 8) {
            return '••••••••';
        }

        return substr($key, 0, 4).'…'.substr($key, -4);
    }

    public static function setGeminiApiKey(?string $value): void
    {
        if ($value === null) {
            return;
        }

        $value = trim($value);

        if ($value === '') {
            return;
        }

        SiteSetting::set(self::GEMINI_KEY, $value);
    }

    public static function clearGeminiApiKey(): void
    {
        SiteSetting::set(self::GEMINI_KEY, '');
    }

    public static function setScanSite(?string $value): void
    {
        SiteSetting::set(self::SCAN_SITE_KEY, trim((string) $value));
    }
}
