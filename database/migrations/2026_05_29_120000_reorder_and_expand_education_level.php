<?php

use App\Models\ReferenceDataOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Reorder the education_level dropdown by academic progression and add two
     * missing bands: "Below High School" and "Higher Secondary (12th / PUC)".
     * PG Diploma moves from last (after PhD) to between Bachelor's and Master's.
     *
     * education_level is DB-overridden (reference_data_options category
     * education_level → config education_level_list), so the live order is
     * sort_order here. Idempotent: existing rows get their sort_order updated,
     * the two new bands are inserted. No existing values are renamed or removed,
     * so profiles already on "High School"/"PhD"/etc. keep their value.
     */
    public function up(): void
    {
        $ordered = [
            'Below High School',
            'High School',
            'Higher Secondary (12th / PUC)',
            'Diploma',
            "Bachelor's",
            'PG Diploma',
            "Master's",
            'PhD',
        ];

        foreach ($ordered as $idx => $value) {
            ReferenceDataOption::updateOrCreate(
                ['category' => 'education_level', 'value' => $value],
                [
                    'label' => $value,
                    'sort_order' => $idx + 1,
                    'is_active' => true,
                ]
            );
        }

        // Mass updateOrCreate writes skip model events — bust the cache so the
        // new order + new bands show on the next request.
        Cache::forget('reference_data_options.education_level');
    }

    public function down(): void
    {
        // Remove only the two newly-added bands; leave the original six and
        // their (now-reordered) sort_order in place — reverting order is not
        // worth the churn and members may already be on the new values.
        ReferenceDataOption::where('category', 'education_level')
            ->whereIn('value', ['Below High School', 'Higher Secondary (12th / PUC)'])
            ->delete();

        Cache::forget('reference_data_options.education_level');
    }
};
