<?php

use App\Models\Branch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill branch_id on existing data so nothing is "branch-orphaned"
     * once scoping kicks in (Stage 2).
     *
     * Strategy:
     * - users, profiles, leads, subscriptions: NULL -> Head Office
     * - coupons: keep NULL (NULL means "global, all branches")
     * - staff_targets: no rows yet, skip
     */
    public function up(): void
    {
        // Find or create Head Office (idempotent — should exist from BranchesSeeder)
        $ho = Branch::where('is_head_office', true)->first();
        if (!$ho) {
            $ho = Branch::create([
                'name' => 'Head Office',
                'code' => 'HO',
                'location' => 'Mangalore',
                'state' => 'Karnataka',
                'is_active' => true,
                'is_head_office' => true,
                'notes' => 'Auto-created during backfill migration.',
            ]);
        }

        $haoId = $ho->id;

        $tablesToBackfill = ['users', 'profiles', 'leads', 'subscriptions'];

        foreach ($tablesToBackfill as $table) {
            $count = DB::table($table)->whereNull('branch_id')->count();
            if ($count > 0) {
                DB::table($table)
                    ->whereNull('branch_id')
                    ->update(['branch_id' => $haoId]);

                echo "  ✓ Backfilled {$count} rows in '{$table}' to Head Office (id={$haoId})\n";
            }
        }
    }

    public function down(): void
    {
        // Revert: NULL out branch_id on backfilled tables (only for HO records,
        // so we don't lose explicit assignments made after this migration ran)
        $ho = Branch::where('is_head_office', true)->first();
        if (!$ho) {
            return;
        }

        $tables = ['users', 'profiles', 'leads', 'subscriptions'];
        foreach ($tables as $table) {
            DB::table($table)
                ->where('branch_id', $ho->id)
                ->update(['branch_id' => null]);
        }
    }
};
