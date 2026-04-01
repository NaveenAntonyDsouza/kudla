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
            // Change height columns from smallint to varchar (stores "143 cm" format)
            $table->string('height_from_cm', 20)->nullable()->change();
            $table->string('height_to_cm', 20)->nullable()->change();
            // Change physical_status from varchar to json (stores array)
            $table->json('physical_status')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('partner_preferences', function (Blueprint $table) {
            $table->smallInteger('height_from_cm')->unsigned()->nullable()->change();
            $table->smallInteger('height_to_cm')->unsigned()->nullable()->change();
            $table->string('physical_status', 30)->nullable()->change();
        });
    }
};
