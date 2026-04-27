# Step 4 — API Exception Handler

## Goal
Map every possible exception (validation errors, auth failures, not found, throttled, uncaught errors) into our envelope error shape with a stable string code. Without this, Laravel returns raw HTML stack traces or default JSON that doesn't match our contract.

## Prerequisites
- [ ] [step-03 — Response envelope](step-03-response-envelope.md) complete
- [ ] `App\Http\Responses\ApiResponse` exists

## Procedure

### 1. Create `App\Exceptions\ApiExceptionHandler`

Create file: `app/Exceptions/ApiExceptionHandler.php`

```php
<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class ApiExceptionHandler
{
    /**
     * Map any throwable to an envelope-shaped JSON response.
     * Called from bootstrap/app.php exception handler.
     */
    public static function render(Throwable $e, Request $request): ?JsonResponse
    {
        // Only intercept /api/* requests — web handles its own errors
        if (! $request->is('api/*')) {
            return null;
        }

        return match (true) {
            $e instanceof ValidationException => self::validation($e),
            $e instanceof AuthenticationException => self::unauthenticated($e),
            $e instanceof AuthorizationException => self::unauthorized($e),
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => self::notFound($e),
            $e instanceof MethodNotAllowedHttpException => self::methodNotAllowed($e),
            $e instanceof ThrottleRequestsException => self::throttled($e),
            default => self::fallback($e),
        };
    }

    private static function validation(ValidationException $e): JsonResponse
    {
        return ApiResponse::error(
            code: 'VALIDATION_FAILED',
            message: 'Please check the fields below.',
            fields: $e->errors(),
            status: 422,
        );
    }

    private static function unauthenticated(AuthenticationException $e): JsonResponse
    {
        return ApiResponse::error(
            code: 'UNAUTHENTICATED',
            message: 'You must log in to access this resource.',
            status: 401,
        );
    }

    private static function unauthorized(AuthorizationException $e): JsonResponse
    {
        return ApiResponse::error(
            code: 'UNAUTHORIZED',
            message: $e->getMessage() ?: 'You do not have permission to perform this action.',
            status: 403,
        );
    }

    private static function notFound(Throwable $e): JsonResponse
    {
        return ApiResponse::error(
            code: 'NOT_FOUND',
            message: 'The requested resource was not found.',
            status: 404,
        );
    }

    private static function methodNotAllowed(MethodNotAllowedHttpException $e): JsonResponse
    {
        return ApiResponse::error(
            code: 'METHOD_NOT_ALLOWED',
            message: 'This HTTP method is not allowed on this endpoint.',
            status: 405,
        );
    }

    private static function throttled(ThrottleRequestsException $e): JsonResponse
    {
        $retryAfter = (int) ($e->getHeaders()['Retry-After'] ?? 60);
        return ApiResponse::error(
            code: 'THROTTLED',
            message: "Too many requests. Try again in {$retryAfter} seconds.",
            status: 429,
        );
    }

    private static function fallback(Throwable $e): JsonResponse
    {
        // In production, never leak stack traces
        $message = app()->environment('local', 'staging')
            ? $e->getMessage()
            : 'An unexpected error occurred.';

        $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

        // Report to Laravel's error log / Sentry / whatever
        report($e);

        return ApiResponse::error(
            code: 'SERVER_ERROR',
            message: $message,
            status: $status,
        );
    }
}
```

### 2. Register in `bootstrap/app.php`

Open `bootstrap/app.php`. Find the `->withExceptions(...)` section (it should exist from Laravel 11+ install). Add:

```php
use App\Exceptions\ApiExceptionHandler;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Map API exceptions to envelope shape
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            return ApiExceptionHandler::render($e, $request);
        });
    })
    ->create();
```

### 3. Clear config + route caches

```bash
php artisan config:clear
php artisan route:clear
```

### 4. Test with curl

```bash
# Validation error — send empty body to an endpoint needing fields (we'll test with the health endpoint for now by faking a required header — we test real validation in step 6)

# 404 test
curl -i -H "Accept: application/json" http://localhost:8000/api/v1/nonexistent

# Expected:
# HTTP/1.1 404 Not Found
# Content-Type: application/json
# {"success":false,"error":{"code":"NOT_FOUND","message":"The requested resource was not found."}}

# 401 test (no token on protected route)
curl -i -H "Accept: application/json" http://localhost:8000/api/v1/auth/ping

# Expected:
# HTTP/1.1 401 Unauthorized
# {"success":false,"error":{"code":"UNAUTHENTICATED","message":"You must log in to access this resource."}}
```

### 5. Update the contract test

Open `tests/Feature/Api/V1/EnvelopeShapeTest.php` and replace the third test:

```php
it('unauthenticated protected endpoint returns envelope-shaped 401', function () {
    $response = getJson('/api/v1/auth/ping');

    $response->assertStatus(401)
        ->assertJsonStructure(['success', 'error' => ['code', 'message']])
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'UNAUTHENTICATED'],
        ]);
});

it('nonexistent endpoint returns envelope-shaped 404', function () {
    $response = getJson('/api/v1/no-such-thing');

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'NOT_FOUND'],
        ]);
});
```

### 6. Run tests

```bash
./vendor/bin/pest --filter=EnvelopeShape
```

All four should pass.

## Verification

- [ ] All 4 Pest tests pass
- [ ] `curl` to nonexistent endpoint returns envelope-shaped 404
- [ ] `curl` to protected endpoint without token returns envelope-shaped 401
- [ ] In local env, triggering an uncaught exception returns envelope-shaped 500 with the error message
- [ ] Verify in prod env (via `APP_ENV=production php artisan serve` if you want to test locally), that exception messages don't leak

## Common issues

| Issue | Fix |
|-------|-----|
| Still returning HTML stack trace | `bootstrap/app.php` not registered correctly; confirm `->withExceptions(...)` block and run `php artisan config:clear` |
| 401 returns `{"message": "Unauthenticated."}` instead of envelope | `ApiExceptionHandler::render()` returning `null` — check `$request->is('api/*')` — ensure URL includes `/api/v1/` |
| Validation errors still 422 but without `fields` key | `ValidationException::errors()` returning array; make sure you pass it into `ApiResponse::error(..., fields: ...)` |
| Contract test failing on third assertion | Check that all 4 handlers in the `match` statement are reached |

## Commit

```bash
git add app/Exceptions/ApiExceptionHandler.php bootstrap/app.php tests/Feature/Api/V1/EnvelopeShapeTest.php
git commit -m "phase-2a wk-01: step-04 API exception handler with stable error codes"
```

## Next step
→ [step-05-force-json-middleware.md](step-05-force-json-middleware.md)
