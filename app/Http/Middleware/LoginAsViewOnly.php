<?php

namespace App\Http\Middleware;

use App\Models\LoginAsSession;
use App\Support\Analytics;
use App\Support\LoginAs;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a "Login as member" visit (App\Support\LoginAs) view-only and
 * without footprints. For every member-site request of a browser session
 * that holds a visit:
 *
 *  1. the visit must still stand: same staff account signed in to the admin
 *     panel, same member on the member site, inside the time limit —
 *     otherwise it is ended and the member site signed out;
 *  2. anything but opening a page (POST / PUT / PATCH / DELETE) is refused —
 *     Exit and Logout end the visit instead;
 *  3. the page is built inside a database transaction that is rolled back,
 *     so what a page writes just by opening (profile view, contact view,
 *     completion %, notifications, queued emails) never reaches the member.
 *
 * The admin panel has its own middleware list and its Livewire requests are
 * skipped here, so staff can keep working in the panel in another tab.
 */
class LoginAsViewOnly
{
    /** Pages that sign someone in or take money: never during a visit. */
    private const REFUSED_PAGES = ['email.sign-in', 'membership.return.phonepe'];

    public const NOTICE = 'View only: nothing can be changed, sent or paid for while you are logged in as a member.';

    public function handle(Request $request, Closure $next): Response
    {
        if (! LoginAs::pending() || $this->isAdminPanel($request)) {
            return $next($request);
        }

        $visit = LoginAs::current();
        if ($why = $this->noLongerStands($visit)) {
            LoginAs::end($why);

            return redirect(LoginAs::adminUrl($visit));
        }

        if ($request->routeIs('login-as.exit', 'logout')) {
            LoginAs::end('exit');

            return redirect(LoginAs::adminUrl($visit));
        }

        if (! $request->isMethodSafe() || $request->routeIs(...self::REFUSED_PAGES)) {
            $visit->increment('blocked_actions');

            return $request->expectsJson()
                ? response()->json(['message' => self::NOTICE, 'error' => self::NOTICE], 403)
                : redirect()->back(fallback: '/dashboard')->with('login_as_notice', self::NOTICE);
        }

        $this->recordPage($request, $visit);

        $request->attributes->set('login_as.active', true);
        $request->attributes->set('login_as.visit', $visit);

        $level = DB::transactionLevel();
        DB::beginTransaction();
        try {
            return $next($request);
        } finally {
            try {
                DB::rollBack($level);
            } catch (\Throwable $e) {
                report($e);
            }
            // key moments queued for GA / PostHog by the page: never sent
            $request->session()->forget(Analytics::SESSION_KEY);
        }
    }

    private function noLongerStands(?LoginAsSession $visit): ?string
    {
        if (! $visit || ! $visit->isOpen()) {
            return 'exit';
        }
        if (! $visit->expires_at || now()->greaterThanOrEqualTo($visit->expires_at)) {
            return 'expired';
        }
        $staff = Auth::guard('admin')->user();
        if (! $staff || (int) $staff->getAuthIdentifier() !== (int) $visit->staff_user_id || ! LoginAs::allowed($staff)) {
            return 'staff_signed_out';
        }
        $member = Auth::guard('web')->user();
        if (! $member || (int) $member->getAuthIdentifier() !== (int) $visit->member_user_id) {
            return 'member_changed';
        }

        return null;
    }

    /** The panel's own pages and its Livewire calls (the staff account's own work). */
    private function isAdminPanel(Request $request): bool
    {
        $admin = trim((string) config('app.admin_path', 'admin'), '/');
        $path = trim($request->path(), '/');

        return $path === $admin
            || str_starts_with($path, $admin.'/')
            || str_starts_with($path, 'livewire')
            || str_ends_with((string) $request->route()?->getName(), 'livewire.update');
    }

    /** Pages opened, for the audit record (addresses only, never what was typed into a search). */
    private function recordPage(Request $request, LoginAsSession $visit): void
    {
        if ($request->ajax() || $request->expectsJson() || ! $request->isMethod('GET')) {
            return;
        }

        try {
            $pages = $visit->pages ?? [];
            if (count($pages) < LoginAs::MAX_PAGES) {
                $pages[] = [
                    't' => now()->timezone(config('app.display_timezone', 'Asia/Kolkata'))->format('g:i:s A'),
                    'p' => '/'.ltrim($request->path(), '/'),
                ];
            }
            $visit->forceFill(['pages' => $pages, 'pages_opened' => $visit->pages_opened + 1])->save();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
