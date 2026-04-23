<?php

use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\getJson;

/*
|--------------------------------------------------------------------------
| GET /api/v1/reference and /api/v1/reference/{list}
|--------------------------------------------------------------------------
|
| Reference dropdown data — castes, occupations, countries, dioceses,
| languages, hobbies, etc. Flutter populates all dropdowns from this.
|
| Design reference: docs/mobile-app/design/09-engagement-api.md §9.9
|
| The production code path: Controller -> ReferenceDataService::get() ->
| checks site_settings table -> falls back to config/reference_data.php.
|
| These tests pre-seed the controller's cache key to bypass the DB check
| entirely. A full integration test against live data lands in Week 2
| when the MySQL test DB is configured.
*/

beforeEach(function () {
    Cache::flush();
});

it('lists all available reference list slugs', function () {
    $response = getJson('/api/v1/reference');

    $response->assertOk()
        ->assertJsonStructure(['success', 'data' => ['lists']]);

    $lists = $response->json('data.lists');

    expect($lists)->toBeArray()->not->toBeEmpty();
    expect($lists)->toContain('castes', 'denominations', 'countries', 'occupations', 'languages');
});

it('returns flat list for a simple reference key', function () {
    // Seed controller-level cache to bypass ReferenceDataService (which queries DB)
    Cache::put('api:v1:reference:castes:raw', ['Brahmin', 'Nair', 'Ezhava'], now()->addMinutes(10));

    $response = getJson('/api/v1/reference/castes');

    $response->assertOk()->assertJsonStructure(['success', 'data']);

    $data = $response->json('data');
    expect($data)->toBeArray()->not->toBeEmpty();
    expect($data[0])->toBeString();
});

it('returns grouped object for a grouped reference key', function () {
    Cache::put('api:v1:reference:denominations:raw', [
        'Catholic' => ['Syrian Catholic', 'Roman Catholic'],
        'Non-Catholic' => ['Protestant', 'Orthodox'],
    ], now()->addMinutes(10));

    $response = getJson('/api/v1/reference/denominations');

    $response->assertOk();
    $data = $response->json('data');

    expect($data)->toBeArray();
    expect(array_keys($data))->toContain('Catholic');
});

it('flattens grouped list when ?flat=1 is passed', function () {
    Cache::put('api:v1:reference:denominations:flat', [
        'Syrian Catholic', 'Roman Catholic', 'Protestant', 'Orthodox',
    ], now()->addMinutes(10));

    $response = getJson('/api/v1/reference/denominations?flat=1');

    $response->assertOk();
    $data = $response->json('data');

    expect($data)->toBeArray()->not->toBeEmpty();
    expect($data[0])->toBeString();
});

it('returns key-value options when ?options=1 is passed', function () {
    Cache::put('api:v1:reference:castes:options', [
        'Brahmin' => 'Brahmin',
        'Nair' => 'Nair',
    ], now()->addMinutes(10));

    $response = getJson('/api/v1/reference/castes?options=1');

    $response->assertOk();
    $data = $response->json('data');

    expect($data)->toBeArray()->not->toBeEmpty();
    $firstKey = array_key_first($data);
    expect($firstKey)->toBe($data[$firstKey]);
});

it('returns envelope 404 for unknown list slugs', function () {
    $response = getJson('/api/v1/reference/not-a-real-list');

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'NOT_FOUND'],
        ]);
});

it('prevents route leaking unknown lists (enumeration protection)', function () {
    // Controller's VALID_LISTS allow-list protects against enumerating
    // arbitrary site_settings.ref_data_* keys via the URL path.
    $response = getJson('/api/v1/reference/users');

    $response->assertNotFound();
});
