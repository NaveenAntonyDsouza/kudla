<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Branch commission payouts — one row per branch per period (typically monthly).
     *
     * Amounts are FROZEN at calc time:
     *   - gross_revenue_paise: captured revenue for the period
     *   - commission_pct: rate at time of calculation (branch.commission_pct may change later)
     *   - payout_amount_paise: gross × pct / 100 (rounded)
     *
     * This freezing ensures historical accuracy — later rate/revenue changes don't
     * corrupt past payout records.
     */
    public function up(): void
    {
        Schema::create('branch_payouts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            // Period covered by this payout (typically a calendar month)
            $table->date('period_start');
            $table->date('period_end');

            // Frozen snapshot at calculation time
            $table->unsignedInteger('gross_revenue_paise')->default(0);
            $table->decimal('commission_pct', 5, 2)->default(0.00);
            $table->unsignedInteger('payout_amount_paise')->default(0);

            // Lifecycle
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->date('paid_on')->nullable();
            $table->string('transaction_reference')->nullable(); // bank ref, UPI ID, cheque #
            $table->text('notes')->nullable();

            // Audit
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Prevent duplicate payouts for the same branch/period
            $table->unique(['branch_id', 'period_start'], 'uniq_branch_period');

            // Common queries
            $table->index('status');
            $table->index('period_start');
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_payouts');
    }
};
