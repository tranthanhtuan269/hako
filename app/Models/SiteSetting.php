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
        return Cache::rememberForever('site_setting.'.$key, function () use ($key, $default) {
            $row = static::query()->find($key);

            return $row?->value ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('site_setting.'.$key);
    }
}
