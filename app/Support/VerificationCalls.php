<?php

namespace App\Support;

use App\Filament\Pages\MembersWithoutPhoto;
use App\Http\Controllers\DiscoverController;
use App\Models\Profile;
use App\Models\ProfileNote;
use App\Models\ProfilePhoto;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\VerificationCall;
use App\Services\DiscoverConfigService;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Verification calls (12 Nov 2026). The owner: "our staff has to call every new profile
 * at least once for verification". Their call goes: check a few details the profile
 * already has (date of birth etc.), fill in what is missing, ask for a photo on WhatsApp
 * if there is none, and sometimes share a matches link on WhatsApp afterwards; when the
 * member does not pick up, a WhatsApp message.
 *
 *  - Every new member gets one row, shared out to the staff whose role has the
 *    "verification_calls" right: whoever has the fewest calls still open, in turn when
 *    that is equal (a member staff registered goes to that staff member). Branch staff
 *    get only their branch's members. Members who joined before the list started
 *    (START_SETTING, 30 days back the first time) are not added.
 *  - A call nobody has made 2 days after it was due is open to any caller ("Anyone can
 *    call"), so nobody waits because someone is on leave. Staff with "assign_lead"
 *    (managers, the owner) see every call and can move them.
 *  - The member edit page is the call screen. Its "Save and mark checked on call" is
 *    "verified". The other results are recorded with checkedOnCall() / record(): no
 *    answer (closed as "could not reach" after 3), call back at a time, wrong number,
 *    details did not match (for a manager to check), not looking any more.
 *  - Every try is a Call note and every WhatsApp message a WhatsApp note in the member's
 *    history, so Staff Activity counts them like any other call.
 */
final class VerificationCalls
{
    public const PERMISSION = 'verification_calls';

    public const NOTE_PREFIX = 'Verification call:';

    public const MAX_NO_ANSWER = 3;

    public const ANYONE_AFTER_DAYS = 2;

    public const START_DAYS = 30;

    public const START_SETTING = 'verification_calls_from';

    /** What can happen on a call, besides "verified" (the edit page's Save and mark checked). */
    public const RESULTS = [
        'no_answer' => 'No answer / switched off',
        'call_back' => 'Busy: call back later',
        'wrong_number' => 'Wrong number',
        'mismatch' => 'Details did not match',
        'not_looking' => 'Not looking any more',
    ];

    public static function enabled(): bool
    {
        // Settings → Site Settings → Staff tools → Verification calls (off until switched on).
        // Never throws: this is asked on the member edit page and in the menu badge, and a
        // site part-way through the migration must not break there.
        return (bool) self::memo('enabled', fn () => rescue(
            fn () => AdminModules::verificationCalls() && Schema::hasTable('verification_calls'),
            false,
            false,
        ));
    }

    public static function isStaff(?User $user): bool
    {
        return $user !== null && ($user->staff_role_id !== null || $user->role === 'admin');
    }

    /** The owner, admins and managers (anyone who may assign leads): every call, and moving them. */
    public static function managesAll(?User $user): bool
    {
        if (! self::isStaff($user)) {
            return false;
        }

        return $user->isSuperAdmin() || ($user->role === 'admin' && $user->staff_role_id === null) || $user->hasPermission('assign_lead');
    }

    public static function isCaller(?User $user): bool
    {
        return $user !== null && self::callerIds()->contains($user->id);
    }

    /** The page is for callers and for whoever manages them. */
    public static function canUse(?User $user): bool
    {
        return self::enabled() && (self::isCaller($user) || self::managesAll($user));
    }

    /** Active staff whose role takes verification calls (never the owner's super admin role). */
    public static function callers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereNotNull('staff_role_id')
            ->whereHas('staffRole', fn ($role) => $role->where('is_active', true)->where('slug', '!=', 'super_admin')
                ->whereHas('permissions', fn ($p) => $p->where('permission_key', self::PERMISSION)->whereIn('scope', ['yes', 'all', 'own'])))
            ->with('staffRole')
            ->orderBy('id')
            ->get(['id', 'name', 'branch_id', 'staff_role_id', 'calls_away_until']);
    }

    /** Away (on leave): new members skip them until that day. */
    public static function isAway(User $caller): bool
    {
        return $caller->calls_away_until !== null
            && Carbon::parse($caller->calls_away_until, StaffActivity::tz())->endOfDay()->gte(now(StaffActivity::tz()));
    }

    /** The callers new members are shared out to: those not away. */
    public static function rotation(): Collection
    {
        return self::callers()->reject(fn (User $c) => self::isAway($c))->values();
    }

    /**
     * A manager marks a caller away until a day (null: until switched back), or back
     * (away false). With $share, their open calls go to the others now. Returns how
     * many calls moved.
     */
    public static function setAway(User $manager, int $staffId, bool $away, ?string $until = null, bool $share = false): int
    {
        if (! self::managesAll($manager)) {
            throw new DomainException('Only a manager can mark someone away.');
        }
        $caller = self::callersFor($manager)->firstWhere('id', $staffId);
        if (! $caller) {
            throw new DomainException('Choose a staff member who takes verification calls.');
        }
        $day = null;
        if ($away) {
            $day = $until ? rescue(fn () => Carbon::createFromFormat('Y-m-d', $until, StaffActivity::tz()), null, false) : Carbon::create(2099, 12, 31);
            if (! $day || $day->endOfDay()->lt(now(StaffActivity::tz()))) {
                throw new DomainException('Pick today or a later day.');
            }
        }
        User::whereKey($caller->id)->update(['calls_away_until' => $day?->toDateString()]);
        StaffActionLog::record($away ? 'verification_away' : 'verification_back', null, array_filter(['staff' => $caller->name, 'until' => $until]));
        self::forget();

        return $away && $share ? self::moveAll($manager, $caller->id, null) : 0;
    }

    public static function callerIds(): Collection
    {
        return self::memo('callers', fn () => self::callers()->pluck('id'));
    }

    /** The callers a manager works with: all of them for head office and the owner, else their own branch's. */
    public static function callersFor(User $viewer): Collection
    {
        return self::callers()
            ->filter(fn (User $c) => $viewer->branch_id === null || $viewer->isSuperAdmin() || self::sameBranch($c, $viewer->branch_id))
            ->values();
    }

    /** Forget what this request remembered (after a change to roles or the list). */
    public static function forget(): void
    {
        if (app()->bound('verification-calls.memo')) {
            app()->forgetInstance('verification-calls.memo');
        }
    }

    /** Members who joined from this day on are on the list. */
    public static function startDate(): Carbon
    {
        $value = SiteSetting::getValue(self::START_SETTING);
        $date = $value ? rescue(fn () => Carbon::parse($value, StaffActivity::tz()), null, false) : null;
        if (! $date) {
            $date = now(StaffActivity::tz())->subDays(self::START_DAYS);
            SiteSetting::setValue(self::START_SETTING, $date->toDateString());
        }

        return $date->startOfDay()->setTimezone(config('app.timezone'));
    }

    public static function setStartDate(User $staff, string $date): Carbon
    {
        if (! self::managesAll($staff)) {
            throw new DomainException('Only a manager can change where the list starts.');
        }
        $day = rescue(fn () => Carbon::createFromFormat('Y-m-d', $date, StaffActivity::tz()), null, false);
        if (! $day || $day->isFuture() || $day->lt(now()->subYears(2))) {
            throw new DomainException('Pick a day in the last two years.');
        }
        SiteSetting::setValue(self::START_SETTING, $day->toDateString());
        StaffActionLog::record('verification_list_start', null, ['from' => $day->toDateString()]);
        self::sync();

        return self::startDate();
    }

    /**
     * Put every new member who is not on the list yet on it, shared out to the callers.
     * Safe to run at any time and from two places at once (one row per member).
     * Returns how many were added.
     */
    public static function sync(): int
    {
        if (! self::enabled()) {
            return 0;
        }
        $from = self::startDate();
        $new = self::eligible(Profile::query())
            ->where('profiles.created_at', '>=', $from)
            ->whereNotExists(fn ($q) => $q->from('verification_calls')->whereColumn('verification_calls.profile_id', 'profiles.id'))
            ->orderBy('profiles.created_at')
            ->orderBy('profiles.id')
            ->limit(1000)
            ->get(['profiles.id', 'profiles.branch_id', 'profiles.created_by_staff_id', 'profiles.registration_source', 'profiles.created_at', 'profiles.details_checked_at', 'profiles.details_checked_by']);
        if ($new->isEmpty()) {
            return 0;
        }

        $callers = self::rotation();
        $staffNames = User::whereIn('id', $new->pluck('created_by_staff_id')->filter()->unique())->pluck('name', 'id');
        $load = self::openLoad();
        $lastGiven = VerificationCall::query()->whereNotNull('assigned_to')->groupBy('assigned_to')
            ->selectRaw('assigned_to, max(assigned_at) as at')->pluck('at', 'assigned_to')->map(fn ($at) => (string) $at)->all();
        // calls already written in their notes since they joined, before this list existed
        $before = ProfileNote::query()->whereIn('profile_id', $new->pluck('id'))->where('note_type', 'call')
            ->groupBy('profile_id')->selectRaw('profile_id, count(*) as n, max(created_at) as at')->get()->keyBy('profile_id');

        $added = 0;
        foreach ($new as $p) {
            $now = now();
            $row = [
                'profile_id' => $p->id, 'status' => 'waiting', 'attempts' => 0, 'no_answers' => 0,
                'due_at' => $p->created_at, 'created_at' => $now, 'updated_at' => $now,
            ];
            if ($earlier = $before[$p->id] ?? null) {
                $row['attempts'] = (int) $earlier->n;
                $row['last_attempt_at'] = $earlier->at;
            }
            // details already gone through with them on a call ("Save and mark checked on call")
            if ($p->details_checked_at && $p->details_checked_at->gte($p->created_at)) {
                $row = array_merge($row, ['status' => 'verified', 'assigned_to' => $p->details_checked_by, 'assigned_at' => $p->details_checked_at,
                    'done_at' => $p->details_checked_at, 'done_by' => $p->details_checked_by, 'attempts' => max(1, $row['attempts'])]);
            } elseif ($p->created_by_staff_id && $p->registration_source === \App\Support\DeviceDetector::ADMIN) {
                // added by staff from the console (owner, 13 Nov 2026): they spoke to the family
                // then, so it counts as verified, marked "Added by staff"; a manager can still
                // put the member on the list (Call again)
                $staffAdded = true;
                $row = array_merge($row, ['status' => 'staff_added', 'assigned_to' => $p->created_by_staff_id, 'assigned_at' => $p->created_at,
                    'done_at' => $p->created_at, 'done_by' => $p->created_by_staff_id]);
            } elseif ($caller = self::pick($p, $callers, $load, $lastGiven)) {
                $row['assigned_to'] = $caller->id;
                $row['assigned_at'] = $now;
                $load[$caller->id] = ($load[$caller->id] ?? 0) + 1;
                $lastGiven[$caller->id] = $now->toDateTimeString();
            }
            $inserted = DB::table('verification_calls')->insertOrIgnore($row);
            $added += $inserted;
            if ($inserted && ($staffAdded ?? false)) {
                ProfileNote::create(['profile_id' => $p->id, 'admin_user_id' => $p->created_by_staff_id, 'note_type' => 'general',
                    'note' => self::NOTE_PREFIX.' not needed. Profile added by staff'.(isset($staffNames[$p->created_by_staff_id]) ? ' ('.$staffNames[$p->created_by_staff_id].')' : '').'.']);
            }
            $staffAdded = false;
        }

        return $added;
    }

    /** Members who should get the call: real members, live, not suspended or deleted. */
    private static function eligible(Builder $query): Builder
    {
        return $query->withoutDeletedAccounts()
            ->whereNotNull('profiles.full_name')
            ->where('profiles.is_active', true)
            ->where(fn ($s) => $s->whereNull('profiles.suspension_status')->orWhereNotIn('profiles.suspension_status', ['suspended', 'banned']))
            ->whereHas('user', fn ($u) => $u->whereNull('staff_role_id')->where('is_active', true));
    }

    /** caller id => calls still open */
    private static function openLoad(): array
    {
        return VerificationCall::query()->whereIn('status', VerificationCall::OPEN)->whereNotNull('assigned_to')
            ->groupBy('assigned_to')->selectRaw('assigned_to, count(*) as n')->pluck('n', 'assigned_to')
            ->map(fn ($n) => (int) $n)->all();
    }

    /** The caller a new member goes to: the one who registered them, else the fewest open calls, in turn. */
    private static function pick(object $profile, Collection $callers, array $load, array $lastGiven, ?int $except = null): ?User
    {
        $fit = $callers->filter(fn (User $c) => $c->id !== $except && self::sameBranch($c, $profile->branch_id));
        if ($fit->isEmpty()) {
            return null;
        }
        if ($profile->created_by_staff_id && ($own = $fit->firstWhere('id', (int) $profile->created_by_staff_id))) {
            return $own;
        }

        return $fit->sortBy(fn (User $c) => [$load[$c->id] ?? 0, $lastGiven[$c->id] ?? '', $c->id])->first();
    }

    private static function sameBranch(User $staff, $branchId): bool
    {
        return $staff->branch_id === null || $branchId === null || (int) $staff->branch_id === (int) $branchId || $staff->isSuperAdmin();
    }

    /** The calls this staff member may see: live members of their branch. */
    public static function rows(User $viewer): Builder
    {
        $query = VerificationCall::query()
            ->join('profiles', 'profiles.id', '=', 'verification_calls.profile_id')
            ->whereNull('profiles.deleted_at')
            ->whereNull('profiles.account_deleted_at')
            ->where('profiles.is_active', true)
            ->select('verification_calls.*');
        if ($viewer->branch_id !== null && ! $viewer->isSuperAdmin()) {
            $query->where(fn ($b) => $b->where('profiles.branch_id', $viewer->branch_id)->orWhereNull('profiles.branch_id'));
        }

        return $query;
    }

    /** One tab of the list: mine, anyone (can call), all (open), check (details did not match), done (last 30 days). */
    public static function tab(User $viewer, string $tab): Builder
    {
        $query = self::rows($viewer);
        $open = fn (Builder $q) => $q->whereIn('verification_calls.status', VerificationCall::OPEN);

        return match ($tab) {
            'anyone' => $open($query)->where(fn ($w) => self::anyoneWhere($w))->orderBy('verification_calls.due_at')->orderBy('verification_calls.id'),
            'all' => $open($query)->orderBy('verification_calls.due_at')->orderBy('verification_calls.id'),
            'check' => $query->where('verification_calls.status', 'mismatch')->orderByDesc('verification_calls.done_at'),
            'done' => $query->whereNotIn('verification_calls.status', VerificationCall::OPEN)
                ->where('verification_calls.done_at', '>=', now()->subDays(30))
                ->when(! self::managesAll($viewer), fn ($q) => $q->where(fn ($m) => $m->where('verification_calls.done_by', $viewer->id)->orWhere('verification_calls.assigned_to', $viewer->id)))
                ->orderByDesc('verification_calls.done_at'),
            default => $open($query)->where('verification_calls.assigned_to', $viewer->id)->orderBy('verification_calls.due_at')->orderBy('verification_calls.id'),
        };
    }

    /**
     * Not given to anyone, given to someone who no longer takes calls, or left 2+ days:
     * due for 2 days and given to its caller 2+ days ago (so the members already waiting
     * when the list starts, or moved to someone, are theirs for 2 days first).
     */
    private static function anyoneWhere($where): void
    {
        $cut = now()->subDays(self::ANYONE_AFTER_DAYS);
        $where->whereNull('verification_calls.assigned_to')
            ->orWhereNotIn('verification_calls.assigned_to', self::callerIds()->all() ?: [0])
            ->orWhere(fn ($late) => self::lateWhere($late, $cut));
    }

    private static function lateWhere($where, CarbonInterface $cut): void
    {
        $where->where('verification_calls.due_at', '<=', $cut)
            ->where(fn ($given) => $given->whereNull('verification_calls.assigned_at')->orWhere('verification_calls.assigned_at', '<=', $cut));
    }

    /** Left 2+ days by the caller it is given to. */
    public static function isLate(VerificationCall $row): bool
    {
        $cut = now()->subDays(self::ANYONE_AFTER_DAYS);

        return $row->isOpen() && $row->due_at !== null && $row->due_at->lte($cut)
            && ($row->assigned_at === null || $row->assigned_at->lte($cut));
    }

    public static function isForAnyone(VerificationCall $row): bool
    {
        return $row->isOpen() && ($row->assigned_to === null
            || ! self::callerIds()->contains((int) $row->assigned_to)
            || self::isLate($row));
    }

    /** Whether this staff member may record calls for this member. */
    public static function mayWork(?User $staff, VerificationCall $row): bool
    {
        if (! self::isStaff($staff)) {
            return false;
        }
        $profile = $row->relationLoaded('profile') ? $row->profile : $row->profile()->first();
        if (! $profile || ! self::sameBranch($staff, $profile->branch_id)) {
            return false;
        }
        if (self::managesAll($staff)) {
            return true;
        }
        if (! self::isCaller($staff)) {
            return false;
        }

        return (int) $row->assigned_to === $staff->id || self::isForAnyone($row)
            || (! $row->isOpen() && ((int) $row->done_by === $staff->id));
    }

    private static function refusal(User $staff, VerificationCall $row): string
    {
        if ($row->isOpen() && $row->assigned_to && $row->caller) {
            return "This call is given to {$row->caller->name}. A manager can move it to you, or it opens to everyone after ".self::ANYONE_AFTER_DAYS.' days.';
        }

        return 'You can\'t record calls for this member.';
    }

    /**
     * Record what happened on a call (anything but "verified", which the edit page's Save
     * and mark checked on call records through checkedOnCall()). Returns the message to show.
     */
    public static function record(User $staff, VerificationCall $row, string $result, ?string $note = null, ?CarbonInterface $callBackAt = null): string
    {
        if (! array_key_exists($result, self::RESULTS)) {
            throw new DomainException('Pick what happened on the call.');
        }
        if (! self::mayWork($staff, $row)) {
            throw new DomainException(self::refusal($staff, $row));
        }
        if (! $row->isOpen()) {
            throw new DomainException('This call is already finished ('.$row->label().'). Put it back on the list first.');
        }
        $note = Str::limit(trim((string) $note), 500, '');
        if ($result === 'mismatch' && $note === '') {
            throw new DomainException('Write what did not match.');
        }
        if ($result === 'not_looking' && $note === '') {
            throw new DomainException('Write what they said (for example: already settled).');
        }
        if ($result === 'call_back') {
            if (! $callBackAt || $callBackAt->lte(now())) {
                throw new DomainException('Pick when to call back: a time later than now.');
            }
            if ($callBackAt->gt(now()->addDays(60))) {
                throw new DomainException('Pick a call-back time in the next 60 days.');
            }
        }

        $noAnswers = $row->no_answers + ($result === 'no_answer' ? 1 : 0);
        $status = match ($result) {
            'no_answer' => $noAnswers >= self::MAX_NO_ANSWER ? 'not_reached' : 'waiting',
            default => $result,
        };
        $closed = ! in_array($status, VerificationCall::OPEN, true);
        $due = match (true) {
            $closed => $row->due_at,
            $result === 'call_back' => Carbon::instance($callBackAt)->setTimezone(config('app.timezone')),
            default => self::nextTry(),
        };
        // a caller who picks up someone else's call (open to anyone) takes it over
        $takeOver = self::isCaller($staff) && (int) $row->assigned_to !== $staff->id;

        DB::transaction(function () use ($row, $staff, $noAnswers, $status, $closed, $due, $takeOver, $result, $note) {
            $row->forceFill(array_merge([
                'attempts' => $row->attempts + 1,
                'no_answers' => $noAnswers,
                'last_attempt_at' => now(),
                'last_attempt_by' => $staff->id,
                'status' => $status,
                'due_at' => $due,
                'done_at' => $closed ? now() : null,
                'done_by' => $closed ? $staff->id : null,
            ], $takeOver ? ['assigned_to' => $staff->id, 'assigned_at' => now()] : []))->save();

            $what = match ($result) {
                'no_answer' => $status === 'not_reached'
                    ? 'no answer (try '.$noAnswers.' of '.self::MAX_NO_ANSWER.'). Could not reach them; the call is closed.'
                    : 'no answer (try '.$noAnswers.' of '.self::MAX_NO_ANSWER.'). Next try '.$due->displayTz()->format('j M').'.',
                'call_back' => 'busy, call back on '.$due->displayTz()->format('j M, g:i A').'.',
                'wrong_number' => 'wrong number.',
                'mismatch' => 'the details did not match. For a manager to check.',
                'not_looking' => 'not looking any more.',
            };
            ProfileNote::create([
                'profile_id' => $row->profile_id,
                'admin_user_id' => $staff->id,
                'note_type' => 'call',
                'note' => self::NOTE_PREFIX.' '.$what.($note !== '' ? ' '.$note : ''),
            ]);
        });
        StaffActionLog::record('verification_'.$result, $row->profile_id, ['try' => $row->attempts]);

        return match ($result) {
            'no_answer' => $status === 'not_reached'
                ? 'Noted. No answer '.self::MAX_NO_ANSWER.' times: closed as "could not reach".'
                : 'Noted. It comes back on the list '.$due->displayTz()->format('j M, g:i A').'. Send the WhatsApp message too.',
            'call_back' => 'Noted. It comes back on the list '.$due->displayTz()->format('j M, g:i A').'.',
            'mismatch' => 'Noted. A manager will see it under "Needs a check".',
            default => 'Noted.',
        };
    }

    /** No answer: try again the next day, at 10 in the morning. */
    private static function nextTry(): Carbon
    {
        return now(StaffActivity::tz())->addDay()->setTime(10, 0)->setTimezone(config('app.timezone'));
    }

    /**
     * The edit page's "Save and mark checked on call": the member is verified, whoever's
     * list they were on. A member not on the list (joined before it started) gets a
     * verified row, so they are not added later. Never stops the save.
     */
    public static function checkedOnCall(User $staff, Profile $profile): void
    {
        if (! self::enabled() || ! self::isStaff($staff)) {
            return;
        }
        try {
            $row = VerificationCall::firstWhere('profile_id', $profile->getKey());
            if ($row?->status === 'verified') {
                return;
            }
            $done = ['status' => 'verified', 'done_at' => now(), 'done_by' => $staff->id, 'last_attempt_at' => now(), 'last_attempt_by' => $staff->id];
            if ($row) {
                $row->forceFill($done + ['attempts' => $row->attempts + 1])->save();
            } else {
                (new VerificationCall)->forceFill($done + ['profile_id' => $profile->getKey(), 'assigned_to' => $staff->id, 'assigned_at' => now(),
                    'attempts' => 1, 'no_answers' => 0, 'due_at' => now()])->save();
            }
            // the call that verified them is a call like the tries before it: in the member's
            // history, and counted for the staff member (Staff Activity, this page's week)
            ProfileNote::create([
                'profile_id' => $profile->getKey(),
                'admin_user_id' => $staff->id,
                'note_type' => 'call',
                'note' => self::NOTE_PREFIX.' details checked with the member. Verified.',
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Put a finished call back on the list (the member called back, or a manager wants it redone). */
    public static function reopen(User $staff, VerificationCall $row): void
    {
        if (! self::mayWork($staff, $row)) {
            throw new DomainException(self::refusal($staff, $row));
        }
        if ($row->isOpen()) {
            return;
        }
        $row->forceFill(['status' => 'waiting', 'no_answers' => 0, 'due_at' => now(), 'done_at' => null, 'done_by' => null]
            + (self::isCaller($staff) ? ['assigned_to' => $staff->id, 'assigned_at' => now()] : []))->save();
        ProfileNote::create(['profile_id' => $row->profile_id, 'admin_user_id' => $staff->id, 'note_type' => 'general',
            'note' => self::NOTE_PREFIX.' put back on the calls list.']);
    }

    /** A manager gives one call to a caller (null: to anyone). */
    public static function assign(User $manager, VerificationCall $row, ?int $toId): void
    {
        if (! self::managesAll($manager) || ! self::mayWork($manager, $row)) {
            throw new DomainException('Only a manager can move calls.');
        }
        $to = $toId ? self::callers()->firstWhere('id', $toId) : null;
        if ($toId && ! $to) {
            throw new DomainException('Choose a staff member who takes verification calls.');
        }
        if ($to && ! self::sameBranch($to, $row->profile?->branch_id)) {
            throw new DomainException("{$to->name} works for another branch.");
        }
        $row->forceFill(['assigned_to' => $to?->id, 'assigned_at' => now()])->save();
        StaffActionLog::record('verification_moved', $row->profile_id, ['to' => $to?->name ?? 'anyone']);
    }

    /**
     * A manager gives several calls (ticked on the list) to one caller, or to anyone (null).
     * Finished calls, another branch's members and calls already with that person are
     * skipped. Returns how many moved.
     */
    public static function assignMany(User $manager, array $ids, ?int $toId): int
    {
        if (! self::managesAll($manager)) {
            throw new DomainException('Only a manager can move calls.');
        }
        $to = $toId ? self::callers()->firstWhere('id', $toId) : null;
        if ($toId && ! $to) {
            throw new DomainException('Choose a staff member who takes verification calls.');
        }
        $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', $ids)))), 0, 500);
        if ($ids === []) {
            throw new DomainException('Tick the calls to move first.');
        }
        $rows = self::rows($manager)->whereIn('verification_calls.id', $ids)
            ->whereIn('verification_calls.status', VerificationCall::OPEN)->with('profile')->get();
        $moved = 0;
        foreach ($rows as $row) {
            if (($to && ! self::sameBranch($to, $row->profile?->branch_id)) || (int) $row->assigned_to === (int) $to?->id) {
                continue;
            }
            $row->forceFill(['assigned_to' => $to?->id, 'assigned_at' => now()])->save();
            $moved++;
        }
        if ($moved) {
            StaffActionLog::record('verification_moved_many', null, ['to' => $to?->name ?? 'anyone', 'calls' => $moved]);
        }

        return $moved;
    }

    /**
     * Move every open call of one staff member (on leave, left): to one other caller, or
     * shared out among the others like new members. Returns how many moved.
     */
    public static function moveAll(User $manager, int $fromId, ?int $toId): int
    {
        if (! self::managesAll($manager)) {
            throw new DomainException('Only a manager can move calls.');
        }
        if ($toId === $fromId) {
            throw new DomainException('Pick someone else to move them to.');
        }
        $callers = self::callers();
        if ($toId && ! $callers->firstWhere('id', $toId)) {
            throw new DomainException('Choose a staff member who takes verification calls.');
        }
        $sharing = self::rotation(); // shared out: not to someone who is away
        $rows = self::rows($manager)->whereIn('verification_calls.status', VerificationCall::OPEN)
            ->where('verification_calls.assigned_to', $fromId)->with('profile')->get();
        $load = self::openLoad();
        $moved = 0;
        foreach ($rows as $row) {
            $to = $toId
                ? $callers->firstWhere('id', $toId)
                : self::pick((object) ['branch_id' => $row->profile?->branch_id, 'created_by_staff_id' => null], $sharing, $load, [], $fromId);
            if (! $to || ! self::sameBranch($to, $row->profile?->branch_id)) {
                continue;
            }
            $row->forceFill(['assigned_to' => $to->id, 'assigned_at' => now()])->save();
            $load[$to->id] = ($load[$to->id] ?? 0) + 1;
            $moved++;
        }
        StaffActionLog::record('verification_moved_all', null, ['from' => User::whereKey($fromId)->value('name'), 'to' => $toId ? User::whereKey($toId)->value('name') : 'shared out', 'calls' => $moved]);

        return $moved;
    }

    /**
     * Calls due now for the menu badge: a caller's own and those open to anyone; for a
     * manager who does not call, only those open to anyone (nobody's, or left 2 days).
     */
    public static function dueCount(?User $viewer): int
    {
        if (! $viewer || ! self::canUse($viewer)) {
            return 0;
        }
        $caller = self::isCaller($viewer);

        return (int) self::memo('due', fn () => self::rows($viewer)
            ->whereIn('verification_calls.status', VerificationCall::OPEN)
            ->where('verification_calls.due_at', '<=', now())
            ->where(fn ($w) => $caller
                ? $w->where('verification_calls.assigned_to', $viewer->id)->orWhere(fn ($a) => self::anyoneWhere($a))
                : self::anyoneWhere($w))
            ->count());
    }

    /**
     * The Work Queue line: [calls due now, when the longest-waiting one fell due]. A caller's
     * own and those open to anyone; every call for whoever manages the list.
     */
    public static function queue(User $viewer): array
    {
        $due = self::rows($viewer)
            ->whereIn('verification_calls.status', VerificationCall::OPEN)
            ->where('verification_calls.due_at', '<=', now())
            ->when(! self::managesAll($viewer), fn ($q) => $q->where(fn ($w) => $w->where('verification_calls.assigned_to', $viewer->id)->orWhere(fn ($a) => self::anyoneWhere($a))));

        return [(clone $due)->count(), (clone $due)->min('verification_calls.due_at')];
    }

    /**
     * For managers: this week per caller (calls made, verified, could not reach) and what
     * each has open now (due now, waiting 2+ days), plus the list as a whole.
     */
    public static function report(User $viewer): array
    {
        $weekStart = now(StaffActivity::tz())->startOfWeek()->setTimezone(config('app.timezone'));
        $late = now()->subDays(self::ANYONE_AFTER_DAYS);
        $open = self::rows($viewer)->whereIn('verification_calls.status', VerificationCall::OPEN)
            ->groupBy('verification_calls.assigned_to')
            ->select([])
            ->selectRaw('verification_calls.assigned_to as who, count(*) as n, sum(case when verification_calls.due_at <= ? then 1 else 0 end) as due, '
                .'sum(case when verification_calls.due_at <= ? and (verification_calls.assigned_at is null or verification_calls.assigned_at <= ?) then 1 else 0 end) as late', [now(), $late, $late])
            ->get()->keyBy(fn ($r) => (int) $r->who);
        $calls = ProfileNote::query()->where('note_type', 'call')->where('note', 'like', self::NOTE_PREFIX.'%')
            ->where('created_at', '>=', $weekStart)->groupBy('admin_user_id')->selectRaw('admin_user_id, count(*) as n')->pluck('n', 'admin_user_id');
        $closed = fn (string $status) => self::rows($viewer)->where('verification_calls.status', $status)->where('verification_calls.done_at', '>=', $weekStart)
            ->groupBy('verification_calls.done_by')->select([])->selectRaw('verification_calls.done_by as who, count(*) as n')->pluck('n', 'who');
        $verified = $closed('verified');
        $notReached = $closed('not_reached');

        $rows = self::callersFor($viewer)
            ->map(fn (User $c) => [
                'id' => $c->id,
                'away' => self::isAway($c) ? (str_starts_with((string) $c->calls_away_until, '2099') ? 'Away' : 'Away until '.Carbon::parse($c->calls_away_until)->format('j M')) : null,
                'name' => $c->name,
                'open' => (int) ($open[$c->id]->n ?? 0),
                'due' => (int) ($open[$c->id]->due ?? 0),
                'late' => (int) ($open[$c->id]->late ?? 0),
                'calls' => (int) ($calls[$c->id] ?? 0),
                'verified' => (int) ($verified[$c->id] ?? 0),
                'not_reached' => (int) ($notReached[$c->id] ?? 0),
            ])->values();

        $all = self::rows($viewer);

        return [
            'rows' => $rows,
            'unassigned' => (int) ($open[0]->n ?? 0),
            'from' => self::startDate(),
            'total' => (clone $all)->count(),
            'verified' => (clone $all)->where('verification_calls.status', 'verified')->count(),
            'openTotal' => (clone $all)->whereIn('verification_calls.status', VerificationCall::OPEN)->count(),
        ];
    }

    /** The verification strip on the member edit page, or null when the member is not on the list. */
    public static function card(?User $viewer, Profile $profile): ?array
    {
        if (! self::enabled() || ! self::isStaff($viewer)) {
            return null;
        }
        $row = VerificationCall::with(['caller', 'doneBy'])->firstWhere('profile_id', $profile->getKey());
        if (! $row) {
            return null;
        }
        $row->setRelation('profile', $profile);
        $may = self::mayWork($viewer, $row);
        $digits = $may ? MembersWithoutPhoto::whatsappDigits($profile) : null;
        $hasPhoto = ProfilePhoto::where('profile_id', $profile->getKey())->whereIn('approval_status', [ProfilePhoto::STATUS_APPROVED, ProfilePhoto::STATUS_PENDING])->exists();

        return [
            'label' => $row->label(),
            'color' => $row->color(),
            'open' => $row->isOpen(),
            'givenTo' => $row->caller?->name,
            'mine' => (int) $row->assigned_to === $viewer->id,
            'forAnyone' => self::isForAnyone($row),
            'tries' => $row->attempts,
            'due' => $row->isOpen() && $row->due_at ? ($row->due_at->lte(now()) ? 'now' : $row->due_at->displayTz()->format('j M, g:i A')) : null,
            'done' => ! $row->isOpen() && $row->done_at ? $row->done_at->displayTz()->format('j M Y').($row->doneBy ? ' by '.$row->doneBy->name : '') : null,
            'joined' => $profile->created_at?->diffForHumans(),
            'dob' => $profile->date_of_birth?->format('j M Y'),
            'may' => $may,
            'hasPhoto' => $hasPhoto,
            'whatsapp' => $digits ? array_filter([
                'missed' => $row->isOpen() ? self::waUrl($digits, self::message($profile, 'missed')) : null,
                'photo' => $hasPhoto ? null : self::waUrl($digits, self::message($profile, 'photo')),
                'matches' => self::waUrl($digits, self::message($profile, 'matches')),
            ]) : [],
        ];
    }

    public static function waUrl(string $digits, string $message): string
    {
        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
    }

    /** The ready-made WhatsApp messages: missed (we tried to call), photo, matches. */
    public static function message(Profile $profile, string $kind): string
    {
        $site = SiteSetting::getValue('site_name', config('app.name'));
        $first = trim((string) strtok((string) $profile->full_name, ' ')) ?: 'there';

        if ($kind === 'photo') {
            return MembersWithoutPhoto::message($profile);
        }
        if ($kind === 'matches') {
            [$community, $url] = self::communityPage($profile);
            $side = $profile->gender === 'female' ? 'grooms' : 'brides';

            return "Hi {$first}, thank you for speaking with us. "
                .($community ? "Here are {$community} {$side} on {$site}: {$url}" : "You can browse {$side} on {$site} here: {$url}")
                .' . Sign in to see the matches picked for you: '.route('matches.index');
        }

        return "Hi {$first}, greetings from {$site}! We called you about your new profile ({$profile->matri_id}) but could not reach you. "
            .'We would like to check a few details with you and help you get more responses from families. '
            .'Please reply with a good time to call, or call us back on this number.';
    }

    /**
     * The member's own community on the Discover pages, the other side (brides for a man):
     * [community name, link], or [null, the Discover hub] when it has no page.
     */
    public static function communityPage(Profile $profile): array
    {
        try {
            $religious = $profile->religiousInfo;
            $pages = collect(app(DiscoverConfigService::class)->communities())->keyBy('slug');
            $counts = DiscoverController::profileCounts('community-matrimony');
            $side = $profile->gender === 'female' ? 'grooms' : 'brides';
            foreach ([$religious?->denomination, $religious?->muslim_sect, $religious?->jain_sect, $religious?->caste] as $value) {
                $slug = Str::slug((string) $value);
                if ($slug !== '' && $pages->has($slug) && isset($counts[$slug])) {
                    return [Str::replaceLast(' Profiles', '', (string) $pages[$slug]['label']), route('discover.side', ['community-matrimony', $slug, $side])];
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return [null, route('discover.hub')];
    }

    /** Write the WhatsApp message staff opened into the member's history (missed, photo, matches). */
    public static function logWhatsApp(User $staff, Profile $profile, string $kind): void
    {
        $row = VerificationCall::firstWhere('profile_id', $profile->getKey());
        if (! $row || ! self::mayWork($staff, $row->setRelation('profile', $profile))) {
            throw new DomainException('You can\'t send messages for this member from here.');
        }
        $note = match ($kind) {
            'missed' => self::NOTE_PREFIX.' opened the "we tried to call you" message on WhatsApp.',
            // the Members Without Photo list reads this prefix as "asked"
            'photo' => MembersWithoutPhoto::NOTE_PREFIX.' asked on WhatsApp to send a profile photo (verification call).',
            'matches' => self::NOTE_PREFIX.' opened the matches link message on WhatsApp.',
            default => throw new DomainException('Unknown message.'),
        };
        ProfileNote::create(['profile_id' => $profile->getKey(), 'admin_user_id' => $staff->id, 'note_type' => 'whatsapp', 'note' => $note]);
    }

    /** Request-long memory (the menu badge is asked on every admin page). */
    private static function memo(string $key, callable $make): mixed
    {
        $app = app();
        if (! $app->bound('verification-calls.memo')) {
            $app->instance('verification-calls.memo', new \ArrayObject);
        }
        $store = $app->make('verification-calls.memo');
        if (! $store->offsetExists($key)) {
            $store[$key] = $make();
        }

        return $store[$key];
    }
}
