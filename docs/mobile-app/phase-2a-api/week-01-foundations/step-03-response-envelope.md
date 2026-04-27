# Step 3 — Response Envelope Helper (`ApiResponse`)

## Goal
Create one helper class every controller uses to produce JSON responses. Enforces the locked envelope shape. If any controller skips this helper and hand-builds JSON, it'll look wrong and fail a contract test we write later.

## Prerequisites
- [ ] [step-02 — API routes skeleton](step-02-api-routes-skeleton.md) complete

## Procedure

### 1. Create `App\Http\Responses\ApiResponse`

Create file: `app/Http/Responses/ApiResponse.php`

```php
<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

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
     * Error response.
     *
     * @param  array<string,array<int,string>>|null  $fields
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
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        string $resourceClass,
        array $extraMeta = [],
    ): JsonResponse {
        $items = $paginator->getCollection()->map(fn ($item) => new $resourceClass($item))->values();

        $meta = array_merge([
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ], $extraMeta);

        return self::ok($items, $meta);
    }

    /**
     * Unwrap JsonResource/ResourceCollection so envelope is consistent.
     * Without this, returning a Resource would double-wrap into {data: {data: ...}}.
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

    /**
     * 201 Created with an envelope.
     */
    public static function created(mixed $data = null, ?array $meta = null): JsonResponse
    {
        return self::ok($data, $meta, 201);
    }

    /**
     * 204 No Content — unusual in our API (we prefer {success: true, data: {...}}),
     * but useful for DELETE endpoints where client doesn't need data back.
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
```

### 2. Use it in the health endpoint

Update `routes/api.php`:

```php
use App\Http\Responses\ApiResponse;

Route::get('/health', fn () => ApiResponse::ok(['status' => 'ok', 'version' => 'v1']));
```

Do the same for `/auth/ping`:

```php
Route::get('/auth/ping', fn (\Illuminate\Http\Request $r) => ApiResponse::ok([
    'user_id' => $r->user()->id,
    'message' => 'authenticated',
]));
```

### 3. Write a Pest contract test

Create `tests/Feature/Api/V1/EnvelopeShapeTest.php`:

```php
<?php

use App\Models\User;
use function Pest\Laravel\getJson;

it('wraps success responses in envelope', function () {
    $response = getJson('/api/v1/health');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => ['status', 'version'],
        ])
        ->assertJson(['success' => true]);
});

it('authenticated health returns envelope', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = getJson('/api/v1/auth/ping', [
        'Authorization' => "Bearer $token",
    ]);

    $response->assertOk()
        ->assertJsonStructure(['success', 'data' => ['user_id', 'message']])
        ->assertJson(['success' => true]);
});

it('unauthenticated protected endpoint returns 401', function () {
    $response = getJson('/api/v1/auth/ping');

    $response->assertUnauthorized();
    // Note: until step 4 (exception handler) lands, this returns default Laravel 401
    // which is {message: "Unauthenticated."} — we'll make this envelope-shaped in step 4
});
```

### 4. Run tests

```bash
./vendor/bin/pest --filter=EnvelopeShape
```

First two tests should pass. Third may look different until step 4.

## Verification

- [ ] `curl -H "Accept: application/json" http://localhost:8000/api/v1/health` returns envelope-shaped JSON
- [ ] Pest tests pass (at least the two that should)
- [ ] `app/Http/Responses/ApiResponse.php` exists
- [ ] No controller hand-builds `response()->json(['success' => ...])` anywhere except this helper

## Common issues

| Issue | Fix |
|-------|-----|
| `Class App\Http\Responses\ApiResponse not found` | Composer autoload — run `composer dump-autoload` |
| Resource returned as `{data: {data: {...}}}` (double-wrapped) | Confirm `unwrap()` is called; make sure controller passes a `JsonResource` instance not a pre-resolved array |
| Pest not installed | Install — see step 8 preview: `composer require pestphp/pest pestphp/pest-plugin-laravel --dev && php artisan pest:install` |

## Commit

```bash
git add app/Http/Responses/ApiResponse.php routes/api.php tests/Feature/Api/V1/EnvelopeShapeTest.php
git commit -m "phase-2a wk-01: step-03 response envelope helper + contract test"
```

## Next step
→ [step-04-api-exception-handler.md](step-04-api-exception-handler.md)
