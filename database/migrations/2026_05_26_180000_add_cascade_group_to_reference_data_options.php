<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * cascade_group links a reference option to a parent grouping used for
     * CASCADING dropdowns — distinct from group_label, which is display-only
     * (and feeds config grouping). Currently used by Diocese: each diocese
     * row's cascade_group is its rite (Latin / Syro-Malabar / Syro-Malankara),
     * so the Denomination → Diocese cascade can show only the dioceses of the
     * selected denomination's rite. NULL for every other category.
     */
    public function up(): void
    {
        Schema::table('reference_data_options', function (Blueprint $t) {
            $t->string('cascade_group', 60)->nullable()->after('group_label');
        });
    }

    public function down(): void
    {
        Schema::table('reference_data_options', function (Blueprint $t) {
            $t->dropColumn('cascade_group');
        });
    }
};
