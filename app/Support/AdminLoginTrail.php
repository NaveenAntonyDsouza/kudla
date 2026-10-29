<?php

namespace App\Support;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;

/**
 * Admin sign-ins (audit, 29 Oct 2026): the admin panel's login never wrote
 * Login History, so the main admin account showed its last login as 9 Aug
 * while in daily use. Every staff sign-in — and every wrong password typed
 * for a staff account — now lands in Login History (method "admin" /
 * "admin_failed") and refreshes the account's last login. Members' logins
 * are recorded by their own login controller and are left alone here.
 */
class AdminLoginTrail
{
    public const SIGNED_IN = 'admin';

    public const WRONG_PASSWORD = 'admin_failed';

    public static function login(Login $event): void
    {
        $user = $event->user;
        if (! self::isStaff($user)) {
            return;
        }

        try {
            LoginHistory::record($user, self::SIGNED_IN);
            DB::table('users')->where('id', $user->getAuthIdentifier())->update(['last_login_at' => now()]);
        } catch (\Throwable) {
            // a trail must never stop someone signing in
        }
    }

    public static function failed(Failed $event): void
    {
        if (self::isStaff($event->user)) {
            LoginHistory::record($event->user, self::WRONG_PASSWORD);
        }
    }

    private static function isStaff($user): bool
    {
        return $user instanceof User && ($user->staff_role_id !== null || $user->role === 'admin');
    }
}
