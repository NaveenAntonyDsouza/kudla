<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'staff_role_id')) {
                $table->foreignId('staff_role_id')->nullable()->after('role')->constrained('staff_roles')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'staff_role_id')) {
                $table->dropConstrainedForeignId('staff_role_id');
            }
        });
    }
};
