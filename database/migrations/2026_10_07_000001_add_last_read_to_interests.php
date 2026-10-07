<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chats tab (Oct 2026): when each side last opened the conversation, so the
 * Chats list and menu can show unread messages. Existing conversations are
 * marked read as of now — otherwise every old chat would light up as unread.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interests', function (Blueprint $table) {
            if (! Schema::hasColumn('interests', 'sender_last_read_at')) {
                $table->timestamp('sender_last_read_at')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('interests', 'receiver_last_read_at')) {
                $table->timestamp('receiver_last_read_at')->nullable()->after('sender_last_read_at');
            }
        });

        DB::table('interests')->update([
            'sender_last_read_at' => now(),
            'receiver_last_read_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('interests', function (Blueprint $table) {
            $table->dropColumn(['sender_last_read_at', 'receiver_last_read_at']);
        });
    }
};
