<?php

use App\Exceptions\ApiExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust Cloudflare as a reverse proxy (kudlamatrimony.com is proxied
        // through Cloudflare). Trusting ONLY Cloudflare's published IP ranges
        // means Laravel reads the real visitor IP from the X-Forwarded-* headers
        // Cloudflare sets — so OTP/login rate-limiting (throttle keys by IP),
        // last-login IP, and registration analytics reflect the real client,
        // not a shared Cloudflare edge IP. Trusting only CF ranges (not '*')
        // prevents anyone hitting the raw origin IP from spoofing X-Forwarded-For.
        // Source: https://www.cloudflare.com/ips/ — updated rarely; re-check if CF changes ranges.
        $middleware->trustProxies(at: [
            // IPv4
            '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
            '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
            '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
            '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
            // IPv6
            '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
            '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
        ], headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO);

        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/dashboard');

        $middleware->alias([
            'profile.complete' => \App\Http\Middleware\EnsureProfileComplete::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);

        // Affiliate tracking — captures ?ref=CODE on every public web request
        $middleware->web(append: [
            \App\Http\Middleware\CaptureAffiliateRef::class,
        ]);

        // Force JSON responses on /api/* — guarantees JSON even if client
        // forgot the Accept header. Prepend ensures it runs before exception
        // handling so errors also come through JSON.
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Map all /api/* exceptions to envelope-shaped JSON with stable error codes.
        // Web routes fall through to Laravel's default rendering.
        // See: docs/mobile-app/design/01-api-foundations.md §1.4
        $exceptions->render(function (Throwable $e, Request $request) {
            return ApiExceptionHandler::render($e, $request);
        });
    })->create();
