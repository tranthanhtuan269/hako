<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Http\Request;

final class TrackingScripts
{
    public const HEAD_KEY = 'tracking_head_html';

    public const CONVERSION_RULES_KEY = 'tracking_conversion_rules';

    /** @deprecated Migrated into CONVERSION_RULES_KEY */
    public const CONVERSION_KEY = 'tracking_conversion_html';

    /** @deprecated Migrated into CONVERSION_RULES_KEY */
    public const CONVERSION_PAGES_KEY = 'tracking_conversion_pages';

    /** @deprecated Migrated into CONVERSION_RULES_KEY */
    public const CONVERSION_SEND_TO_KEY = 'tracking_conversion_send_to';

    public static function headHtml(): string
    {
        return trim((string) SiteSetting::get(self::HEAD_KEY, ''));
    }

    /**
     * @return list<array{path: string, html: string, send_to: string}>
     */
    public static function conversionRules(): array
    {
        $raw = SiteSetting::get(self::CONVERSION_RULES_KEY);

        if (is_string($raw) && $raw !== '') {
            return self::normalizeRules(self::decodeRules($raw));
        }

        return self::migrateLegacyConversionRules();
    }

    public static function conversionActiveForRequest(?Request $request = null): bool
    {
        return self::conversionForRequest($request) !== null;
    }

    /**
     * @return array{path: string, html: string, send_to: string}|null
     */
    public static function conversionForRequest(?Request $request = null): ?array
    {
        $request ??= request();
        $path = self::normalizeRequestPath($request);

        $rules = self::conversionRules();

        usort($rules, fn (array $a, array $b) => strlen($b['path']) <=> strlen($a['path']));

        foreach ($rules as $rule) {
            if ($rule['html'] === '') {
                continue;
            }

            if (self::pathMatches($rule['path'], $path)) {
                return $rule;
            }
        }

        return null;
    }

    public static function setHeadHtml(?string $html): void
    {
        SiteSetting::set(self::HEAD_KEY, self::sanitize($html));
    }

    /**
     * @param  list<array{path?: string, html?: string|null}>  $rules
     */
    public static function setConversionRules(array $rules): void
    {
        $normalized = [];

        foreach ($rules as $rule) {
            $path = self::normalizePathPattern(trim((string) ($rule['path'] ?? '')));
            $html = self::sanitize($rule['html'] ?? null);

            if ($path === '' && $html === '') {
                continue;
            }

            if ($path === '') {
                continue;
            }

            $normalized[] = [
                'path' => $path,
                'html' => $html,
                'send_to' => self::extractSendTo($html),
            ];
        }

        SiteSetting::set(self::CONVERSION_RULES_KEY, json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function sanitize(?string $html): string
    {
        if ($html === null) {
            return '';
        }

        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $html = preg_replace('/<\/?(head|body|html)[^>]*>/i', '', $html) ?? $html;

        return trim($html);
    }

    /**
     * @return list<array{path: string, html: string, send_to: string}>
     */
    private static function migrateLegacyConversionRules(): array
    {
        $html = trim((string) SiteSetting::get(self::CONVERSION_KEY, ''));

        if ($html === '') {
            return [];
        }

        $pagesRaw = SiteSetting::get(self::CONVERSION_PAGES_KEY, '[]');
        $pages = is_string($pagesRaw) ? json_decode($pagesRaw, true) : [];
        $pages = is_array($pages) ? $pages : [];

        $pathMap = [
            'home' => ['/'],
            'stores' => ['/stores/*'],
            'coupons' => ['/coupons/*'],
            'categories' => ['/categories/*'],
            'blog' => ['/blog/*'],
            'authors' => ['/authors/*'],
            'pages' => ['/about-us', '/contact-us', '/privacy-policy', '/terms-of-service', '/cookie-policy', '/disclaimer'],
            'search' => ['/search'],
        ];

        $rules = [];

        foreach ($pages as $page) {
            if (! is_string($page) || ! isset($pathMap[$page])) {
                continue;
            }

            foreach ($pathMap[$page] as $path) {
                $rules[] = [
                    'path' => $path,
                    'html' => $html,
                    'send_to' => self::extractSendTo($html),
                ];
            }
        }

        if ($rules !== []) {
            self::setConversionRules($rules);
        }

        return $rules;
    }

    /**
     * @return list<array{path?: string, html?: string, send_to?: string}>
     */
    private static function decodeRules(string $raw): array
    {
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  list<array{path?: string, html?: string, send_to?: string}>  $rules
     * @return list<array{path: string, html: string, send_to: string}>
     */
    private static function normalizeRules(array $rules): array
    {
        $normalized = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $path = self::normalizePathPattern(trim((string) ($rule['path'] ?? '')));
            $html = self::sanitize($rule['html'] ?? null);

            if ($path === '' || $html === '') {
                continue;
            }

            $normalized[] = [
                'path' => $path,
                'html' => $html,
                'send_to' => self::extractSendTo($html) ?: trim((string) ($rule['send_to'] ?? '')),
            ];
        }

        return $normalized;
    }

    private static function normalizeRequestPath(Request $request): string
    {
        $path = '/' . trim($request->path(), '/');

        return $path === '//' ? '/' : $path;
    }

    private static function normalizePathPattern(string $path): string
    {
        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            $parsed = parse_url($path, PHP_URL_PATH);

            return self::normalizePathPattern((string) ($parsed ?? '/'));
        }

        $path = '/' . trim($path, '/');

        return $path === '//' ? '/' : $path;
    }

    private static function pathMatches(string $pattern, string $path): bool
    {
        $pattern = self::normalizePathPattern($pattern);

        if ($pattern === '/') {
            return $path === '/';
        }

        if (str_ends_with($pattern, '/*')) {
            $prefix = rtrim($pattern, '/*');

            return $path === $prefix || str_starts_with($path, $prefix . '/');
        }

        return $path === $pattern;
    }

    private static function extractSendTo(string $html): string
    {
        if (preg_match("/['\"]send_to['\"]\s*:\s*['\"]([^'\"]+)['\"]/", $html, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }
}
