<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /** Cached in place of a setting that isn't saved, so the caller's default isn't. */
    private const NOT_SET = "\0not-set";

    public static function getValue(string $key, $default = null): ?string
    {
        // Cache what's stored, never the default: callers pass different
        // defaults, and caching the first one gave everyone else that value
        // for an hour (10 Oct 2026: a check script's "(default)" made the
        // re-engagement cap read as 1).
        $value = Cache::remember("site_setting.{$key}", 3600, function () use ($key) {
            $setting = static::where('key', $key)->first();

            return $setting ? $setting->value : self::NOT_SET;
        });

        return $value === self::NOT_SET ? $default : $value;
    }

    public static function setValue(string $key, string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("site_setting.{$key}");
        Cache::forget('site_settings.all');
        Cache::forget('api:v1:site-settings'); // the app's /site/settings snapshot
    }

    /**
     * Whether Hindu members must pick a caste/community. On by default
     * (matrimony); a site can switch it off (e.g. a dating site) via the
     * `caste_required` setting.
     */
    public static function casteRequired(): bool
    {
        try {
            return static::getValue('caste_required', '1') === '1';
        } catch (QueryException) {
            // Validation rules are also built where no site_settings table
            // exists (fresh install, tests with a partial schema). Fall back
            // to the matrimony default rather than blowing up rule building.
            return true;
        }
    }

    /**
     * Validation rule for the `caste` field. Only Hindus are ever required
     * to give one — Jains pick a sect instead and never see a caste field.
     */
    public static function casteRule(string $extra = ''): string
    {
        $rule = static::casteRequired() ? 'nullable|required_if:religion,Hindu|string' : 'nullable|string';

        return $extra === '' ? $rule : $rule.'|'.$extra;
    }

    public static function getAll(): Collection
    {
        return Cache::remember('site_settings.all', 3600, function () {
            return static::all();
        });
    }
}
