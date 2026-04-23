<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add heading_font + body_font columns to theme_settings.
     * Values store the CSS font-family name (e.g., "Playfair Display", "Inter").
     * Defaults match the platform's original fonts so existing sites keep their look.
     */
    public function up(): void
    {
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->string('heading_font', 100)->default('Playfair Display')->after('secondary_light');
            $table->string('body_font', 100)->default('Inter')->after('heading_font');
        });
    }

    public function down(): void
    {
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->dropColumn(['heading_font', 'body_font']);
        });
    }
};
