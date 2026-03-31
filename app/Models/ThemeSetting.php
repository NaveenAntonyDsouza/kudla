<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ThemeSetting extends Model
{
    protected $fillable = [
        'site_name',
        'tagline',
        'primary_color',
        'primary_hover',
        'primary_light',
        'secondary_color',
        'secondary_hover',
        'secondary_light',
        'logo_url',
        'favicon_url',
    ];

    public static function getTheme(): self
    {
        $theme = Cache::remember('theme_settings', 3600, function () {
            return static::first() ?? new self([
                'site_name' => 'Matrimony Platform',
                'tagline' => 'Find Your Perfect Match',
                'primary_color' => '#8B1D91',
                'primary_hover' => '#6B1571',
                'primary_light' => '#F3E8F7',
                'secondary_color' => '#00BCD4',
                'secondary_hover' => '#00ACC1',
                'secondary_light' => '#E0F7FA',
            ]);
        });

        // Guard against __PHP_Incomplete_Class from stale cache
        if (! $theme instanceof self) {
            Cache::forget('theme_settings');

            return static::first() ?? new self([
                'site_name' => 'Matrimony Platform',
                'tagline' => 'Find Your Perfect Match',
                'primary_color' => '#8B1D91',
                'primary_hover' => '#6B1571',
                'primary_light' => '#F3E8F7',
                'secondary_color' => '#00BCD4',
                'secondary_hover' => '#00ACC1',
                'secondary_light' => '#E0F7FA',
            ]);
        }

        return $theme;
    }
}
