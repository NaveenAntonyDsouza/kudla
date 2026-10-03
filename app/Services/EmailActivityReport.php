<?php

namespace App\Services;

use App\Http\Controllers\UnsubscribeController;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\SiteSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Numbers for admin → Reports → Email Activity. */
class EmailActivityReport
{
    /** Readable names for emails that aren't admin templates. */
    private const OTHER_TYPES = [
        'verification-code' => 'Verification / login codes',
        'password' => 'Password emails',
        'test' => 'Test emails',
        'other' => 'Other',
    ];

    public function build(int $days): array
    {
        $since = now()->subDays($days - 1)->startOfDay();
        $names = $this->typeNames();

        $byType = EmailLog::query()
            ->where('created_at', '>=', $since)
            ->whereIn('event', ['sent', 'failed'])
            ->select('email_type', 'event', DB::raw('count(*) as total'))
            ->groupBy('email_type', 'event')
            ->get()
            ->groupBy('email_type')
            ->map(fn ($rows, $type) => [
                'type' => $names[$type] ?? $type,
                'sent' => (int) $rows->firstWhere('event', 'sent')?->total,
                'failed' => (int) $rows->firstWhere('event', 'failed')?->total,
            ])
            ->sortByDesc('sent')
            ->values()
            ->all();

        $daily = collect(range($days - 1, 0))->map(function ($ago) {
            $day = now()->subDays($ago)->toDateString();

            return [
                'date' => Carbon::parse($day)->format('D j M'),
                'sent' => EmailLog::where('event', 'sent')->whereDate('created_at', $day)->count(),
                'failed' => EmailLog::where('event', 'failed')->whereDate('created_at', $day)->count(),
            ];
        })->reverse()->values()->all();

        $unsubscribes = EmailLog::where('event', 'unsubscribed')->where('created_at', '>=', $since)
            ->select('email_type', DB::raw('count(*) as total'))
            ->groupBy('email_type')
            ->pluck('total', 'email_type')
            ->mapWithKeys(fn ($total, $pref) => [UnsubscribeController::PREFERENCES[$pref] ?? $pref => (int) $total])
            ->all();

        $quota = max(1, (int) SiteSetting::getValue('mail_daily_quota', '100'));
        $last24h = EmailLog::where('event', 'sent')->where('created_at', '>=', now()->subDay())->count();

        return [
            'totals' => [
                'sent' => array_sum(array_column($byType, 'sent')),
                'failed' => array_sum(array_column($byType, 'failed')),
                'unsubscribed' => array_sum($unsubscribes),
            ],
            'last24h' => $last24h,
            'quota' => $quota,
            'quotaPercent' => (int) round($last24h / $quota * 100),
            'byType' => $byType,
            'daily' => $daily,
            'unsubscribes' => $unsubscribes,
            'failures' => EmailLog::where('event', 'failed')->latest('created_at')->limit(20)->get()
                ->map(fn (EmailLog $log) => [
                    'when' => $log->created_at?->format('j M, g:i A'),
                    'type' => $names[$log->email_type] ?? $log->email_type,
                    'recipient' => $log->recipient,
                    'error' => $log->error,
                ])->all(),
        ];
    }

    /** slug => admin template name, plus names for the non-template emails. */
    private function typeNames(): array
    {
        try {
            $templates = EmailTemplate::pluck('name', 'slug')->all();
        } catch (\Throwable) {
            $templates = [];
        }

        return $templates + self::OTHER_TYPES;
    }
}
