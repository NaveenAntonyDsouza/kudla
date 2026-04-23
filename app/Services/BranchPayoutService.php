<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchPayout;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * BranchPayoutService — calculates and manages branch commission payouts.
 *
 * Calculation rule:
 *   gross_revenue = SUM(subscriptions.amount WHERE branch_id = X
 *                       AND payment_status = 'paid'
 *                       AND created_at IN [period_start, period_end])
 *   payout_amount = ROUND(gross_revenue * commission_pct / 100)
 *
 * Frozen at calc time:
 *   - commission_pct (snapshot — branch.commission_pct may change later)
 *   - gross_revenue_paise
 *   - payout_amount_paise
 *
 * Idempotent: generateForMonth() skips branches that already have a row for that period.
 */
class BranchPayoutService
{
    /**
     * Calculate payout figures for one branch over a period (typically a calendar month).
     *
     * @return array{gross_revenue_paise: int, commission_pct: float, payout_amount_paise: int}
     */
    public function calculateForBranch(Branch $branch, Carbon $periodStart, ?Carbon $periodEnd = null): array
    {
        $periodEnd = $periodEnd ?? $periodStart->copy()->endOfMonth();

        $grossPaise = (int) Subscription::where('branch_id', $branch->id)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->sum('amount');

        $commissionPct = (float) $branch->commission_pct;
        $payoutPaise = (int) round($grossPaise * $commissionPct / 100);

        return [
            'gross_revenue_paise' => $grossPaise,
            'commission_pct' => $commissionPct,
            'payout_amount_paise' => $payoutPaise,
        ];
    }

    /**
     * Generate payout rows for ALL eligible branches for a given month.
     *
     * Eligibility:
     *   - Active branch (is_active = true)
     *   - Not Head Office (is_head_office = false)
     *   - Has commission_pct > 0
     *   - Generated revenue > 0 in the period
     *
     * Idempotent: skips branches that already have a payout row for this period.
     *
     * @return Collection of created BranchPayout instances
     */
    public function generateForMonth(Carbon $monthStart, ?int $createdByUserId = null): Collection
    {
        $monthStart = $monthStart->copy()->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $eligibleBranches = Branch::active()
            ->where('is_head_office', false)
            ->where('commission_pct', '>', 0)
            ->get();

        $created = collect();

        foreach ($eligibleBranches as $branch) {
            // Idempotency check — skip if already generated
            $existing = BranchPayout::where('branch_id', $branch->id)
                ->whereDate('period_start', $monthStart->toDateString())
                ->first();
            if ($existing) {
                continue;
            }

            $calc = $this->calculateForBranch($branch, $monthStart, $monthEnd);

            // Skip zero-revenue branches (no need for ₹0 rows)
            if ($calc['gross_revenue_paise'] <= 0) {
                continue;
            }

            $payout = BranchPayout::create([
                'branch_id' => $branch->id,
                'period_start' => $monthStart->toDateString(),
                'period_end' => $monthEnd->toDateString(),
                'gross_revenue_paise' => $calc['gross_revenue_paise'],
                'commission_pct' => $calc['commission_pct'],
                'payout_amount_paise' => $calc['payout_amount_paise'],
                'status' => BranchPayout::STATUS_PENDING,
                'created_by_user_id' => $createdByUserId,
            ]);

            $created->push($payout);
        }

        return $created;
    }

    /**
     * Mark a payout as paid. Records the payment date, transaction reference, optional notes.
     */
    public function markAsPaid(BranchPayout $payout, Carbon $paidOn, ?string $reference = null, ?string $notes = null): BranchPayout
    {
        $payout->update([
            'status' => BranchPayout::STATUS_PAID,
            'paid_on' => $paidOn->toDateString(),
            'transaction_reference' => $reference,
            'notes' => $notes ? trim(($payout->notes ?? '') . "\n" . $notes) : $payout->notes,
        ]);

        return $payout;
    }

    /**
     * Cancel a payout (e.g., dispute resolved, recalculation needed).
     */
    public function cancel(BranchPayout $payout, ?string $reason = null): BranchPayout
    {
        $payout->update([
            'status' => BranchPayout::STATUS_CANCELLED,
            'notes' => $reason ? trim(($payout->notes ?? '') . "\n[Cancelled] " . $reason) : $payout->notes,
        ]);

        return $payout;
    }
}
