<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the current commission percentage to each branch.
     * Default 0.00 — admin must explicitly set a rate (avoids accidental liability).
     * Rate can change over time; historical payouts snapshot the rate at calc time
     * in branch_payouts.commission_pct.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->decimal('commission_pct', 5, 2)
                ->default(0.00)
                ->after('is_head_office')
                ->comment('Current commission rate paid to this branch on subscription revenue');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('commission_pct');
        });
    }
};
