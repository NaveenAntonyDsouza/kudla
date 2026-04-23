<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track which storage disk each photo lives on.
     *
     * Enables "hybrid mode" — old photos stay on their original disk; new uploads
     * follow the currently-selected SiteSetting `active_storage_driver`.
     *
     * Possible values: 'public' (local), 'cloudinary', 'r2'
     */
    public function up(): void
    {
        Schema::table('profile_photos', function (Blueprint $table) {
            $table->string('storage_driver', 30)->default('public')->after('original_url');
            $table->index('storage_driver');
        });
    }

    public function down(): void
    {
        Schema::table('profile_photos', function (Blueprint $table) {
            $table->dropIndex(['storage_driver']);
            $table->dropColumn('storage_driver');
        });
    }
};
