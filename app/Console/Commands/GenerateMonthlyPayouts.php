<?php

namespace App\Console\Commands;

use App\Services\BranchPayoutService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('branches:generate-monthly-payouts {--month= : YYYY-MM (defaults to PREVIOUS month)}')]
#[Description('Generate branch commission payouts for a given month. Idempotent — skips branches that already have a row for the month.')]
class GenerateMonthlyPayouts extends Command
{
    public function handle(BranchPayoutService $service): int
    {
        $monthInput = $this->option('month');

        try {
            $month = $monthInput
                ? Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth()
                : now()->subMonth()->startOfMonth();
        } catch (\Throwable $e) {
            $this->error("Invalid --month value '{$monthInput}'. Use format YYYY-MM, e.g., 2026-04.");
            return self::FAILURE;
        }

        $this->info("Generating payouts for: " . $month->format('F Y'));
        $this->line("Period: {$month->toDateString()} to {$month->copy()->endOfMonth()->toDateString()}");
        $this->line('');

        $created = $service->generateForMonth($month);

        if ($created->isEmpty()) {
            $this->warn('No new payouts created. (Either branches already have payouts for this month, or no eligible branches with revenue.)');
            return self::SUCCESS;
        }

        $this->info("Created {$created->count()} payout(s):");
        $this->table(
            ['Branch', 'Code', 'Gross Revenue', 'Commission %', 'Payout Amount'],
            $created->map(fn ($p) => [
                $p->branch?->name ?? 'Unknown',
                $p->branch?->code ?? '?',
                '₹' . number_format($p->gross_revenue_paise / 100, 2),
                $p->commission_pct . '%',
                '₹' . number_format($p->payout_amount_paise / 100, 2),
            ])->toArray()
        );

        $totalPayout = $created->sum('payout_amount_paise') / 100;
        $this->line('');
        $this->info("Total payouts to disburse: ₹" . number_format($totalPayout, 2));

        return self::SUCCESS;
    }
}
