<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * One admin-editable dropdown option, with an Active/Inactive flag.
 *
 * Backs the `reference_data_options` table and the Filament Reference
 * Data Options page (App\Filament\Resources\ReferenceDataOptionResource).
 * GatewayConfigProvider reads from this table at boot — for any
 * category that has rows here, the active values replace
 * config('reference_data.{category_list}').
 *
 * The cache key here matches the one GatewayConfigProvider checks
 * (`reference_data_options.{category}`). saved/deleted events bust the
 * cache for the affected category so admin edits take effect on the
 * very next request, not after a 1-hour TTL.
 *
 * Renamed `ReferenceDataOption` (not `ReferenceData`) so the model
 * name doesn't collide with anything called "reference data" elsewhere
 * in the namespace, and it's clear that this is "one option" not "the
 * whole concept".
 */
class ReferenceDataOption extends Model
{
    protected $table = 'reference_data_options';

    protected $fillable = [
        'category', 'value', 'label', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $invalidate = function (self $row): void {
            Cache::forget('reference_data_options.'.$row->category);

            // If the row was moved between categories, bust the source
            // too so its dropdown reflects the removal.
            if ($row->wasChanged('category')) {
                Cache::forget('reference_data_options.'.$row->getOriginal('category'));
            }
        };

        static::saved($invalidate);
        static::deleted($invalidate);
    }
}
