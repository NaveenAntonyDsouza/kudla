<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Reads reference data from site_settings DB (admin-edited) first,
 * falls back to config/reference_data.php if no DB override exists.
 *
 * Usage: ReferenceDataService::get('educational_qualifications_list')
 */
class ReferenceDataService
{
    /**
     * Get reference data by key.
     * Checks DB override (set via Reference Data Editor) first,
     * falls back to config/reference_data.php.
     */
    public static function get(string $key, $default = []): array
    {
        return Cache::remember("ref_data.{$key}", 3600, function () use ($key, $default) {
            $dbKey = 'ref_data_' . $key;
            $dbValue = SiteSetting::where('key', $dbKey)->value('value');

            if ($dbValue) {
                $decoded = json_decode($dbValue, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }

            return config("reference_data.{$key}", $default);
        });
    }

    /**
     * Get a flat list (for simple select dropdowns).
     * For grouped arrays, flattens all values into a single list.
     */
    public static function getFlat(string $key, $default = []): array
    {
        $data = self::get($key, $default);

        // If it's a grouped array, flatten it
        $flat = [];
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $flat = array_merge($flat, $v);
            } else {
                $flat[] = $v;
            }
        }

        return $flat;
    }

    /**
     * Get as key => value options for select dropdowns.
     * Returns ['value' => 'value'] pairs.
     */
    public static function getOptions(string $key, $default = []): array
    {
        $items = self::getFlat($key, $default);

        return array_combine($items, $items);
    }

    /**
     * Get as grouped options for select dropdowns (with optgroups).
     * Returns ['Group' => ['value' => 'value']] pairs.
     */
    public static function getGroupedOptions(string $key, $default = []): array
    {
        $data = self::get($key, $default);
        $grouped = [];

        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $grouped[$k] = array_combine($v, $v);
            } else {
                $grouped[$v] = $v;
            }
        }

        return $grouped;
    }

    /**
     * Clear cache for a specific key or all reference data.
     */
    public static function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget("ref_data.{$key}");
        } else {
            // Clear all reference data caches
            $keys = array_keys(\App\Filament\Pages\ReferenceDataEditor::categories());
            foreach ($keys as $k) {
                Cache::forget("ref_data.{$k}");
            }
        }
    }
}
