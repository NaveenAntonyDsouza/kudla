<?php

use App\Models\ReferenceDataOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Broaden reference_data_options' uniqueness from (category, value) to
     * (category, value, group_label) so the same value can live in two
     * different groups under the same category — e.g. denomination 'Other'
     * under both Catholic and Non-Catholic optgroups (previously the
     * Catholic 'Other' insert was blocked).
     *
     * NULL-group_label note: MySQL treats NULL as distinct in unique
     * indexes, so for ungrouped categories (group_label IS NULL) this
     * constraint effectively reduces to (category, value, NULL) — which
     * cannot collide. We only create rows via updateOrCreate at the
     * application layer, so duplicates can't sneak in that way either.
     *
     * After the constraint is broadened, seed the Catholic 'Other'
     * denomination row that v35's migration tried to insert.
     */
    public function up(): void
    {
        Schema::table('reference_data_options', function (Blueprint $table) {
            $table->dropUnique(['category', 'value']);
            $table->unique(['category', 'value', 'group_label'], 'rdo_category_value_group_unique');
        });

        ReferenceDataOption::updateOrCreate(
            ['category' => 'denomination', 'value' => 'Other', 'group_label' => 'Catholic'],
            [
                'label' => 'Other',
                'sort_order' => 999,
                'is_active' => true,
            ]
        );

        Cache::forget('reference_data_options.denomination');
    }

    public function down(): void
    {
        ReferenceDataOption::where('category', 'denomination')
            ->where('value', 'Other')
            ->where('group_label', 'Catholic')
            ->delete();

        Schema::table('reference_data_options', function (Blueprint $table) {
            $table->dropUnique('rdo_category_value_group_unique');
            $table->unique(['category', 'value']);
        });

        Cache::forget('reference_data_options.denomination');
    }
};
