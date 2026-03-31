<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, $default = null): ?string
    {
        return Cache::remember("site_setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function setValue(string $key, string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("site_setting.{$key}");
        Cache::forget('site_settings.all');
    }

    public static function getAll(): Collection
    {
        return Cache::remember('site_settings.all', 3600, function () {
            return static::all();
        });
    }
}
