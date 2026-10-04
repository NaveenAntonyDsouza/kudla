<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * How many "finish your registration" reminders a member has had (max 3).
 * Members reminded by the hand runs of 2 Oct 2026 count as 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'registration_reminders_sent')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedTinyInteger('registration_reminders_sent')->default(0);
            });
        }

        if (Schema::hasColumn('users', 'last_registration_reminder_at')) {
            DB::table('users')->whereNotNull('last_registration_reminder_at')->where('registration_reminders_sent', 0)
                ->update(['registration_reminders_sent' => 1]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('registration_reminders_sent');
        });
    }
};
