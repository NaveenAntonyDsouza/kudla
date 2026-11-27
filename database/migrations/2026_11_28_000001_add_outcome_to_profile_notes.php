<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff numbers (owner, 27 Nov 2026, Step 1 of the staff analytics plan): a call note keeps
 * what happened on the call (spoke, no_answer, call_back, wrong_number, mismatch,
 * not_looking), so Staff Activity counts each answer from one place. Until now a call to a
 * member who is not on the calls list kept its answer only in the note's text. Older notes
 * stay empty ("answer not saved"); nothing is filled in after the fact.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('profile_notes') && ! Schema::hasColumn('profile_notes', 'outcome')) {
            Schema::table('profile_notes', function (Blueprint $table) {
                $table->string('outcome', 20)->nullable()->after('note_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('profile_notes') && Schema::hasColumn('profile_notes', 'outcome')) {
            Schema::table('profile_notes', function (Blueprint $table) {
                $table->dropColumn('outcome');
            });
        }
    }
};
