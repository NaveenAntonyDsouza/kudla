<?php

use App\Models\Profile;
use App\Models\ReferenceDataOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Consolidate duplicate marital_status options to one canonical value each:
     *   "Divorced" -> "Divorcee", "Widower" -> "Widow/Widower".
     * Migrates the few profiles on the retired spelling to the canonical value
     * (same status, no information lost), then deactivates the duplicate
     * dropdown options so members see 6 distinct statuses instead of 8.
     */
    public function up(): void
    {
        // 1) Migrate profile data to the canonical values.
        Profile::where('marital_status', 'Divorced')->update(['marital_status' => 'Divorcee']);
        Profile::where('marital_status', 'Widower')->update(['marital_status' => 'Widow/Widower']);

        // 2) Hide the now-duplicate dropdown options.
        ReferenceDataOption::where('category', 'marital_status')
            ->whereIn('value', ['Divorced', 'Widower'])
            ->update(['is_active' => false]);

        // 3) Mass updates skip model events, so bust the reference-data cache
        //    for this category so the new list shows on the next request.
        Cache::forget('reference_data_options.marital_status');
    }

    public function down(): void
    {
        ReferenceDataOption::where('category', 'marital_status')
            ->whereIn('value', ['Divorced', 'Widower'])
            ->update(['is_active' => true]);
        Cache::forget('reference_data_options.marital_status');
        // Data migration not reversed — Divorcee/Widow-Widower are valid either way.
    }
};
