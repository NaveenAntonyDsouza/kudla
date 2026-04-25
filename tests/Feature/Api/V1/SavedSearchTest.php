<?php

use App\Http\Controllers\Api\V1\SearchController;
use App\Models\Profile;
use App\Models\SavedSearch;
use App\Models\User;
use App\Services\ProfileAccessService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| SearchController saved-search CRUD
|--------------------------------------------------------------------------
| GET /search/saved · POST /search/saved · DELETE /search/saved/{id}
|
| Inline-table approach (step-8/9 pattern) so updateOrCreate / delete /
| count actually persist. The saved_searches table has no complex column
| dependencies — only profile_id (FK omitted in tests), search_name,
| criteria (JSON), timestamps.
|
| API field naming is Flutter-facing: `name` (→ search_name) and
| `filters` (→ criteria). The controller does the translation; tests
| verify both sides.
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

function createSavedSearchesTable(): void
{
    if (Schema::hasTable('saved_searches')) {
        return;
    }

    Schema::create('saved_searches', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('search_name', 100);
        $t->json('criteria')->nullable();
        $t->timestamps();
        $t->index('profile_id');
    });
}

/** Build a User with an attached Profile (all set via setRelation). */
function buildSavedSearchUser(int $id = 6600, bool $withProfile = true): User
{
    $user = new User();
    $user->exists = true;
    $user->forceFill([
        'id' => $id,
        'email' => "saved{$id}@example.com",
        'phone' => '9800001234',
        'is_active' => true,
    ]);
    $user->setRelation('userMemberships', new \Illuminate\Database\Eloquent\Collection());

    if ($withProfile) {
        $profile = new Profile();
        $profile->exists = true;
        $profile->forceFill([
            'id' => $id,
            'user_id' => $id,
            'matri_id' => 'AM'.str_pad((string) $id, 6, '0', STR_PAD_LEFT),
            'gender' => 'male',
            'is_active' => true,
            'is_approved' => true,
        ]);
        $profile->setRelation('user', $user);
        $user->setRelation('profile', $profile);
    } else {
        $user->setRelation('profile', null);
    }

    return $user;
}

/**
 * Plain SearchController instance — we test real DB CRUD so no seam
 * overrides are needed. ProfileAccessService is autowired by the
 * container; it isn't called on any saved-search endpoint.
 */
function savedController(): SearchController
{
    return app(SearchController::class);
}

function savedRequest(User $user, string $method = 'GET', array $body = []): Request
{
    $request = Request::create('/api/v1/search/saved', $method, $body);
    $request->setUserResolver(fn () => $user);

    return $request;
}

beforeEach(function () {
    createSavedSearchesTable();
});

afterEach(function () {
    Schema::dropIfExists('saved_searches');
});

/* ==================================================================
 |  savedList — GET /search/saved
 | ================================================================== */

it('savedList returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildSavedSearchUser(withProfile: false);

    $response = savedController()->savedList(savedRequest($user));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

it('savedList returns an empty array when user has no saved searches', function () {
    $user = buildSavedSearchUser();

    $response = savedController()->savedList(savedRequest($user));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data'])->toBe([]);
});

it('savedList returns items in descending created_at order with translated fields', function () {
    $user = buildSavedSearchUser();

    // Seed 2 saved searches — API should return them newest first.
    SavedSearch::create([
        'profile_id' => $user->profile->id,
        'search_name' => 'Older Search',
        'criteria' => ['religion' => ['Hindu']],
    ]);
    sleep(1);  // ensure distinct created_at timestamps
    SavedSearch::create([
        'profile_id' => $user->profile->id,
        'search_name' => 'Newer Search',
        'criteria' => ['religion' => ['Jain'], 'age_from' => 25],
    ]);

    $response = savedController()->savedList(savedRequest($user));
    $data = $response->getData(true)['data'];

    expect(count($data))->toBe(2);
    expect($data[0]['name'])->toBe('Newer Search');          // API field
    expect($data[0]['filters'])->toBe(['religion' => ['Jain'], 'age_from' => 25]);
    expect($data[1]['name'])->toBe('Older Search');
    expect($data[0])->toHaveKey('created_at');
});

it('savedList scopes to the authenticated user only', function () {
    $mine = buildSavedSearchUser(id: 6601);
    $other = buildSavedSearchUser(id: 6602);

    SavedSearch::create([
        'profile_id' => $mine->profile->id,
        'search_name' => 'Mine',
        'criteria' => [],
    ]);
    SavedSearch::create([
        'profile_id' => $other->profile->id,
        'search_name' => 'Not mine',
        'criteria' => [],
    ]);

    $response = savedController()->savedList(savedRequest($mine));
    $data = $response->getData(true)['data'];

    expect(count($data))->toBe(1);
    expect($data[0]['name'])->toBe('Mine');
});

/* ==================================================================
 |  saveSearch — POST /search/saved
 | ================================================================== */

it('saveSearch throws ValidationException when name is missing', function () {
    $user = buildSavedSearchUser();

    expect(fn () => savedController()->saveSearch(savedRequest($user, 'POST', [
        'filters' => ['religion' => ['Hindu']],
    ])))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('saveSearch throws ValidationException when filters is missing or not an array', function () {
    $user = buildSavedSearchUser();

    expect(fn () => savedController()->saveSearch(savedRequest($user, 'POST', [
        'name' => 'Bangalore',
    ])))->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(fn () => savedController()->saveSearch(savedRequest($user, 'POST', [
        'name' => 'Bangalore',
        'filters' => 'not an array',
    ])))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('saveSearch persists to saved_searches with API→DB column translation', function () {
    $user = buildSavedSearchUser();

    $response = savedController()->saveSearch(savedRequest($user, 'POST', [
        'name' => 'Bangalore Hindu',
        'filters' => ['religion' => ['Hindu'], 'native_country' => 'India'],
    ]));

    expect($response->getStatusCode())->toBe(201);
    $data = $response->getData(true)['data'];
    expect($data['name'])->toBe('Bangalore Hindu');
    expect($data['filters'])->toBe(['religion' => ['Hindu'], 'native_country' => 'India']);

    // DB row uses internal column names.
    $row = SavedSearch::where('profile_id', $user->profile->id)->first();
    expect($row->search_name)->toBe('Bangalore Hindu');
    expect($row->criteria)->toBe(['religion' => ['Hindu'], 'native_country' => 'India']);
});

it('saveSearch returns 422 on the 11th save (quota exceeded)', function () {
    $user = buildSavedSearchUser();

    // Seed 10 existing saved searches — MAX_SAVED_SEARCHES.
    for ($i = 1; $i <= SearchController::MAX_SAVED_SEARCHES; $i++) {
        SavedSearch::create([
            'profile_id' => $user->profile->id,
            'search_name' => "Existing {$i}",
            'criteria' => [],
        ]);
    }

    $response = savedController()->saveSearch(savedRequest($user, 'POST', [
        'name' => '11th Search',
        'filters' => ['religion' => ['Hindu']],
    ]));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('VALIDATION_FAILED');
    expect($response->getData(true)['error']['message'])
        ->toContain((string) SearchController::MAX_SAVED_SEARCHES);

    // Row count unchanged.
    expect(SavedSearch::where('profile_id', $user->profile->id)->count())
        ->toBe(SearchController::MAX_SAVED_SEARCHES);
});

/* ==================================================================
 |  deleteSaved — DELETE /search/saved/{id}
 | ================================================================== */

it('deleteSaved removes the row when caller is the owner', function () {
    $user = buildSavedSearchUser();
    $saved = SavedSearch::create([
        'profile_id' => $user->profile->id,
        'search_name' => 'To be deleted',
        'criteria' => [],
    ]);
    $savedId = $saved->id;

    $response = savedController()->deleteSaved(savedRequest($user, 'DELETE'), $saved);

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['deleted'])->toBeTrue();
    expect(SavedSearch::find($savedId))->toBeNull();
});

it('deleteSaved returns 403 when caller is not the owner', function () {
    $owner = buildSavedSearchUser(id: 6601);
    $stranger = buildSavedSearchUser(id: 6602);
    $saved = SavedSearch::create([
        'profile_id' => $owner->profile->id,
        'search_name' => 'Not yours',
        'criteria' => [],
    ]);

    $response = savedController()->deleteSaved(savedRequest($stranger, 'DELETE'), $saved);

    expect($response->getStatusCode())->toBe(403);
    expect($response->getData(true)['error']['code'])->toBe('UNAUTHORIZED');
    expect(SavedSearch::find($saved->id))->not->toBeNull();  // preserved
});

it('deleteSaved returns 422 PROFILE_REQUIRED when user has no profile', function () {
    $user = buildSavedSearchUser(withProfile: false);
    $saved = SavedSearch::create([
        'profile_id' => 9999,
        'search_name' => 'Orphan',
        'criteria' => [],
    ]);

    $response = savedController()->deleteSaved(savedRequest($user, 'DELETE'), $saved);

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

/* ==================================================================
 |  Constants (contract lock)
 | ================================================================== */

it('MAX_SAVED_SEARCHES is 10', function () {
    expect(SearchController::MAX_SAVED_SEARCHES)->toBe(10);
});
