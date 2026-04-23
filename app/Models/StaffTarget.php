<?php

namespace App\Models;

use App\Models\Concerns\BranchScopable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffTarget extends Model
{
    use BranchScopable;

    protected $fillable = [
        'staff_user_id',
        'branch_id',
        'month',
        'registration_target',
        'revenue_target',
        'call_target',
        'incentive_per_registration',
        'incentive_per_subscription_pct',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'date',
            'registration_target' => 'integer',
            'revenue_target' => 'integer',
            'call_target' => 'integer',
            'incentive_per_registration' => 'integer',
            'incentive_per_subscription_pct' => 'decimal:2',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Find target for a specific user and month.
     * Defaults to current month if $month is null.
     */
    public static function findForUser(int $userId, ?Carbon $month = null): ?self
    {
        $month = $month ?? static::getCurrentMonthFirstDay();

        return static::where('staff_user_id', $userId)
            ->whereDate('month', $month->toDateString())
            ->first();
    }

    /**
     * Get the first day of the current month as a Carbon instance.
     */
    public static function getCurrentMonthFirstDay(): Carbon
    {
        return now()->startOfMonth();
    }

    /**
     * Compute actual performance metrics for this target's period.
     * Returns array with registrations, revenue_paise, calls, and incentive breakdown.
     */
    public function computeActuals(): array
    {
        $monthStart = $this->month->copy()->startOfMonth();
        $monthEnd = $this->month->copy()->endOfMonth();

        // Registrations (leads converted by this staff in the target month)
        $registrations = Lead::where('converted_by_staff_id', $this->staff_user_id)
            ->whereBetween('converted_at', [$monthStart, $monthEnd])
            ->count();

        // Revenue from subscriptions by members created by this staff, paid in the target month
        $revenuePaise = (int) Subscription::where('payment_status', 'paid')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->whereHas('user.profile', function ($q) {
                $q->where('created_by_staff_id', $this->staff_user_id);
            })
            ->sum('amount');

        // Calls made by this staff in the target month
        $calls = CallLog::where('called_by_staff_id', $this->staff_user_id)
            ->whereBetween('called_at', [$monthStart, $monthEnd])
            ->count();

        // Incentive breakdown
        $incentiveFromRegistrations = $registrations * $this->incentive_per_registration;
        $incentiveFromRevenue = (int) round($revenuePaise * $this->incentive_per_subscription_pct / 100);
        $incentiveEarned = $incentiveFromRegistrations + $incentiveFromRevenue;

        return [
            'registrations' => $registrations,
            'revenue_paise' => $revenuePaise,
            'calls' => $calls,
            'incentive_from_registrations_paise' => $incentiveFromRegistrations,
            'incentive_from_revenue_paise' => $incentiveFromRevenue,
            'incentive_earned_paise' => $incentiveEarned,
        ];
    }

    /**
     * Get progress percentage (0-100, capped) for a given metric.
     * Metric: 'registrations' | 'revenue' | 'calls'
     */
    public function getProgressPercent(string $metric): float
    {
        $actuals = $this->computeActuals();

        [$actual, $target] = match ($metric) {
            'registrations' => [$actuals['registrations'], $this->registration_target],
            'revenue' => [$actuals['revenue_paise'], $this->revenue_target],
            'calls' => [$actuals['calls'], $this->call_target],
            default => [0, 0],
        };

        if ($target <= 0) {
            return 0.0;
        }

        return min(100.0, round(($actual / $target) * 100, 1));
    }
}
