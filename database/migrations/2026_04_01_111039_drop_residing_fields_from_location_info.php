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
            $table->dropColumn(['country', 'state', 'city', 'citizenship', 'residency_status', 'grew_up_in', 'is_nri']);
        });
    }

    public function down(): void
    {
        Schema::table('location_info', function (Blueprint $table) {
            $table->string('country', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('citizenship', 100)->nullable();
            $table->string('residency_status', 50)->nullable();
            $table->string('grew_up_in', 100)->nullable();
            $table->boolean('is_nri')->default(false);
        });
    }
};
