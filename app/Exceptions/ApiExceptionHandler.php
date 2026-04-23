<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Maps any Throwable to our API envelope shape with a stable error code.
 *
 * Registered in bootstrap/app.php via ->withExceptions(). Only intercepts
 * /api/* requests — web routes use Laravel's default exception rendering.
 *
 * Error code catalogue: docs/mobile-app/reference/error-codes.md
 * Design reference:    docs/mobile-app/design/01-api-foundations.md §1.4
 */
class ApiExceptionHandler
{
    /**
     * Convert any throwable to an envelope-shaped JSON response, or return
     * null to let Laravel's default renderer handle it (for web routes).
     */
    public static function render(Throwable $e, Request $request): ?JsonResponse
    {
        // Only intercept /api/* requests. Web routes keep their default rendering.
        if (! $request->is('api/*')) {
            return null;
        }

        return match (true) {
            $e instanceof ValidationException => self::validation($e),
            $e instanceof AuthenticationException => self::unauthenticated(),
            $e instanceof AuthorizationException => self::unauthorized($e),
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => self::notFound(),
            $e instanceof MethodNotAllowedHttpException => self::methodNotAllowed(),
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

    private static function unauthenticated(): JsonResponse
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

    private static function notFound(): JsonResponse
    {
        return ApiResponse::error(
            code: 'NOT_FOUND',
            message: 'The requested resource was not found.',
            status: 404,
        );
    }

    private static function methodNotAllowed(): JsonResponse
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
        // In non-prod envs, surface the real message to speed up debugging.
        // In production, never leak implementation details.
        $message = app()->environment('local', 'staging', 'testing')
            ? $e->getMessage()
            : 'An unexpected error occurred.';

        $status = $e instanceof HttpExceptionInterface
            ? $e->getStatusCode()
            : 500;

        // Always send to error reporter (Laravel log / Sentry / Crashlytics back end)
        report($e);

        return ApiResponse::error(
            code: 'SERVER_ERROR',
            message: $message,
            status: $status,
        );
    }
}
