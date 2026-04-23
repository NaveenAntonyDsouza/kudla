<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('profiles', 'is_vip')) {
                $table->boolean('is_vip')->default(false)->after('id_proof_verified');
            }
            if (!Schema::hasColumn('profiles', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_vip');
            }
        });

        // Add composite index separately to avoid issues if it already exists
        Schema::table('profiles', function (Blueprint $table) {
            try {
                $table->index(['is_vip', 'is_featured'], 'profiles_vip_featured_index');
            } catch (\Throwable $e) {
                // Index may already exist, ignore
            }
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            try {
                $table->dropIndex('profiles_vip_featured_index');
            } catch (\Throwable $e) {
                // Index may not exist, ignore
            }
            $table->dropColumn(['is_vip', 'is_featured']);
        });
    }
};
