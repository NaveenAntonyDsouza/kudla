<?php

use App\Http\Resources\V1\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

/*
|--------------------------------------------------------------------------
| API v1 Envelope Shape Contract
|--------------------------------------------------------------------------
|
| Pins the JSON envelope shape that every /api/v1/* endpoint must produce.
| If any endpoint drifts from this contract, these tests fail loudly.
|
| Success envelope:  { "success": true,  "data": ..., "meta"?: {...} }
| Error envelope:    { "success": false, "error": { "code": "...", "message": "...", "fields"?: {...} } }
|
| Design reference:  docs/mobile-app/design/01-api-foundations.md §1.4
| Error codes:       docs/mobile-app/reference/error-codes.md
*/

it('wraps success responses in the canonical envelope shape', function () {
    $response = getJson('/api/v1/health');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => ['status', 'version'],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'status' => 'ok',
                'version' => 'v1',
            ],
        ]);
});

it('returns envelope-shaped 401 when accessing protected endpoint without a token', function () {
    $response = getJson('/api/v1/auth/ping');

    $response->assertStatus(401)
        ->assertJsonStructure([
            'success',
            'error' => ['code', 'message'],
        ])
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'UNAUTHENTICATED'],
        ]);
});

it('returns envelope-shaped 404 when accessing nonexistent endpoint', function () {
    $response = getJson('/api/v1/no-such-endpoint');

    $response->assertNotFound()
        ->assertJsonStructure([
            'success',
            'error' => ['code', 'message'],
        ])
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'NOT_FOUND'],
        ]);
});

it('returns envelope-shaped 405 on wrong HTTP method', function () {
    // /health is defined as GET only — hitting it with POST should return 405
    $response = postJson('/api/v1/health');

    $response->assertStatus(405)
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'METHOD_NOT_ALLOWED'],
        ]);
});

it('leaves web route exceptions alone (does not hijack non-api)', function () {
    // Hit a nonexistent web path — should return Laravel's default 404 behaviour,
    // NOT the API envelope.
    $response = $this->get('/no-such-web-page');

    $response->assertNotFound();

    // Response should NOT contain our envelope shape
    expect($response->headers->get('Content-Type'))->not->toBe('application/json');
});

it('returns JSON even when client omits Accept header', function () {
    // ForceJsonResponse middleware prepends Accept: application/json.
    // Send request without Accept header and confirm we still get JSON back.
    $response = $this->get('/api/v1/health');  // plain get(), not getJson()

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/json');
    $response->assertJson(['success' => true]);
});

/*
|--------------------------------------------------------------------------
| Helper-level shape pins
|--------------------------------------------------------------------------
| Asserts ApiResponse::ok / ::error / ::paginated emit exactly the keys
| documented in the class docblock. Drift here breaks every consumer that
| pins on these envelope keys (Flutter app, Bruno collection, OpenAPI
| spec). Faster + more robust than relying on a single endpoint.
*/

it('ApiResponse::ok emits {success, data} with no meta key when meta is null', function () {
    $response = ApiResponse::ok(['hello' => 'world']);
    $body = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($body)->toMatchArray([
        'success' => true,
        'data' => ['hello' => 'world'],
    ]);
    expect($body)->not->toHaveKey('meta');
    expect($body)->not->toHaveKey('error');
});

it('ApiResponse::ok adds the meta block verbatim when supplied', function () {
    $response = ApiResponse::ok(
        data: ['matri_id' => 'AM100001'],
        meta: ['cached' => true, 'cached_at' => '2026-04-26T12:00:00Z'],
    );
    $body = $response->getData(true);

    expect($body)->toHaveKeys(['success', 'data', 'meta']);
    expect($body['meta'])->toBe([
        'cached' => true,
        'cached_at' => '2026-04-26T12:00:00Z',
    ]);
});

it('ApiResponse::error emits {success: false, error: {code, message}} without fields by default', function () {
    $response = ApiResponse::error('PREMIUM_REQUIRED', 'Upgrade to chat.', status: 403);
    $body = $response->getData(true);

    expect($response->getStatusCode())->toBe(403);
    expect($body)->toMatchArray([
        'success' => false,
        'error' => [
            'code' => 'PREMIUM_REQUIRED',
            'message' => 'Upgrade to chat.',
        ],
    ]);
    expect($body['error'])->not->toHaveKey('fields');
    expect($body)->not->toHaveKey('data');
});

it('ApiResponse::error adds error.fields block when validation errors are supplied', function () {
    $response = ApiResponse::error(
        code: 'VALIDATION_FAILED',
        message: 'Please check the fields below.',
        fields: [
            'email' => ['The email field is required.'],
            'password' => ['The password must be at least 8 characters.', 'The password must contain a number.'],
        ],
        status: 422,
    );
    $body = $response->getData(true);

    expect($response->getStatusCode())->toBe(422);
    expect($body['error'])->toHaveKey('fields');
    expect($body['error']['fields'])->toBeArray();
    expect($body['error']['fields']['email'])->toBeArray()->toHaveCount(1);
    expect($body['error']['fields']['password'])->toBeArray()->toHaveCount(2);
    // Each field maps to a list of *strings* — not nested objects.
    expect($body['error']['fields']['email'][0])->toBeString();
});

it('ApiResponse::paginated emits canonical {page, per_page, total, last_page} meta keys', function () {
    $paginator = new LengthAwarePaginator(
        items: new Collection([]),
        total: 0,
        perPage: 15,
        currentPage: 1,
    );

    $response = ApiResponse::paginated($paginator, UserResource::class);
    $body = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($body)->toHaveKeys(['success', 'data', 'meta']);
    expect($body['success'])->toBeTrue();
    expect($body['data'])->toBeArray()->toBeEmpty();

    // The 4 keys every paginated list endpoint must expose.
    expect($body['meta'])->toHaveKeys(['page', 'per_page', 'total', 'last_page']);
    expect($body['meta']['page'])->toBe(1);
    expect($body['meta']['per_page'])->toBe(15);
    expect($body['meta']['total'])->toBe(0);
    expect($body['meta']['last_page'])->toBe(1);
});

it('ApiResponse::paginated merges extraMeta alongside the canonical keys', function () {
    $paginator = new LengthAwarePaginator(
        items: new Collection([]),
        total: 0,
        perPage: 10,
        currentPage: 1,
    );

    $response = ApiResponse::paginated(
        $paginator,
        UserResource::class,
        extraMeta: ['tab' => 'all', 'unread_count' => 7],
    );
    $body = $response->getData(true);

    // Canonical keys still present.
    expect($body['meta'])->toHaveKeys(['page', 'per_page', 'total', 'last_page']);
    // Extra keys merged in (NOT nested under a sub-key — flat into meta).
    expect($body['meta']['tab'])->toBe('all');
    expect($body['meta']['unread_count'])->toBe(7);
});

it('returns envelope-shaped 422 with VALIDATION_FAILED and a fields block on real validation failure', function () {
    // POST /api/v1/contact requires name/email/subject/message. Sending
    // an empty body must produce the canonical validation envelope —
    // proves ValidationException → ApiExceptionHandler → ApiResponse
    // wiring is intact end-to-end.
    $response = postJson('/api/v1/contact', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'error' => ['code', 'message', 'fields'],
        ])
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'VALIDATION_FAILED'],
        ]);

    $body = $response->json();
    expect($body['error']['fields'])->toBeArray();
    // Each missing required field surfaces with at least one message string.
    foreach (['name', 'email', 'subject', 'message'] as $field) {
        expect($body['error']['fields'])->toHaveKey($field);
        expect($body['error']['fields'][$field])->toBeArray();
        expect($body['error']['fields'][$field][0])->toBeString();
    }
});
