<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the "Other → Specify" pattern (already in use for diocese / religion)
     * to denomination and caste.
     *
     *  - religious_info.other_denomination_name  — typed denomination when the
     *    member picks 'Other' (Non-Catholic 'Other' row serves both groups —
     *    see note below).
     *  - religious_info.other_caste_name        — typed caste when the member
     *    picks 'Other (not listed)' / 'Other'.
     *
     * Note on Catholic 'Other': reference_data_options has a unique constraint
     * on (category, value), so a separate 'denomination/Other' row for the
     * Catholic group can't sit alongside the existing Non-Catholic one. A
     * Catholic member with a rare denomination still picks "Other" from the
     * Non-Catholic group; the Specify text input appears (it triggers on
     * denomination === 'Other'), and the actual denomination gets captured in
     * other_denomination_name. The functionality works; only the visual group
     * placement is a compromise. Lifting the unique constraint to
     * (category, value, group_label) would let us add a dedicated Catholic
     * 'Other' row — leaving that as a future refinement.
     *
     * Sub-caste is NOT extended here; it already implements the
     * functionally-equivalent in-place free-text pattern (the sub_caste
     * column itself stores the typed value when "Other" is picked).
     */
    public function up(): void
    {
        Schema::table('religious_info', function (Blueprint $table) {
            if (! Schema::hasColumn('religious_info', 'other_denomination_name')) {
                $table->string('other_denomination_name', 100)->nullable()->after('denomination');
            }
            if (! Schema::hasColumn('religious_info', 'other_caste_name')) {
                $table->string('other_caste_name', 100)->nullable()->after('caste');
            }
        });

        // Defensive cache bust — denomination_list reads through this cache,
        // and any prior partial deploy may have left stale state.
        Cache::forget('reference_data_options.denomination');
    }

    public function down(): void
    {
        Schema::table('religious_info', function (Blueprint $table) {
            if (Schema::hasColumn('religious_info', 'other_denomination_name')) {
                $table->dropColumn('other_denomination_name');
            }
            if (Schema::hasColumn('religious_info', 'other_caste_name')) {
                $table->dropColumn('other_caste_name');
            }
        });

        Cache::forget('reference_data_options.denomination');
    }
};
