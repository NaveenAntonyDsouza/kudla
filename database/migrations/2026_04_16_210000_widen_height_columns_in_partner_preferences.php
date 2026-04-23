<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_preferences', function (Blueprint $table) {
            $table->string('height_from_cm', 50)->nullable()->change();
            $table->string('height_to_cm', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('partner_preferences', function (Blueprint $table) {
            $table->string('height_from_cm', 20)->nullable()->change();
            $table->string('height_to_cm', 20)->nullable()->change();
        });
    }
};
