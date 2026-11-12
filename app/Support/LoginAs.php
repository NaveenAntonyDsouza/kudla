<?php

namespace App\Support;

use App\Models\AdminActivityLog;
use App\Models\LoginAsSession;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * "Login as member" (impersonation), 11 Nov 2026: a staff account opens the
 * member site exactly as one member sees it, to answer "I can't see my
 * photo / my matches / my plan" without asking for the member's password.
 *
 * The rules, all enforced here and in App\Http\Middleware\LoginAsViewOnly:
 *  - own staff right (login_as_member), off for every role until the owner
 *    switches it on; the staff account must have two-step sign-in set up;
 *  - a reason is typed first; start, end, pages opened and refused button
 *    presses are recorded (login_as_sessions + the Activity Log);
 *  - VIEW ONLY: every form / button on the member site is refused, and any
 *    database write a page makes just by opening is undone, so the member
 *    gets no profile views, "last active", contact views, emails or
 *    notifications from the visit, and analytics are switched off;
 *  - it ends after MINUTES, on Exit, or when the staff account signs out.
 *
 * The admin panel and the member site keep separate sign-ins in one browser
 * session (config/auth.php), so the staff account stays signed in to the
 * panel while the member site's sign-in is the member.
 */
final class LoginAs
{
    public const SESSION_KEY = 'login_as';

    public const PERMISSION = 'login_as_member';

    public const MINUTES = 30;

    /** Most page addresses kept per visit (the count carries on past it). */
    public const MAX_PAGES = 300;

    /**
     * Is THIS request a member-site page being viewed by staff as a member?
     * Set only by LoginAsViewOnly once it has checked the visit, so it is
     * never true in the admin panel (where the same staff account may be
     * working in another tab), in the app's API, or in queue / scheduled jobs.
     */
    public static function active(): bool
    {
        try {
            return (bool) request()->attributes->get('login_as.active', false);
        } catch (\Throwable) {
            return false;
        }
    }

    /** Does this browser session hold a visit (checked or not)? */
    public static function pending(): bool
    {
        try {
            return is_array(self::store()?->get(self::SESSION_KEY));
        } catch (\Throwable) {
            return false;
        }
    }

    /** The open visit of this browser session, or null. */
    public static function current(): ?LoginAsSession
    {
        try {
            $data = self::store()?->get(self::SESSION_KEY);

            return is_array($data) && isset($data['id']) ? LoginAsSession::find($data['id']) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** May this account use "Login as member" at all? (the staff right) */
    public static function allowed(?User $staff): bool
    {
        if (! $staff || ! ($staff->staff_role_id !== null || $staff->role === 'admin')) {
            return false;
        }

        return $staff->isSuperAdmin()
            || ($staff->role === 'admin' && $staff->staff_role_id === null) // legacy owner account
            || $staff->hasPermission(self::PERMISSION);
    }

    /** Why this staff account may not open this member now; null when it may. */
    public static function refusal(?User $staff, ?Profile $profile): ?string
    {
        if (! self::allowed($staff)) {
            return 'Your staff role does not include "Login as member".';
        }
        if (blank($staff->getAppAuthenticationSecret())) {
            return 'Set up two-step sign-in first (My Profile, top right). "Login as member" is only for accounts protected by it.';
        }
        if (! $profile || $profile->trashed()) {
            return 'This member is deleted.';
        }
        $member = $profile->user;
        if (! $member) {
            return 'This member has no login account.';
        }
        if ($member->staff_role_id !== null || $member->role === 'admin' || $member->id === $staff->id) {
            return 'Staff accounts cannot be opened this way.';
        }

        return null;
    }

    /**
     * Start a visit: record it, then sign the member site in as the member
     * for this browser session only (no "remember me", no Login event, so
     * no login history and no "last login" for the member).
     *
     * @throws \DomainException with the refusal sentence
     */
    public static function start(User $staff, Profile $profile, string $reason): LoginAsSession
    {
        if ($why = self::refusal($staff, $profile)) {
            throw new \DomainException($why);
        }
        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw new \DomainException('Please say why, in a few words.');
        }

        // before anything is recorded: without a browser session there is nothing to open
        $session = self::store() ?? throw new \LogicException('Login as member needs a browser session.');

        if (self::pending()) {
            self::end('replaced');
        }

        $member = $profile->user;
        $visit = LoginAsSession::create([
            'staff_user_id' => $staff->id,
            'member_user_id' => $member->id,
            'matri_id' => $profile->matri_id,
            'reason' => mb_substr($reason, 0, 500),
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 500),
            'started_at' => now(),
            'expires_at' => now()->addMinutes(self::MINUTES),
            'pages' => [],
        ]);

        self::log($staff->id, 'login_as_started', $profile->id, [
            'matri_id' => $profile->matri_id,
            'reason' => $visit->reason,
            'visit' => $visit->id,
        ]);

        $guard = Auth::guard('web');
        $session->put($guard->getName(), $member->getAuthIdentifier());
        $session->put(self::SESSION_KEY, ['id' => $visit->id, 'staff' => $staff->id, 'member' => $member->id]);
        $session->forget(Analytics::SESSION_KEY);
        $guard->setUser($member);

        return $visit;
    }

    /** End the visit and sign the member site out (the admin panel sign-in stays). */
    public static function end(string $how = 'exit'): ?LoginAsSession
    {
        $visit = self::current();

        try {
            if ($visit && $visit->isOpen()) {
                $visit->update(['ended_at' => now(), 'ended_how' => $how]);
                self::log($visit->staff_user_id, 'login_as_ended', Profile::withTrashed()->where('user_id', $visit->member_user_id)->value('id'), [
                    'matri_id' => $visit->matri_id,
                    'how' => $how,
                    'minutes' => $visit->minutes,
                    'pages_opened' => $visit->pages_opened,
                    'visit' => $visit->id,
                ]);
            }
        } catch (\Throwable $e) {
            report($e); // the sign-out below must still happen
        }

        try {
            $session = self::store();
            if (! $session) {
                return $visit;
            }
            $session->forget(self::SESSION_KEY);
            $session->forget(Analytics::SESSION_KEY);
            // not logout(): that would replace the member's own "remember me" token
            // and sign their phone out
            Auth::guard('web')->logoutCurrentDevice();
            $session->forget(Auth::guard('web')->getName());
        } catch (\Throwable $e) {
            report($e);
        }
        request()->attributes->set('login_as.active', false);

        return $visit;
    }

    /**
     * The browser session of this request. The app's API, queue workers and
     * scheduled jobs have none (Livewire's test harness calls a panel
     * component without one, so tests fall back to the app's store).
     */
    private static function store(): ?\Illuminate\Contracts\Session\Session
    {
        try {
            if (request()->hasSession()) {
                return request()->session();
            }

            return app()->runningUnitTests() ? app('session.store') : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Where staff land afterwards: the member's page in the admin panel. */
    public static function adminUrl(?LoginAsSession $visit = null): string
    {
        $base = '/'.trim((string) config('app.admin_path', 'admin'), '/');
        $profileId = $visit ? Profile::withTrashed()->where('user_id', $visit->member_user_id)->value('id') : null;

        return $profileId ? $base.'/users/'.$profileId : $base;
    }

    private static function log(int $staffId, string $action, ?int $profileId, array $details): void
    {
        try {
            // the staff account by id: on the member site auth()->id() is the member
            AdminActivityLog::create([
                'admin_user_id' => $staffId,
                'action' => $action,
                'model_type' => $profileId ? 'Profile' : null,
                'model_id' => $profileId,
                'changes' => $details,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
