<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add medium-size variant + preserved original URL to profile_photos.
     *
     * Current columns:
     *   - photo_url     (was: the full uploaded image — now: the "full" display variant, ~1200px WebP)
     *   - thumbnail_url (was: unused/same as photo_url — now: thumb variant, ~200px WebP)
     *
     * Adding:
     *   - medium_url    — mid-size variant for card views (~600px WebP)
     *   - original_url  — preserved original upload (for admin download / reprocessing)
     */
    public function up(): void
    {
        Schema::table('profile_photos', function (Blueprint $table) {
            $table->string('medium_url', 500)->nullable()->after('thumbnail_url');
            $table->string('original_url', 500)->nullable()->after('medium_url');
        });
    }

    public function down(): void
    {
        Schema::table('profile_photos', function (Blueprint $table) {
            $table->dropColumn(['medium_url', 'original_url']);
        });
    }
};
