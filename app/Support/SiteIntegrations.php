<?php

namespace App\Support;

use App\Models\SiteSetting;

final class SiteIntegrations
{
    private const GEMINI_KEY = 'gemini_api_key';

    private const SCAN_SITE_KEY = 'scan_site';

    private const SCAN_API_URL_KEY = 'scan_api_url';

    private const SCAN_AFFILIATE_SIGNUPS_API_URL_KEY = 'scan_affiliate_signups_api_url';

    private const SCAN_API_LIMIT_KEY = 'scan_api_limit';

    private const SCAN_DEFAULT_BASE_URL = 'https://scan.thuoc360.com';

    public static function geminiApiKey(): string
    {
        $stored = trim((string) SiteSetting::get(self::GEMINI_KEY, ''));

        if ($stored !== '') {
            return $stored;
        }

        return trim((string) env('GEMINI_API_KEY', ''));
    }

    public static function scanSiteStored(): string
    {
        return strtolower(trim((string) SiteSetting::get(self::SCAN_SITE_KEY, '')));
    }

    public static function scanSite(): string
    {
        $stored = self::scanSiteStored();

        if ($stored !== '') {
            return $stored;
        }

        return self::defaultScanSiteFromDomain();
    }

    public static function defaultScanSiteFromDomain(): string
    {
        $domain = trim((string) config('site.domain', ''));

        if ($domain === '') {
            $host = parse_url(trim((string) config('site.url', '')), PHP_URL_HOST);
            $domain = is_string($host) ? $host : '';
        }

        if ($domain === '' && ! app()->runningInConsole()) {
            $domain = trim((string) request()->getHost());
        }

        $domain = strtolower($domain);
        $domain = (string) preg_replace('/^www\./', '', $domain);
        $segment = explode('.', $domain)[0] ?? $domain;
        $slug = strtolower((string) preg_replace('/[^a-z0-9_-]/', '', $segment));

        return $slug !== '' ? $slug : 'site';
    }

    public static function scanApiUrl(): string
    {
        $stored = trim((string) SiteSetting::get(self::SCAN_API_URL_KEY, ''));

        if ($stored !== '') {
            return $stored;
        }

        return rtrim(self::SCAN_DEFAULT_BASE_URL, '/') . '/api/coupons';
    }

    public static function scanSyncUrl(): string
    {
        return rtrim(self::SCAN_DEFAULT_BASE_URL, '/') . '/api/coupons/import';
    }

    public static function scanAffiliateSignupsApiUrl(): string
    {
        $stored = trim((string) SiteSetting::get(self::SCAN_AFFILIATE_SIGNUPS_API_URL_KEY, ''));

        if ($stored === '') {
            return rtrim(self::SCAN_DEFAULT_BASE_URL, '/') . '/api/affiliate-signups';
        }

        if (preg_match('#/coupons/?$#i', $stored)) {
            return (string) preg_replace('#/coupons/?$#i', '/affiliate-signups', $stored);
        }

        return $stored;
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

    public static function setScanAffiliateSignupsApiUrl(?string $value): void
    {
        SiteSetting::set(self::SCAN_AFFILIATE_SIGNUPS_API_URL_KEY, trim((string) $value));
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
