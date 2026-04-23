<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-type privacy levels — matches how Shaadi/BharatMatrimony present privacy.
     *
     * Previously: one `privacy_level` applies to ALL photos.
     * Now: each photo type has its own visibility setting.
     *
     * Example: user wants profile photo public, album photos only to contacts,
     * family photos hidden entirely. The new columns express this cleanly.
     *
     * Defaults match the legacy single-level behavior for backward compatibility.
     */
    public function up(): void
    {
        Schema::table('photo_privacy_settings', function (Blueprint $table) {
            $table->string('profile_photo_privacy', 30)->default('visible_to_all')->after('privacy_level');
            $table->string('album_photos_privacy', 30)->default('visible_to_all')->after('profile_photo_privacy');
            $table->string('family_photos_privacy', 30)->default('interest_accepted')->after('album_photos_privacy');
        });
    }

    public function down(): void
    {
        Schema::table('photo_privacy_settings', function (Blueprint $table) {
            $table->dropColumn([
                'profile_photo_privacy',
                'album_photos_privacy',
                'family_photos_privacy',
            ]);
        });
    }
};
