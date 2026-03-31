<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name', 255);
            $table->string('tagline', 255)->nullable();
            $table->string('primary_color', 7)->default('#8B1D91');
            $table->string('primary_hover', 7)->default('#6B1571');
            $table->string('primary_light', 7)->default('#F3E8F7');
            $table->string('secondary_color', 7)->default('#00BCD4');
            $table->string('secondary_hover', 7)->default('#00ACC1');
            $table->string('secondary_light', 7)->default('#E0F7FA');
            $table->string('logo_url', 500)->nullable();
            $table->string('favicon_url', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
    }
};
