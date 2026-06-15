<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

class ThemeManager
{
    public static function all(): array
    {
        return config('themes.themes', []);
    }

    public static function current(): string
    {
        $default = config('themes.default', 'classic');

        try {
            if (! Schema::hasTable('site_settings')) {
                return $default;
            }

            $key = SiteSetting::get('frontend_theme', $default);
            $themes = static::all();

            if (! array_key_exists($key, $themes)) {
                return $default;
            }

            return $key;
        } catch (Throwable) {
            return $default;
        }
    }

    public static function currentConfig(): array
    {
        $key = static::current();

        return static::all()[$key];
    }

    public static function cssPath(): string
    {
        return static::currentConfig()['css'];
    }

    public static function fontUrl(): string
    {
        return static::currentConfig()['font'];
    }

    public static function jsPath(): ?string
    {
        return static::currentConfig()['js'] ?? null;
    }

    public static function is(string $key): bool
    {
        return static::current() === $key;
    }

    public static function set(string $key): void
    {
        if (! array_key_exists($key, static::all())) {
            throw new InvalidArgumentException("Unknown theme: {$key}");
        }

        SiteSetting::set('frontend_theme', $key);
    }
}
