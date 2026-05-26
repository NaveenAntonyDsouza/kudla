<?php

use App\Models\Community;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Add a managed "Other / Not Listed" community for Hindu & Jain.
     *
     * Caste is required for Hindu/Jain registrations and the caste field is a
     * closed dropdown sourced from the Communities table (deliberately no
     * free-text, to keep the primary match/search dimension clean). Without an
     * escape option, a prospective member whose community isn't in the curated
     * list cannot finish signup. This adds a single controlled value per
     * religion as that escape — matchable, no fragmentation — sorted last so
     * members see the real communities first.
     *
     * Reversible: deactivate it on the Communities admin page (one toggle), or
     * roll this migration back. sort_order capped at 99 (column is an unsigned
     * tinyInteger, max 255; existing values top out at 21). firstOrCreate keeps
     * it idempotent and won't duplicate a row an admin may already have added.
     */
    public function up(): void
    {
        foreach (['Hindu', 'Jain'] as $religion) {
            Community::firstOrCreate(
                ['religion' => $religion, 'community_name' => 'Other / Not Listed'],
                ['sub_communities' => [], 'is_active' => true, 'sort_order' => 99],
            );
        }
    }

    public function down(): void
    {
        Community::query()
            ->whereIn('religion', ['Hindu', 'Jain'])
            ->where('community_name', 'Other / Not Listed')
            ->delete();
    }
};
