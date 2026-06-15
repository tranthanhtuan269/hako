<?php

namespace App\Support;

final class Seo
{
    public static function title(string $pageTitle, bool $includeBrand = true): string
    {
        $pageTitle = trim($pageTitle);
        $brand = config('site.name');

        if (! $includeBrand || str_contains($pageTitle, $brand)) {
            return $pageTitle;
        }

        return "{$pageTitle} — {$brand}";
    }

    public static function description(string $text, int $max = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)));

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1), '.,;:!? ') . '…';
    }

    public static function canonical(?string $url = null): string
    {
        if ($url) {
            return $url;
        }

        return rtrim(config('site.url'), '/') . request()->getPathInfo();
    }

    public static function ogImage(?string $image = null): string
    {
        if ($image) {
            return self::absoluteUrl($image);
        }

        if ($configured = config('site.og_image')) {
            return self::absoluteUrl($configured);
        }

        $png = public_path('images/og-default.png');
        $svg = public_path('images/og-default.svg');

        if (file_exists($png)) {
            return self::absoluteUrl('/images/og-default.png');
        }

        if (file_exists($svg)) {
            return self::absoluteUrl('/images/og-default.svg');
        }

        return rtrim(config('site.url'), '/');
    }

    public static function absoluteUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return rtrim(config('site.url'), '/') . '/' . ltrim($path, '/');
    }
}
