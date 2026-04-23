<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\Interest;
use App\Models\Lead;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    public static function exportUsers(): StreamedResponse
    {
        $profiles = Profile::with(['user', 'religiousInfo', 'educationDetail', 'locationInfo'])
            ->whereNotNull('full_name')
            ->orderBy('created_at', 'desc')
            ->get();

        return self::streamCsv('users_export.csv', [
            'Matri ID', 'Full Name', 'Gender', 'DOB', 'Age', 'Religion', 'Denomination/Caste',
            'Education', 'Occupation', 'State', 'Country', 'Email', 'Phone',
            'Profile Completion %', 'Approved', 'Active', 'Created By', 'Registered At',
        ], $profiles->map(fn (Profile $p) => [
            $p->matri_id,
            $p->full_name,
            $p->gender,
            $p->date_of_birth?->format('d/m/Y'),
            $p->date_of_birth ? $p->date_of_birth->age : '',
            $p->religiousInfo?->religion,
            $p->religiousInfo?->denomination ?: $p->religiousInfo?->caste,
            $p->educationDetail?->highest_education,
            $p->educationDetail?->occupation,
            $p->locationInfo?->native_state,
            $p->locationInfo?->native_country,
            $p->user?->email,
            $p->user?->phone,
            $p->profile_completion_pct,
            $p->is_approved ? 'Yes' : 'No',
            $p->is_active ? 'Yes' : 'No',
            $p->created_by,
            $p->created_at?->format('d/m/Y'),
        ])->toArray());
    }

    public static function exportInterests(): StreamedResponse
    {
        $interests = Interest::with(['senderProfile', 'receiverProfile'])
            ->orderBy('created_at', 'desc')
            ->get();

        return self::streamCsv('interests_export.csv', [
            'ID', 'Sender Matri ID', 'Sender Name', 'Receiver Matri ID', 'Receiver Name',
            'Status', 'Message', 'Sent At',
        ], $interests->map(fn (Interest $i) => [
            $i->id,
            $i->senderProfile?->matri_id,
            $i->senderProfile?->full_name,
            $i->receiverProfile?->matri_id,
            $i->receiverProfile?->full_name,
            $i->status,
            $i->custom_message ?: '(template)',
            $i->created_at?->format('d/m/Y H:i'),
        ])->toArray());
    }

    public static function exportPayments(): StreamedResponse
    {
        $subscriptions = Subscription::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return self::streamCsv('payments_export.csv', [
            'ID', 'User', 'Email', 'Plan', 'Amount (INR)', 'Status',
            'Razorpay Payment ID', 'Starts', 'Expires', 'Payment Date',
        ], $subscriptions->map(fn (Subscription $s) => [
            $s->id,
            $s->user?->name,
            $s->user?->email,
            $s->plan_name,
            number_format($s->amount / 100, 2),
            $s->payment_status,
            $s->razorpay_payment_id,
            $s->starts_at?->format('d/m/Y'),
            $s->expires_at?->format('d/m/Y'),
            $s->created_at?->format('d/m/Y H:i'),
        ])->toArray());
    }

    /**
     * Export staff performance data for the given date range.
     *
     * @param string $dateRange  One of: today, this_week, this_month, last_30_days, all
     */
    public static function exportStaffPerformance(string $dateRange = 'this_month'): StreamedResponse
    {
        [$from, $to] = self::resolveDateRange($dateRange);

        $staff = User::whereNotNull('staff_role_id')
            ->with('staffRole')
            ->orderBy('name')
            ->get();

        $rows = $staff->map(function (User $user) use ($from, $to) {
            return self::computeStaffMetrics($user, $from, $to);
        })->toArray();

        return self::streamCsv('staff_performance_' . $dateRange . '.csv', [
            'Staff Name', 'Role', 'Email',
            'Leads Assigned', 'Leads Converted', 'Conversion Rate %',
            'Calls Made', 'Calls Connected', 'Total Call Duration (min)', 'Avg Call Duration (min)',
        ], array_map(fn ($r) => [
            $r['name'],
            $r['role'],
            $r['email'],
            $r['leads_assigned'],
            $r['leads_converted'],
            $r['conversion_rate'],
            $r['calls_made'],
            $r['calls_connected'],
            $r['total_call_duration'],
            $r['avg_call_duration'],
        ], $rows));
    }

    /**
     * Compute performance metrics for a single staff user within a date range.
     * Public so StaffPerformanceReport page can reuse it.
     */
    public static function computeStaffMetrics(User $user, ?Carbon $from, ?Carbon $to): array
    {
        $leadsQuery = Lead::where('assigned_to_staff_id', $user->id);
        $convertedQuery = Lead::where('converted_by_staff_id', $user->id);
        $callsQuery = CallLog::where('called_by_staff_id', $user->id);

        if ($from) {
            $convertedQuery->where('converted_at', '>=', $from);
            $callsQuery->where('called_at', '>=', $from);
        }
        if ($to) {
            $convertedQuery->where('converted_at', '<=', $to);
            $callsQuery->where('called_at', '<=', $to);
        }

        $leadsAssigned = $leadsQuery->count();
        $leadsConverted = $convertedQuery->count();
        $conversionRate = $leadsAssigned > 0
            ? round(($leadsConverted / $leadsAssigned) * 100, 1)
            : 0;

        $callsMade = $callsQuery->count();
        $callsConnected = (clone $callsQuery)->where('outcome', 'connected')->count();
        $totalDuration = (clone $callsQuery)->sum('duration_minutes') ?? 0;
        $avgDuration = $callsMade > 0
            ? round($totalDuration / $callsMade, 1)
            : 0;

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'role' => $user->staffRole?->name ?? '—',
            'email' => $user->email,
            'leads_assigned' => $leadsAssigned,
            'leads_converted' => $leadsConverted,
            'conversion_rate' => $conversionRate,
            'calls_made' => $callsMade,
            'calls_connected' => $callsConnected,
            'total_call_duration' => (int) $totalDuration,
            'avg_call_duration' => $avgDuration,
        ];
    }

    /**
     * Resolve a date range string to [Carbon $from, Carbon $to] boundaries.
     * Returns [null, null] for 'all'.
     */
    public static function resolveDateRange(string $range): array
    {
        return match ($range) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_30_days' => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
            'all' => [null, null],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    public static function dateRangeOptions(): array
    {
        return [
            'today' => 'Today',
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'last_30_days' => 'Last 30 Days',
            'all' => 'All Time',
        ];
    }

    private static function streamCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8 compatibility
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
