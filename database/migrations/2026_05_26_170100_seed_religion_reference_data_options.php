<?php

use App\Models\ReferenceDataOption;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Move the religion dropdown lists out of config / the textarea editor and
     * into the per-row reference_data_options table, so they're all managed
     * from one well-structured admin page (with Active toggles + sort order).
     *
     * Reads each list's CURRENT effective value from config (file default, or
     * any existing textarea/site_settings override) so nothing changes for
     * users. Idempotent via firstOrCreate on (category, value). Religion itself
     * is already seeded elsewhere, so it's not repeated here. Denomination is
     * grouped (Catholic / Non-Catholic) and stored with group_label set.
     */
    public function up(): void
    {
        // Flat lists:  table category  =>  config key
        $flat = [
            'diocese' => 'diocese_list',
            'muslim_sect' => 'muslim_sect_list',
            'jain_sect' => 'jain_sect_list',
            'religious_observance' => 'religious_observance_list',
            'gothram' => 'gothram_list',
            'nakshatra' => 'nakshatra_list',
            'rasi' => 'rasi_list',
            'jamath' => 'jamath_list',
        ];

        foreach ($flat as $category => $configKey) {
            $order = 0;
            foreach ((array) config("reference_data.$configKey", []) as $value) {
                if (! is_string($value) || $value === '') {
                    continue;
                }
                ReferenceDataOption::firstOrCreate(
                    ['category' => $category, 'value' => $value],
                    ['group_label' => null, 'sort_order' => $order, 'is_active' => true],
                );
                $order += 10;
            }
        }

        // Grouped list: Denomination (Catholic / Non-Catholic).
        $order = 0;
        foreach ((array) config('reference_data.denomination_list', []) as $group => $values) {
            if (! is_array($values)) {
                continue; // skip any stray flat entry defensively
            }
            foreach ($values as $value) {
                if (! is_string($value) || $value === '') {
                    continue;
                }
                ReferenceDataOption::firstOrCreate(
                    ['category' => 'denomination', 'value' => $value],
                    ['group_label' => (string) $group, 'sort_order' => $order, 'is_active' => true],
                );
                $order += 10;
            }
        }
    }

    public function down(): void
    {
        ReferenceDataOption::query()->whereIn('category', [
            'diocese', 'muslim_sect', 'jain_sect', 'religious_observance',
            'gothram', 'nakshatra', 'rasi', 'jamath', 'denomination',
        ])->delete();
    }
};
