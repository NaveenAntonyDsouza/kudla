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
            $table->string('residency_status', 50)->nullable()->after('native_district');
        });
    }

    public function down(): void
    {
        Schema::table('location_info', function (Blueprint $table) {
            $table->dropColumn('residency_status');
        });
    }
};
