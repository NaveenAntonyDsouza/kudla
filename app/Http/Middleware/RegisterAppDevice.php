<?php

namespace App\Http\Middleware;

use App\Support\DeviceRegistry;
use App\Support\LoginAs;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Push notifications for the Kudla WebView app (1.0.7+, 20 Nov 2026).
 *
 * The app signs members in through the website, not the API, so it cannot call
 * POST /api/v1/devices. Instead it keeps the phone's FCM push token in the cookie
 * `km_app_push` (not encrypted: the app writes it, see bootstrap/app.php). On a page opened
 * by a signed-in member, this links that token to the member (DeviceRegistry::register).
 * Remembered in the session, so it is one small query per sign-in, not per page.
 * Signing out unlinks it again (App\Listeners\UnlinkAppDeviceOnLogout).
 *
 * Never during a staff "Login as member" visit: staff must not get a member's pushes.
 */
class RegisterAppDevice
{
    public const COOKIE = 'km_app_push';

    private const SESSION_KEY = 'app_push_token';

    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->cookies->get(self::COOKIE, '');

        if ($token !== ''
            && $request->isMethod('GET')
            && strlen($token) <= 255
            && preg_match('/^[A-Za-z0-9:_\-]+$/', $token)
            && ($user = $request->user())
            && $request->hasSession()
            && $request->session()->get(self::SESSION_KEY) !== $token
            && ! LoginAs::pending()) {
            $ua = (string) $request->userAgent();
            DeviceRegistry::register($user, [
                'fcm_token' => $token,
                'platform' => 'android',
                'app_version' => preg_match('#KudlaMatrimonyApp/([0-9.]{1,20})#', $ua, $m) ? $m[1] : null,
                'os_version' => preg_match('#Android ([0-9.]{1,12})#', $ua, $m) ? 'Android '.$m[1] : null,
            ]);
            $request->session()->put(self::SESSION_KEY, $token);
        }

        return $next($request);
    }
}
