<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Already reminded" markers for the finish-registration and add-a-photo
 * reminders, moved from the cache (wiped by any cache clear) to the users
 * table. Filled from the admin activity log, which records the Matri IDs
 * each run sent to, so members reminded before this change stay protected.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'registration_reminders_sent' => 'last_registration_reminder_at',
        'photo_reminders_sent' => 'last_photo_reminder_at',
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                if (! Schema::hasColumn('users', $column)) {
                    $table->timestamp($column)->nullable();
                }
            }
        });

        if (! Schema::hasTable('admin_activity_log')) {
            return;
        }

        foreach (self::COLUMNS as $action => $column) {
            $runs = DB::table('admin_activity_log')->where('action', $action)->orderBy('created_at')->get(['changes', 'created_at']);
            foreach ($runs as $run) {
                $matriIds = (array) (json_decode((string) $run->changes, true)['sent'] ?? []);
                foreach (array_chunk(array_filter($matriIds, 'is_string'), 200) as $chunk) {
                    $userIds = DB::table('profiles')->whereIn('matri_id', $chunk)->pluck('user_id');
                    // Later runs overwrite earlier ones (ordered by date)
                    DB::table('users')->whereIn('id', $userIds)->update([$column => $run->created_at]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(array_values(self::COLUMNS));
        });
    }
};
