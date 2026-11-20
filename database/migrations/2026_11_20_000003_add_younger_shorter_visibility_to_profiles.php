<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settings -> Who Can See My Profile (owner 20 Nov 2026): men choose "Show to members older / taller
 * than me", women "Show to members younger / shorter than me". The older / taller columns exist since
 * April; these are the women's two. On (true) for everyone, so nobody's profile disappears.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('profiles', 'search_visible_to_younger')) {
                $table->boolean('search_visible_to_younger')->default(true)->after('search_visible_to_taller');
            }
            if (! Schema::hasColumn('profiles', 'search_visible_to_shorter')) {
                $table->boolean('search_visible_to_shorter')->default(true)->after('search_visible_to_younger');
            }
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['search_visible_to_younger', 'search_visible_to_shorter']);
        });
    }
};
