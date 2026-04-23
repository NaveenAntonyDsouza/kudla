<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('profiles', 'created_by_staff_id')) {
                $table->foreignId('created_by_staff_id')
                    ->nullable()
                    ->after('creator_name')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            if (Schema::hasColumn('profiles', 'created_by_staff_id')) {
                $table->dropConstrainedForeignId('created_by_staff_id');
            }
        });
    }
};
