<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Email logs never stored which member each email went to (Oct 2026). Fill it
 * in for the emails already logged, by matching the recipient address.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE email_logs SET user_id = (SELECT users.id FROM users WHERE users.email = email_logs.recipient LIMIT 1)
            WHERE user_id IS NULL AND recipient IS NOT NULL');
    }

    public function down(): void
    {
        // Nothing to undo: the filled-in ids are correct either way.
    }
};
