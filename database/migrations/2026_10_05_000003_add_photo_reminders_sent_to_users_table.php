<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * How many "add a photo" reminders a member has had (max 3). Members
 * reminded by the hand run of 2 Oct 2026 count as 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'photo_reminders_sent')) {
            Schema::table('users', fn (Blueprint $t) => $t->unsignedTinyInteger('photo_reminders_sent')->default(0));
        }
        if (Schema::hasColumn('users', 'last_photo_reminder_at')) {
            DB::table('users')->whereNotNull('last_photo_reminder_at')->where('photo_reminders_sent', 0)->update(['photo_reminders_sent' => 1]);
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('photo_reminders_sent'));
    }
};
