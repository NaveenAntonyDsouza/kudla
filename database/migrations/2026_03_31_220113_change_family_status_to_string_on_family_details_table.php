<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_details', function (Blueprint $table) {
            $table->string('family_status', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('family_details', function (Blueprint $table) {
            $table->enum('family_status', ['middle_class', 'upper_middle', 'rich', 'affluent'])->nullable()->change();
        });
    }
};
