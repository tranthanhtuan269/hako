<?php

namespace App\Support;

use App\Models\SiteSetting;

final class SiteIntegrations
{
    private const GEMINI_KEY = 'gemini_api_key';

    private const SCAN_SITE_KEY = 'scan_site';

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

        $env = trim((string) env('SCAN_SITE', ''));

        if ($env !== '') {
            return $env;
        }

        $domain = (string) config('site.domain', '');

        return strtolower(explode('.', $domain)[0] ?? $domain);
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
