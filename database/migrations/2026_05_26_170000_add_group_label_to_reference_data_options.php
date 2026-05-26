<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional group/heading for dropdown lists that render grouped, e.g.
     * Denomination → Catholic / Non-Catholic. NULL for ordinary flat lists
     * (the existing 25 categories), so their behaviour is unchanged. This
     * lets the per-row reference_data_options table model the one grouped
     * religion list without a separate structure — the boot loader rebuilds
     * a grouped array when any row in a category carries a group_label.
     *
     * Named group_label (not `group`) to avoid the SQL reserved word.
     */
    public function up(): void
    {
        Schema::table('reference_data_options', function (Blueprint $t) {
            $t->string('group_label', 80)->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('reference_data_options', function (Blueprint $t) {
            $t->dropColumn('group_label');
        });
    }
};
