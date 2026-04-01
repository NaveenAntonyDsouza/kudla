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
        Schema::table('partner_preferences', function (Blueprint $table) {
            $table->json('income_range')->nullable()->after('employment_status');
            $table->json('da_category')->nullable()->after('physical_status');
        });
    }

    public function down(): void
    {
        Schema::table('partner_preferences', function (Blueprint $table) {
            $table->dropColumn(['income_range', 'da_category']);
        });
    }
};
