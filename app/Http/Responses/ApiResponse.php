<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Canonical API response envelope helper.
 *
 * Every controller under App\Http\Controllers\Api\V1\* should return JSON
 * through this class. The envelope shape is:
 *
 *   success:  { "success": true, "data": ..., "meta"?: {...} }
 *   error:    { "success": false, "error": { "code": "...", "message": "...", "fields"?: {...} } }
 *
 * The shape is pinned by tests/Feature/Api/V1/EnvelopeShapeTest.php — do not
 * drift.
 *
 * Design reference: docs/mobile-app/design/01-api-foundations.md §1.4
 */
class ApiResponse
{
    /**
     * Success response.
     *
     * @param  mixed  $data
     * @param  array<string,mixed>|null  $meta
     */
    public static function ok(mixed $data = null, ?array $meta = null, int $status = 200): JsonResponse
    {
        $payload = ['success' => true, 'data' => self::unwrap($data)];

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * 201 Created success.
     *
     * @param  mixed  $data
     * @param  array<string,mixed>|null  $meta
     */
    public static function created(mixed $data = null, ?array $meta = null): JsonResponse
    {
        return self::ok($data, $meta, 201);
    }

    /**
     * 204 No Content — used sparingly. We generally prefer {success: true, data: ...}
     * even for delete operations.
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Error response.
     *
     * @param  string                               $code    stable string code (see error-codes.md)
     * @param  string                               $message user-safe short message
     * @param  array<string,array<int,string>>|null $fields  validation field errors
     * @param  int                                  $status  HTTP status
     */
    public static function error(
        string $code,
        string $message,
        ?array $fields = null,
        int $status = 400,
    ): JsonResponse {
        $error = ['code' => $code, 'message' => $message];

        if ($fields !== null) {
            $error['fields'] = $fields;
        }

        return response()->json(
            ['success' => false, 'error' => $error],
            $status,
        );
    }

    /**
     * Paginated list response — applies a Resource class over each item,
     * emits standard meta block.
     *
     * @param  class-string<JsonResource>       $resourceClass
     * @param  array<string,mixed>              $extraMeta
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        string $resourceClass,
        array $extraMeta = [],
    ): JsonResponse {
        $items = $paginator->getCollection()
            ->map(fn ($item) => (new $resourceClass($item))->resolve())
            ->values();

        $meta = array_merge([
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ], $extraMeta);

        return self::ok($items, $meta);
    }

    /**
     * Unwrap JsonResource / ResourceCollection so envelope is consistent.
     *
     * Without this, returning a Resource would double-wrap into {data: {data: ...}}
     * because JsonResource auto-wraps itself.
     */
    private static function unwrap(mixed $data): mixed
    {
        if ($data instanceof ResourceCollection) {
            return $data->resolve();
        }

        if ($data instanceof JsonResource) {
            return $data->resolve();
        }

        return $data;
    }
}
