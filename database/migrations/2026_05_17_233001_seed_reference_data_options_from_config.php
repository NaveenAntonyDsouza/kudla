<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Seed reference_data_options with the current canonical values from
 * config/reference_data.php for ~25 flat lists.
 *
 * Idempotent — insertOrIgnore relies on the (category, value) unique
 * index, so re-running is harmless. Existing admin-set rows are
 * preserved if this re-runs after an admin has edited values.
 *
 * Each seeded option gets is_active = true. Admin can flip individual
 * rows to inactive via the new Filament Reference Data Options page.
 *
 * Categories NOT seeded here:
 *   - educational_qualifications_list, occupation_category_list,
 *     country_list, denomination_list, diocese_list, how_did_you_hear_list
 *     → these have GROUPED structure; managed via the existing textarea
 *     ReferenceDataEditor + site_settings JSON path.
 *   - caste_list, sub_caste_list, rasi_list, nakshatra_list, gothram_list,
 *     muslim_sect_list, jain_sect_list, jamath_list
 *     → community-specific lookups with relationships; left in config.
 *   - height_list, weight_list
 *     → universal physical measurements, unlikely to need admin edits;
 *     ~80-100 rows would clutter the admin UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        // category name in the new table → config key in reference_data.php
        $categories = [
            'complexion' => 'complexion_list',
            'body_type' => 'body_type_list',
            'blood_group' => 'blood_group_list',
            'physical_status' => 'physical_status_list',
            'mother_tongue' => 'language_list',
            'religion' => 'religion_list',
            'family_status' => 'family_status_list',
            'residency_status' => 'residency_status_list',
            'preferred_call_time' => 'preferred_call_time_list',
            'marital_status' => 'marital_status_list',
            'custodian_relation' => 'custodian_relation_list',
            'created_by' => 'created_by_list',
            'education_level' => 'education_level_list',
            'employment_category' => 'employment_category_list',
            'diet' => 'eating_habits',
            'drinking' => 'drinking_habits',
            'smoking' => 'smoking_habits',
            'cultural_background' => 'cultural_background_list',
            'hobbies' => 'hobbies_list',
            'music' => 'music_list',
            'books' => 'books_list',
            'movies' => 'movies_list',
            'sports' => 'sports_list',
            'cuisine' => 'cuisine_list',
            'annual_income' => 'annual_income_list',
        ];

        $now = now();

        foreach ($categories as $category => $configKey) {
            $values = config('reference_data.'.$configKey, []);
            if (! is_array($values) || empty($values)) {
                continue;
            }

            $rows = [];
            foreach (array_values($values) as $index => $value) {
                $rows[] = [
                    'category' => $category,
                    'value' => (string) $value,
                    'label' => null,
                    'sort_order' => $index,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('reference_data_options')->insertOrIgnore($rows);

            // Bust the runtime cache so the next request sees the freshly
            // seeded rows (matches the cache key pattern used by the
            // GatewayConfigProvider override + ReferenceDataOption model
            // boot events).
            Cache::forget('reference_data_options.'.$category);
        }
    }

    public function down(): void
    {
        DB::table('reference_data_options')->delete();
    }
};
