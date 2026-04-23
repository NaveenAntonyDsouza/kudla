<?php

use function Pest\Laravel\getJson;

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
| Design reference: docs/mobile-app/design/01-api-foundations.md §1.4
|
| Note on DB-dependent tests: the authenticated-token scenario is covered
| by tinker-based manual verification in step-01 and curl verification
| in step-02. A full auth integration test lands in Phase 2a Week 2 once
| we set up a test MySQL DB (SQLite :memory: can't run MySQL-specific
| migrations like FULLTEXT indexes that the app uses).
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

it('returns 401 when accessing protected endpoint without a token', function () {
    // Step 2 state: Laravel's default {"message":"Unauthenticated."} shape.
    // Step 4 (ApiExceptionHandler) converts this into envelope shape; this
    // test will be expanded then to assert error.code === 'UNAUTHENTICATED'.
    $response = getJson('/api/v1/auth/ping');

    $response->assertStatus(401);
});
