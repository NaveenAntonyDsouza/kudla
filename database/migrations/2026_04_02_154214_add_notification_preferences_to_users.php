<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('is_active');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->string('show_profile_to', 30)->default('all')->after('is_active');
            $table->boolean('is_hidden')->default(false)->after('show_profile_to');
            $table->boolean('search_visible_to_older')->default(true)->after('is_hidden');
            $table->boolean('search_visible_to_taller')->default(true)->after('search_visible_to_older');
            $table->string('deletion_reason', 200)->nullable()->after('search_visible_to_taller');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['show_profile_to', 'is_hidden', 'search_visible_to_older', 'search_visible_to_taller', 'deletion_reason']);
        });
    }
};
