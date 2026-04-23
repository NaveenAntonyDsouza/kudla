<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('ad_space', 50)->index(); // homepage_banner, sidebar, search_results, footer_banner, mobile_banner
            $table->string('type', 20)->default('image'); // image, html
            $table->string('image_path', 500)->nullable();
            $table->string('click_url', 500)->nullable();
            $table->text('html_code')->nullable();
            $table->string('advertiser_name', 100)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('priority')->default(0); // Higher = shown first
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
