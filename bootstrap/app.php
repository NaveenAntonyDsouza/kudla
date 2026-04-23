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
