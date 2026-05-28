<?php

use App\Models\ReferenceDataOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Expand the mother_tongue dropdown from 17 to 63 languages, with the
     * top 13 prioritised for this community (Mangalore region + national +
     * Gulf-diaspora Arabic), then Other Indian alphabetical, then
     * International alphabetical. Idempotent: re-running updates the
     * sort_order/is_active for existing rows and inserts only what's missing.
     */
    public function up(): void
    {
        $languages = [
            // Top tier
            'Tulu', 'Konkani', 'Kannada', 'Beary', 'Kodava', 'Malayalam',
            'English', 'Hindi', 'Tamil', 'Telugu', 'Marathi', 'Urdu', 'Arabic',
            // Other Indian
            'Assamese', 'Awadhi', 'Bengali', 'Bhojpuri', 'Bodo', 'Chhattisgarhi',
            'Dogri', 'Garhwali', 'Gujarati', 'Haryanvi', 'Kashmiri', 'Khasi',
            'Kumaoni', 'Magahi', 'Maithili', 'Manipuri', 'Marwari', 'Mizo',
            'Nepali', 'Odia', 'Punjabi', 'Rajasthani', 'Sanskrit', 'Santali', 'Sindhi',
            // International
            'Chinese', 'Dutch', 'Filipino', 'French', 'German', 'Greek', 'Hebrew',
            'Indonesian', 'Italian', 'Japanese', 'Korean', 'Malay', 'Norwegian',
            'Persian', 'Polish', 'Portuguese', 'Romanian', 'Russian', 'Sinhala',
            'Spanish', 'Swahili', 'Swedish', 'Thai', 'Turkish', 'Vietnamese',
        ];

        foreach ($languages as $idx => $value) {
            ReferenceDataOption::updateOrCreate(
                ['category' => 'mother_tongue', 'value' => $value],
                [
                    'label' => $value,
                    'sort_order' => $idx + 1,
                    'is_active' => true,
                ]
            );
        }

        // Mass updateOrCreate writes skip model events, so the reference-data
        // cache for mother_tongue won't auto-bust. Clear it explicitly so the
        // new list shows on the next request.
        Cache::forget('reference_data_options.mother_tongue');
    }

    public function down(): void
    {
        // Not reversible by design — additions are non-destructive and members
        // may have already selected newly-added languages. Drop only the cache.
        Cache::forget('reference_data_options.mother_tongue');
    }
};
