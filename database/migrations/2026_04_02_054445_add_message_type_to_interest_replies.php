<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE interest_replies MODIFY COLUMN reply_type ENUM('accept', 'decline', 'message') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE interest_replies MODIFY COLUMN reply_type ENUM('accept', 'decline') NOT NULL");
    }
};
