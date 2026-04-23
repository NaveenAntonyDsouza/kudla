<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add branch_id (nullable FK to branches) to all tables that need branch scoping.
     * NULL means "head office" / "global" — Phase 1.4.2 will backfill defaults.
     */
    public function up(): void
    {
        // 1. users — staff and members both can belong to a branch
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('staff_role_id')
                ->constrained('branches')
                ->nullOnDelete();
            $table->index('branch_id');
        });

        // 2. profiles — which branch the profile is registered under
        Schema::table('profiles', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('created_by_staff_id')
                ->constrained('branches')
                ->nullOnDelete();
            $table->index('branch_id');
        });

        // 3. leads — which branch the lead belongs to
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('assigned_to_staff_id')
                ->constrained('branches')
                ->nullOnDelete();
            $table->index('branch_id');
        });

        // 4. subscriptions — for revenue attribution per branch
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('user_id')
                ->constrained('branches')
                ->nullOnDelete();
            $table->index('branch_id');
        });

        // 5. coupons — branch-restricted coupons (NULL = global)
        Schema::table('coupons', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('id')
                ->constrained('branches')
                ->nullOnDelete();
            $table->index('branch_id');
        });

        // 6. staff_targets — Phase 1.3.5 targets can be branch-scoped (future use)
        Schema::table('staff_targets', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('staff_user_id')
                ->constrained('branches')
                ->nullOnDelete();
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        foreach (['users', 'profiles', 'leads', 'subscriptions', 'coupons', 'staff_targets'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
