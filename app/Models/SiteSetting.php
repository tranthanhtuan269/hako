<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'key';

    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = 'site_setting.'.$key;

        try {
            return Cache::rememberForever($cacheKey, function () use ($key, $default) {
                $row = static::query()->find($key);

                return $row?->value ?? $default;
            });
        } catch (\Throwable) {
            // Cache may be unwritable (bad ownership under storage/framework/cache).
            // Fall back to DB so admin pages like Branding do not 500.
            $row = static::query()->find($key);

            return $row?->value ?? $default;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        try {
            Cache::forget('site_setting.'.$key);
        } catch (\Throwable) {
            // Ignore cache clear failures; value is already persisted.
        }
    }
}
