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
        Schema::table('lifestyle_info', function (Blueprint $table) {
            $table->string('diet', 30)->nullable()->change();
            $table->string('smoking', 20)->nullable()->change();
            $table->string('drinking', 20)->nullable()->change();
            $table->string('cultural_background', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        // No reversal needed
    }
};
