<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 15)->unique()->nullable()->after('email');
            $table->enum('role', ['user', 'admin', 'moderator', 'support'])->default('user')->after('phone');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->boolean('is_active')->default(true)->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'role', 'phone_verified_at', 'is_active']);
        });
    }
};
