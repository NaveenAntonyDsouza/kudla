<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('height_cm');
        });

        // Also expand height column to accommodate full reference data strings
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('height', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->smallInteger('height_cm')->unsigned()->nullable()->after('complexion');
            $table->string('height', 30)->nullable()->change();
        });
    }
};
