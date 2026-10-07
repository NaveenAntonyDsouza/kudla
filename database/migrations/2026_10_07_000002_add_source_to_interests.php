<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where an interest was sent from — profile page, a profile card, the phone
 * action bar or the app (Oct 2026), so the admin reports can show which
 * buttons members use. Earlier interests stay empty ("before tracking").
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('interests', 'source')) {
            Schema::table('interests', function (Blueprint $table) {
                $table->string('source', 20)->nullable()->after('custom_message');
            });
        }
    }

    public function down(): void
    {
        Schema::table('interests', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
