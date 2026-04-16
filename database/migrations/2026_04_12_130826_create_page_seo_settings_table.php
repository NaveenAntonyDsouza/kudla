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
        Schema::create('page_seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('page_slug', 50)->unique(); // home, search, login, register, etc.
            $table->string('page_label', 100);          // Human-readable: "Home Page", "Search"
            $table->string('meta_title', 200)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_image_url', 500)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_seo_settings');
    }
};
