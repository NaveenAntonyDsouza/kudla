<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Admin audit phase 3 (Oct 2026), per site: where new members drop off
 * (funnel), whether they are still around 1 and 3 months after joining
 * (retention), and how members from each "How did you hear about us?"
 * answer get on (sources). Read straight from the tables; no personal data
 * leaves the method, only counts.
 */
class MemberFunnel
{
    /** Funnel steps, in order: [key => label]. */
    public const STEPS = [
        'joined' => 'Signed up',
        'finished' => 'Finished registration',
        'photo' => 'Has an approved photo',
        'interest' => 'Sent or received an interest',
        'connected' => 'Connected (an interest accepted, either way)',
        'paid' => 'Bought a paid plan',
    ];

    /**
     * Members who joined between $from and $to, at each step.
     *
     * @return list<array{key: string, label: string, count: int, of_joined: ?int, of_previous: ?int}>
     */
    public static function funnel(Carbon $from, Carbon $to): array
    {
        $members = self::cohort($from, $to);
        $joined = $members->count();
        $steps = [];
        $previous = null;
        foreach (self::STEPS as $key => $label) {
            $count = $key === 'joined' ? $joined : $members->where($key, true)->count();
            $steps[] = [
                'key' => $key,
                'label' => $label,
                'count' => $count,
                'of_joined' => $key === 'joined' ? null : self::pct($count, $joined),
                'of_previous' => $previous === null ? null : self::pct($count, $previous),
            ];
            $previous = $count;
        }

        return $steps;
    }

    /**
     * Each month's new members: how many finished, and how many used the site
     * again 1 and 3 months (30 / 90 days) after joining. "Used the site" =
     * their last activity (users.last_login_at, kept as "last active" since
     * 2 Oct 2026, or a login in login_history) is on or after that day, so
     * anyone who came back counts. Only members old enough are counted for
     * each column; a month nobody is old enough for shows null.
     *
     * @return list<array{month: string, label: string, joined: int, finished: ?int, after_1m: ?int, after_3m: ?int, eligible_1m: int, eligible_3m: int}>
     */
    public static function retention(int $months = 12, ?Carbon $now = null): array
    {
        $now ??= now();
        $start = $now->copy()->startOfMonth()->subMonths($months - 1);

        $rows = DB::table('profiles as p')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->whereNull('u.staff_role_id')
            ->whereNotNull('p.full_name')
            ->where('p.created_at', '>=', $start)
            ->selectRaw('p.created_at, p.onboarding_completed, u.last_login_at, (select max(lh.logged_in_at) from login_history lh where lh.user_id = p.user_id) as last_login_history')
            ->get();

        $byMonth = [];
        foreach ($rows as $r) {
            $joined = Carbon::parse($r->created_at);
            $lastSeen = collect([$r->last_login_at, $r->last_login_history])->filter()->map(fn ($t) => Carbon::parse($t))->max();
            $m = &$byMonth[$joined->format('Y-m')];
            $m ??= ['joined' => 0, 'finished' => 0, 'e1' => 0, 'a1' => 0, 'e3' => 0, 'a3' => 0];
            $m['joined']++;
            $m['finished'] += (int) (bool) $r->onboarding_completed;
            foreach ([1 => 30, 3 => 90] as $col => $days) {
                $mark = $joined->copy()->addDays($days);
                if ($mark->lte($now)) {
                    $m['e' . $col]++;
                    $m['a' . $col] += (int) ($lastSeen && $lastSeen->gte($mark));
                }
            }
            unset($m);
        }

        $out = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $m = $byMonth[$month->format('Y-m')] ?? ['joined' => 0, 'finished' => 0, 'e1' => 0, 'a1' => 0, 'e3' => 0, 'a3' => 0];
            $out[] = [
                'month' => $month->format('Y-m'),
                'label' => $month->format('M Y'),
                'joined' => $m['joined'],
                'finished' => self::pct($m['finished'], $m['joined']),
                'after_1m' => $m['e1'] > 0 ? self::pct($m['a1'], $m['e1']) : null,
                'after_3m' => $m['e3'] > 0 ? self::pct($m['a3'], $m['e3']) : null,
                'eligible_1m' => $m['e1'],
                'eligible_3m' => $m['e3'],
            ];
        }

        return array_reverse($out); // newest first
    }

    /**
     * Sign-ups between $from and $to by their "How did you hear about us?"
     * answer (members added by staff are their own line), and how they got on.
     *
     * @return list<array{source: string, joined: int, share: ?int, finished: ?int, photo: ?int, connected: ?int, paid: int}>
     */
    public static function sources(Carbon $from, Carbon $to): array
    {
        $members = self::cohort($from, $to);
        $total = $members->count();

        return $members
            ->groupBy(fn ($m) => $m['staff_added'] ? 'Added by staff' : ($m['source'] ?: 'Not answered'))
            ->map(fn (Collection $g, string $source) => [
                'source' => $source,
                'joined' => $g->count(),
                'share' => self::pct($g->count(), $total),
                'finished' => self::pct($g->where('finished', true)->count(), $g->count()),
                'photo' => self::pct($g->where('photo', true)->count(), $g->count()),
                'connected' => self::pct($g->where('connected', true)->count(), $g->count()),
                'paid' => $g->where('paid', true)->count(),
            ])
            ->sortByDesc('joined')
            ->values()
            ->all();
    }

    /** Sign-ups between $from and $to by device (phone, computer, tablet). */
    public static function devices(Carbon $from, Carbon $to): array
    {
        $members = self::cohort($from, $to);

        return $members->groupBy(fn ($m) => $m['device'] ?: 'Not recorded')
            ->map(fn (Collection $g, string $device) => ['device' => $device, 'joined' => $g->count(), 'share' => self::pct($g->count(), $members->count())])
            ->sortByDesc('joined')->values()->all();
    }

    /**
     * One entry per member who joined between $from and $to (deleted ones
     * included: they did sign up), with a flag for each funnel step.
     */
    private static function cohort(Carbon $from, Carbon $to): Collection
    {
        $hasStaffColumn = \Illuminate\Support\Facades\Schema::hasColumn('profiles', 'created_by_staff_id');

        return DB::table('profiles as p')
            ->whereNotNull('p.full_name')
            ->where('p.created_at', '>=', $from)
            ->where('p.created_at', '<', $to)
            ->selectRaw(implode(', ', [
                'p.id',
                'p.onboarding_completed',
                'p.how_did_you_hear_about_us as source',
                'p.registration_source as device',
                $hasStaffColumn ? 'p.created_by_staff_id' : 'null as created_by_staff_id',
                "exists(select 1 from profile_photos ph where ph.profile_id = p.id and ph.approval_status = 'approved') as photo",
                'exists(select 1 from interests i where i.sender_profile_id = p.id or i.receiver_profile_id = p.id) as interest',
                "exists(select 1 from interests i where (i.sender_profile_id = p.id or i.receiver_profile_id = p.id) and i.status = 'accepted') as connected",
                "exists(select 1 from subscriptions s where s.user_id = p.user_id and s.payment_status = 'paid') as paid",
            ]))
            ->get()
            ->map(fn ($r) => [
                'finished' => (bool) $r->onboarding_completed,
                'photo' => (bool) $r->photo,
                'interest' => (bool) $r->interest,
                'connected' => (bool) $r->connected,
                'paid' => (bool) $r->paid,
                'source' => trim((string) $r->source),
                'device' => trim((string) $r->device),
                'staff_added' => $r->created_by_staff_id !== null,
            ]);
    }

    private static function pct(int $part, int $whole): ?int
    {
        return $whole > 0 ? (int) round($part / $whole * 100) : null;
    }
}
