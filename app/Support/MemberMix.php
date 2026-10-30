<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Insights (Oct 2026): who the members are and how men and women use the
 * site — counts only. "Live" = a member's profile that is switched on and not
 * deleted; "active" = live and used the site in the last 30 days
 * (users.last_login_at, kept as "last active" since 2 Oct 2026).
 */
class MemberMix
{
    public const AGE_BANDS = ['18–24', '25–29', '30–34', '35–39', '40+'];

    /** @return array{live: array{male: int, female: int}, active: array{male: int, female: int}} */
    public static function genderBalance(): array
    {
        $count = fn (bool $active) => self::live($active)->selectRaw('p.gender, count(*) as n')->groupBy('p.gender')->pluck('n', 'gender');
        $live = $count(false);
        $active = $count(true);

        return [
            'live' => ['male' => (int) ($live['male'] ?? 0), 'female' => (int) ($live['female'] ?? 0)],
            'active' => ['male' => (int) ($active['male'] ?? 0), 'female' => (int) ($active['female'] ?? 0)],
        ];
    }

    /** Members per age band, men and women apart: [band => [male, female]]. */
    public static function ageGroups(bool $activeOnly = false): array
    {
        $out = array_fill_keys(self::AGE_BANDS, ['male' => 0, 'female' => 0]);
        $rows = self::live($activeOnly)->whereNotNull('p.date_of_birth')->get(['p.gender', 'p.date_of_birth']);
        foreach ($rows as $r) {
            if (! isset($out[self::AGE_BANDS[0]][$r->gender])) {
                continue;
            }
            $age = Carbon::parse($r->date_of_birth)->age;
            $band = match (true) {
                $age < 25 => '18–24',
                $age < 30 => '25–29',
                $age < 35 => '30–34',
                $age < 40 => '35–39',
                default => '40+',
            };
            $out[$band][$r->gender]++;
        }

        return $out;
    }

    /** Live members by the country they live in, largest first: [country => n]; the rest as "Other countries". */
    public static function countries(int $top = 8): array
    {
        return self::topWithOther(
            self::live()->leftJoin('location_info as l', 'l.profile_id', '=', 'p.id')
                ->selectRaw("coalesce(nullif(l.residing_country, ''), 'Not given') as k, count(*) as n")
                ->groupBy('k')->pluck('n', 'k')->all(),
            $top,
            'Other countries'
        );
    }

    /** Live members by home (native) district, largest first: [district => n]. */
    public static function homeDistricts(int $top = 10): array
    {
        return self::topWithOther(
            self::live()->leftJoin('location_info as l', 'l.profile_id', '=', 'p.id')
                ->selectRaw("coalesce(nullif(l.native_district, ''), 'Not given') as k, count(*) as n")
                ->groupBy('k')->pluck('n', 'k')->all(),
            $top,
            'Other districts'
        );
    }

    /**
     * Interests by who sent them (last $days days, withdrawn ones left out), and
     * how many each active member received in the last 30 days.
     *
     * @return array{male: array, female: array}
     */
    public static function interestsByGender(int $days = 90): array
    {
        $out = [];
        $active = self::genderBalance()['active'];
        foreach (['male', 'female'] as $g) {
            $sent = DB::table('interests as i')->join('profiles as s', 's.id', '=', 'i.sender_profile_id')
                ->where('s.gender', $g)->where('i.status', '!=', 'cancelled')->where('i.created_at', '>=', now()->subDays($days));
            $n = (clone $sent)->count();
            $answered = (clone $sent)->whereIn('i.status', ['accepted', 'declined'])->count();
            $accepted = (clone $sent)->where('i.status', 'accepted')->count();
            $received30 = DB::table('interests as i')->join('profiles as r', 'r.id', '=', 'i.receiver_profile_id')
                ->where('r.gender', $g)->where('i.status', '!=', 'cancelled')->where('i.created_at', '>=', now()->subDays(30))->count();
            $out[$g] = [
                'sent' => $n,
                'answered_pct' => $n > 0 ? (int) round($answered / $n * 100) : null,
                'accepted_pct' => $answered > 0 ? (int) round($accepted / $answered * 100) : null,
                'received_30d' => $received30,
                'received_per_active' => $active[$g] > 0 ? round($received30 / $active[$g], 1) : null,
            ];
        }

        return $out;
    }

    /**
     * The last $weeks weeks (7-day blocks ending now, the same "week" as the
     * Scorecard): sign-ups, interests sent and connections in each.
     *
     * @return array{labels: list<string>, signups: list<int>, interests: list<int>, connections: list<int>}
     */
    public static function weeklyTrend(int $weeks = 12, ?Carbon $now = null): array
    {
        $now ??= now();
        $out = ['labels' => [], 'signups' => [], 'interests' => [], 'connections' => []];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $to = $now->copy()->subDays(7 * $i);
            $from = $to->copy()->subDays(7);
            $out['labels'][] = $from->copy()->addDay()->format('j M');
            $out['signups'][] = DB::table('profiles')->whereNotNull('full_name')->where('created_at', '>=', $from)->where('created_at', '<', $to)->count();
            $out['interests'][] = DB::table('interests')->where('status', '!=', 'cancelled')->where('created_at', '>=', $from)->where('created_at', '<', $to)->count();
            $out['connections'][] = DB::table('interest_replies')->where('reply_type', 'accept')->where('created_at', '>=', $from)->where('created_at', '<', $to)->count();
        }

        return $out;
    }

    /**
     * Sign-ups per month by "How did you hear about us?" answer, the last
     * $months months: the $top biggest sources by name, the rest as "Other
     * answers". Unanswered and staff-added members are left out (the table
     * above the chart shows them).
     *
     * @return array{labels: list<string>, series: array<string, list<int>>}
     */
    public static function sourcesByMonth(int $months = 6, int $top = 6, ?Carbon $now = null): array
    {
        $now ??= now();
        $start = $now->copy()->startOfMonth()->subMonths($months - 1);
        $hasStaff = \Illuminate\Support\Facades\Schema::hasColumn('profiles', 'created_by_staff_id');
        $rows = DB::table('profiles')
            ->whereNotNull('full_name')
            ->where('created_at', '>=', $start)
            ->whereNotNull('how_did_you_hear_about_us')->where('how_did_you_hear_about_us', '!=', '')
            ->when($hasStaff, fn ($q) => $q->whereNull('created_by_staff_id'))
            ->get(['how_did_you_hear_about_us as source', 'created_at']);

        $keys = [];
        for ($i = 0; $i < $months; $i++) {
            $keys[] = $start->copy()->addMonths($i)->format('Y-m');
        }
        $ranked = $rows->groupBy(fn ($r) => trim($r->source))->map->count()->sortDesc();
        $named = $ranked->keys()->take($top)->all();

        $series = [];
        foreach ($rows as $r) {
            $name = in_array(trim($r->source), $named, true) ? trim($r->source) : 'Other answers';
            $month = Carbon::parse($r->created_at)->format('Y-m');
            $series[$name] ??= array_fill_keys($keys, 0);
            if (isset($series[$name][$month])) {
                $series[$name][$month]++;
            }
        }
        // keep the order: biggest source first, "Other answers" last
        $ordered = [];
        foreach (array_merge($named, ['Other answers']) as $name) {
            if (isset($series[$name])) {
                $ordered[$name] = array_values($series[$name]);
            }
        }

        return [
            'labels' => array_map(fn ($k) => Carbon::createFromFormat('Y-m-d', $k . '-01')->format('M Y'), $keys),
            'series' => $ordered,
        ];
    }

    /** Live member profiles (optionally only those active in the last 30 days), as a query on `profiles as p`. */
    private static function live(bool $activeOnly = false): \Illuminate\Database\Query\Builder
    {
        return DB::table('profiles as p')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->whereNull('u.staff_role_id')
            ->whereNull('p.deleted_at')
            ->where('p.is_active', true)
            ->whereNotNull('p.full_name')
            ->when($activeOnly, fn ($q) => $q->where('u.last_login_at', '>=', now()->subDays(30)));
    }

    private static function topWithOther(array $counts, int $top, string $otherLabel): array
    {
        arsort($counts);
        $notGiven = $counts['Not given'] ?? null;
        unset($counts['Not given']);
        $out = array_slice($counts, 0, $top, true);
        $rest = array_sum(array_slice($counts, $top, null, true));
        if ($rest > 0) {
            $out[$otherLabel] = $rest;
        }
        if ($notGiven) {
            $out['Not given'] = $notGiven;
        }

        return $out;
    }
}
