<?php

use App\Models\ReferenceDataOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the "Other → Specify" pattern (already in use for diocese / religion)
     * to the Catholic denomination group and to caste.
     *
     *  - religious_info.other_denomination_name  — typed denomination when the
     *    member picks 'Other' (in either Catholic or Non-Catholic group).
     *  - religious_info.other_caste_name        — typed caste when the member
     *    picks 'Other (not listed)'.
     *  - reference_data_options                 — adds 'Other' to the
     *    Catholic denomination group (Non-Catholic already has one).
     *
     * Sub-caste is NOT extended here; it already implements the
     * functionally-equivalent in-place free-text pattern (the sub_caste
     * column itself stores the typed value when "Other" is picked).
     */
    public function up(): void
    {
        // 1) New specify columns on religious_info — both nullable to allow
        //    existing rows to keep validating.
        Schema::table('religious_info', function (Blueprint $table) {
            if (! Schema::hasColumn('religious_info', 'other_denomination_name')) {
                $table->string('other_denomination_name', 100)->nullable()->after('denomination');
            }
            if (! Schema::hasColumn('religious_info', 'other_caste_name')) {
                $table->string('other_caste_name', 100)->nullable()->after('caste');
            }
        });

        // 2) Seed 'Other' into the Catholic denomination group. group_label
        //    is mandatory here because denomination is rendered grouped
        //    (Catholic / Non-Catholic optgroups). Place it last in the
        //    group via a high sort_order so the canonical denominations
        //    keep their ordering at the top.
        ReferenceDataOption::updateOrCreate(
            ['category' => 'denomination', 'value' => 'Other', 'group_label' => 'Catholic'],
            [
                'label' => 'Other',
                'sort_order' => 999,
                'is_active' => true,
            ]
        );

        // 3) Bust the denomination cache so the new option shows on the next
        //    request (mass writes skip model events).
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

        ReferenceDataOption::where('category', 'denomination')
            ->where('value', 'Other')
            ->where('group_label', 'Catholic')
            ->delete();

        Cache::forget('reference_data_options.denomination');
    }
};
