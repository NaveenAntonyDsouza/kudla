<?php

namespace App\Support;

use App\Models\AdminActivityLog;
use App\Models\LoginAsSession;
use App\Models\LoginHistory;
use App\Models\ProfileNote;
use App\Models\ProfilePhoto;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Staff Activity (12 Nov 2026, owner: "can admin see how many calls staff
 * made, how many notes per day?"). Counts, per staff account, the work the
 * admin already records:
 *  - Notes popup (ProfileNote, by note_type): calls, WhatsApp, other contacts,
 *    and the different members contacted. The "Plan set to …" note the Change
 *    Plan page writes by itself is a plan change, not a contact;
 *  - follow-ups set, and done (on time or late) from the Follow-up Report;
 *  - Activity Log: details checked on a call, member edits, approvals;
 *  - plans assigned (manual Subscription), photos approved; from 12 Nov 2026
 *    also photos rejected and ID proofs / horoscopes checked (StaffActionLog);
 *  - "Login as member" visits and admin sign-ins.
 * Nothing new is recorded; it only reads. Dates are the display time zone's.
 */
final class StaffActivity
{
    /** Note text the Change Plan page writes by itself (ChangeMembershipPlan). */
    public const PLAN_NOTE_PREFIX = 'Plan set to ';

    /** Note types that are a contact with the member (everything but a plain "general" note). */
    public const CONTACT_TYPES = ['call', 'whatsapp', 'email', 'meeting', 'walk_in'];

    public const RANGES = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'this_week' => 'This week',
        'last_7' => 'Last 7 days',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'custom' => 'Pick dates',
    ];

    public static function tz(): string
    {
        return (string) config('app.display_timezone', 'Asia/Kolkata');
    }

    /**
     * Start and end of a range, as display-time-zone days.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function range(string $range, ?string $from = null, ?string $to = null): array
    {
        $today = CarbonImmutable::now(self::tz())->startOfDay();

        [$start, $end] = match ($range) {
            'yesterday' => [$today->subDay(), $today->subDay()],
            'this_week' => [$today->startOfWeek(), $today],
            'last_7' => [$today->subDays(6), $today],
            'this_month' => [$today->startOfMonth(), $today],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()->startOfDay()],
            'custom' => [self::day($from) ?? $today, self::day($to) ?? $today],
            default => [$today, $today],
        };
        if ($end->lessThan($start)) {
            [$start, $end] = [$end, $start];
        }
        // never more than a year at once
        if ($start->diffInDays($end) > 366) {
            $start = $end->subDays(366);
        }

        return [$start->startOfDay(), $end->endOfDay()];
    }

    private static function day(?string $value): ?CarbonImmutable
    {
        try {
            return filled($value) ? CarbonImmutable::parse($value, self::tz())->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** The accounts that count as staff, in name order. */
    public static function staff(): Collection
    {
        return User::query()
            ->where(fn ($q) => $q->whereNotNull('staff_role_id')->orWhere('role', 'admin'))
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
    }

    /**
     * One row per staff account (or only $onlyStaffId), busiest first.
     *
     * @return list<array<string, mixed>>
     */
    public static function summary(CarbonImmutable $from, CarbonImmutable $to, ?int $onlyStaffId = null): array
    {
        $staff = self::staff()->when($onlyStaffId, fn ($c) => $c->where('id', $onlyStaffId));
        $events = self::events($from, $to, $staff->pluck('id')->all());

        $rows = $staff->map(function (User $u) use ($events) {
            $mine = $events->where('staff', $u->id);
            $row = ['staff_id' => $u->id, 'name' => $u->name, 'is_active' => (bool) $u->is_active] + self::count($mine);
            $row['days_active'] = $mine->where('work', true)->pluck('day')->unique()->count();

            return $row;
        });

        // people who did something first; quiet accounts after them, by name
        return $rows->sortBy([['total', 'desc'], ['name', 'asc']])->values()->all();
    }

    /**
     * Day by day for one staff account (newest day first; days with nothing left out).
     *
     * @return list<array<string, mixed>>
     */
    public static function daily(int $staffId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return self::events($from, $to, [$staffId])
            ->groupBy('day')
            ->map(function (Collection $day, string $date) {
                $work = $day->where('work', true);

                return ['day' => $date] + self::count($day) + [
                    'first' => $work->min('at')?->format('g:i A'),
                    'last' => $work->max('at')?->format('g:i A'),
                ];
            })
            ->sortKeysDesc()
            ->values()
            ->all();
    }

    /** The notes themselves, newest first (for "what did they write"). */
    public static function notes(int $staffId, CarbonImmutable $from, CarbonImmutable $to, int $limit = 100): Collection
    {
        [$a, $b] = self::stored($from, $to);

        return ProfileNote::query()
            ->with(['profile' => fn ($q) => $q->withTrashed()->select('id', 'matri_id', 'full_name')])
            ->where('admin_user_id', $staffId)
            ->whereBetween('created_at', [$a, $b])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /** @param Collection<int, array> $events */
    private static function count(Collection $events): array
    {
        $by = fn (string $kind) => $events->where('kind', $kind);
        $contacts = $events->whereIn('kind', ['call', 'whatsapp', 'other_contact']);

        $row = [
            'calls' => $by('call')->count(),
            'whatsapp' => $by('whatsapp')->count(),
            'other_contacts' => $by('other_contact')->count(),
            'general_notes' => $by('general_note')->count(),
            'members_contacted' => $contacts->pluck('member')->filter()->unique()->count(),
            'followups_set' => $by('followup_set')->count(),
            'followups_done' => $by('followup_done')->count(),
            'followups_late' => $by('followup_done')->where('late', true)->count(),
            'details_checked' => $by('details_checked')->count(),
            'member_edits' => $by('member_edit')->count(),
            'approvals' => $by('approval')->count(),
            'plans_assigned' => $by('plan')->count(),
            'photos_approved' => $by('photo')->count(),
            'photos_rejected' => $by('photo_rejected')->count(),
            'id_checks' => $by('id_check')->count(),
            'assisted' => $by('assisted')->count(),
            'login_as' => $by('login_as')->count(),
            'login_as_minutes' => (int) $by('login_as')->sum('minutes'),
            'sign_ins' => $by('sign_in')->count(),
        ];
        $row['total'] = $events->where('work', true)->count();

        return $row;
    }

    /** Range in the time zone the database rows are written in. */
    private static function stored(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $tz = (string) config('app.timezone', 'UTC');

        return [$from->setTimezone($tz)->toDateTimeString(), $to->setTimezone($tz)->toDateTimeString()];
    }

    /**
     * Every counted thing as one event: staff, kind, time, display day, member.
     * "work" marks what counts as doing something (sign-ins don't).
     *
     * @param  list<int>  $staffIds
     * @return Collection<int, array>
     */
    private static function events(CarbonImmutable $from, CarbonImmutable $to, array $staffIds): Collection
    {
        if ($staffIds === []) {
            return collect();
        }
        [$a, $b] = self::stored($from, $to);
        $tz = self::tz();
        $event = function (int $staff, string $kind, $at, ?int $member = null, array $extra = []) use ($tz): array {
            $at = CarbonImmutable::parse($at, (string) config('app.timezone', 'UTC'))->setTimezone($tz);

            return ['staff' => $staff, 'kind' => $kind, 'at' => $at, 'day' => $at->toDateString(), 'member' => $member, 'work' => $kind !== 'sign_in'] + $extra;
        };
        $events = collect();

        // Notes written (contacts, plain notes, follow-ups set)
        ProfileNote::query()
            ->whereIn('admin_user_id', $staffIds)
            ->whereBetween('created_at', [$a, $b])
            ->get(['admin_user_id', 'profile_id', 'note_type', 'note', 'follow_up_date', 'created_at'])
            ->each(function (ProfileNote $n) use ($events, $event) {
                if (str_starts_with((string) $n->note, self::PLAN_NOTE_PREFIX) && ($n->note_type ?? 'general') === 'general') {
                    return; // the Change Plan page's own note: counted as a plan below
                }
                $type = $n->note_type ?: 'general';
                $kind = match (true) {
                    $type === 'call' => 'call',
                    $type === 'whatsapp' => 'whatsapp',
                    in_array($type, self::CONTACT_TYPES, true) => 'other_contact',
                    default => 'general_note',
                };
                $events->push($event($n->admin_user_id, $kind, $n->getRawOriginal('created_at'), $n->profile_id));
                if ($n->follow_up_date) {
                    $events->push($event($n->admin_user_id, 'followup_set', $n->getRawOriginal('created_at'), $n->profile_id));
                }
            });

        // Follow-ups marked done (by whoever did it, on the day they did it)
        ProfileNote::query()
            ->whereIn('follow_up_completed_by', $staffIds)
            ->whereBetween('follow_up_completed_at', [$a, $b])
            ->get(['follow_up_completed_by', 'profile_id', 'follow_up_date', 'follow_up_completed_at'])
            ->each(function (ProfileNote $n) use ($events, $event, $tz) {
                $done = CarbonImmutable::parse($n->getRawOriginal('follow_up_completed_at'), (string) config('app.timezone', 'UTC'))->setTimezone($tz);
                $late = $n->follow_up_date && $done->toDateString() > $n->follow_up_date->toDateString();
                $events->push($event((int) $n->follow_up_completed_by, 'followup_done', $n->getRawOriginal('follow_up_completed_at'), $n->profile_id, ['late' => $late]));
            });

        // Activity Log: details checked on a call, member edits, approvals
        $actions = [
            'member_details_checked' => 'details_checked',
            'member_profile_updated' => 'member_edit',
            'profile_approved' => 'approval',
            // logged from 12 Nov 2026 (App\Support\StaffActionLog)
            'photo_rejected' => 'photo_rejected',
            'id_proof_approved' => 'id_check',
            'id_proof_rejected' => 'id_check',
            'document_approved' => 'id_check',
            'document_rejected' => 'id_check',
            // assisted service, from 12 Nov 2026 (App\Support\Assisted)
            'assisted_suggested' => 'assisted',
            'assisted_interest_sent' => 'assisted',
            'assisted_accepted' => 'assisted',
            'assisted_declined' => 'assisted',
            'assisted_meeting' => 'assisted',
        ];
        AdminActivityLog::query()
            ->whereIn('admin_user_id', $staffIds)
            ->whereIn('action', array_keys($actions))
            ->whereBetween('created_at', [$a, $b])
            ->get(['admin_user_id', 'action', 'model_type', 'model_id', 'created_at'])
            ->each(fn (AdminActivityLog $l) => $events->push($event(
                (int) $l->admin_user_id, $actions[$l->action], $l->getRawOriginal('created_at'), $l->model_type === 'Profile' ? $l->model_id : null,
            )));

        // Plans assigned by hand (Change Plan page)
        Subscription::query()
            ->withoutGlobalScopes()
            ->whereBetween('created_at', [$a, $b])
            ->whereNotNull('gateway_metadata')
            ->get(['gateway_metadata', 'created_at'])
            ->each(function (Subscription $s) use ($events, $event, $staffIds) {
                $by = (int) ($s->gateway_metadata['assigned_by_admin_id'] ?? 0);
                if ($by && in_array($by, $staffIds, true)) {
                    $events->push($event($by, 'plan', $s->getRawOriginal('created_at')));
                }
            });

        // Photos approved
        ProfilePhoto::query()
            ->whereIn('approved_by', $staffIds)
            ->whereBetween('approved_at', [$a, $b])
            ->get(['approved_by', 'profile_id', 'approved_at'])
            ->each(fn (ProfilePhoto $p) => $events->push($event((int) $p->approved_by, 'photo', $p->getRawOriginal('approved_at'), $p->profile_id)));

        // "Login as member" visits
        LoginAsSession::query()
            ->whereIn('staff_user_id', $staffIds)
            ->whereBetween('started_at', [$a, $b])
            ->get()
            ->each(fn (LoginAsSession $v) => $events->push($event($v->staff_user_id, 'login_as', $v->getRawOriginal('started_at'), null, ['minutes' => $v->minutes])));

        // Admin sign-ins (not counted as work)
        LoginHistory::query()
            ->whereIn('user_id', $staffIds)
            ->where('login_method', AdminLoginTrail::SIGNED_IN)
            ->whereBetween('logged_in_at', [$a, $b])
            ->get(['user_id', 'logged_in_at'])
            ->each(fn (LoginHistory $h) => $events->push($event($h->user_id, 'sign_in', $h->getRawOriginal('logged_in_at'))));

        return $events;
    }
}
