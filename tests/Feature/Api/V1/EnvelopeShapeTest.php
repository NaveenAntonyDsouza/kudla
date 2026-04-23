<?php

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
