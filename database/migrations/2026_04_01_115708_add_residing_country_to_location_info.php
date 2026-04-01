<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('location_info', function (Blueprint $table) {
            $table->string('residing_country', 100)->nullable()->after('profile_id');
        });
    }

    public function down(): void
    {
        Schema::table('location_info', function (Blueprint $table) {
            $table->dropColumn('residing_country');
        });
    }
};
