<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('suspension_status', 20)->default('active')->after('is_hidden'); // active, suspended, banned
            $table->text('suspension_reason')->nullable()->after('suspension_status');
            $table->timestamp('suspended_at')->nullable()->after('suspension_reason');
            $table->timestamp('suspension_ends_at')->nullable()->after('suspended_at'); // null = permanent
            $table->foreignId('suspended_by')->nullable()->after('suspension_ends_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropForeign(['suspended_by']);
            $table->dropColumn(['suspension_status', 'suspension_reason', 'suspended_at', 'suspension_ends_at', 'suspended_by']);
        });
    }
};
