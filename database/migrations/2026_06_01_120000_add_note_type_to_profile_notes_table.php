<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tag each profile note with the interaction type (call / whatsapp / email /
 * meeting / walk_in / general) so the staff interaction log is scannable and
 * can be reported on. Existing + system-created notes default to 'general'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_notes', function (Blueprint $table) {
            $table->string('note_type', 20)->default('general')->after('admin_user_id');
            $table->index('note_type');
        });
    }

    public function down(): void
    {
        Schema::table('profile_notes', function (Blueprint $table) {
            $table->dropIndex(['note_type']);
            $table->dropColumn('note_type');
        });
    }
};
