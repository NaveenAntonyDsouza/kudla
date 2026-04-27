<?php

use App\Http\Controllers\Api\V1\SearchController;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/*
|--------------------------------------------------------------------------
| SearchController::partner — GET /api/v1/search/partner
|--------------------------------------------------------------------------
| Exercises controller-layer wiring: auth guard, per_page clamp, envelope
| shape, applied_filters echo. The underlying query (buildSearchQuery +
| applySortOrder) uses MySQL-specific SQL (TIMESTAMPDIFF, CAST AS
| UNSIGNED) that SQLite :memory: can't run — the executeQuery seam is
| overridden in a test subclass to return pre-built paginators, so
| these tests stay DB-free. Real-query behaviour is verified by Bruno
| smoke in step-16 against a migrated MySQL instance.
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

/** Build a search-user + profile (or no profile). */
function buildSearchUser(bool $withProfile = true): User
{
    $user = new User();
    $user->exists = true;
    $user->forceFill([
        'id' => 8800,
        'email' => 'search@example.com',
        'phone' => '9876500000',
        'is_active' => true,
    ]);
    $user->setRelation('userMemberships', new \Illuminate\Database\Eloquent\Collection());

    if ($withProfile) {
        $profile = new Profile();
        $profile->exists = true;
        $profile->forceFill([
            'id' => 8800,
            'user_id' => 8800,
            'matri_id' => 'AM880000',
            'full_name' => 'Search Me',
            'gender' => 'male',
            'date_of_birth' => Carbon::parse('1993-05-15'),
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
 * Build a SearchController with executeQuery overridden to return a
 * pre-built LengthAwarePaginator. Captures the args the controller
 * passed so tests can assert on per_page clamping.
 */
function buildSearchController(int $totalItems = 0, array $items = []): SearchController
{
    return new class($totalItems, $items) extends SearchController {
        public static int $capturedPerPage = 0;

        public function __construct(
            private int $totalForStub,
            private array $itemsForStub,
        ) {}

        protected function executeQuery(Request $request, Profile $viewer, int $perPage): LengthAwarePaginator
        {
            self::$capturedPerPage = $perPage;

            $page = max(1, (int) $request->query('page', 1));

            return new LengthAwarePaginator(
                items: $this->itemsForStub,
                total: $this->totalForStub,
                perPage: $perPage,
                currentPage: $page,
                options: ['path' => $request->url()],
            );
        }
    };
}

function searchRequest(User $user, array $query = []): Request
{
    $request = Request::create(
        '/api/v1/search/partner?'.http_build_query($query),
        'GET',
    );
    $request->setUserResolver(fn () => $user);

    return $request;
}

/* ==================================================================
 |  Guard paths
 | ================================================================== */

it('returns 422 PROFILE_REQUIRED when user has no profile', function () {
    $user = buildSearchUser(withProfile: false);
    $controller = buildSearchController();

    $response = $controller->partner(searchRequest($user));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

/* ==================================================================
 |  Envelope shape + pagination meta
 | ================================================================== */

it('returns envelope with data array + meta block on happy path', function () {
    $user = buildSearchUser();
    $controller = buildSearchController(totalItems: 0);

    $response = $controller->partner(searchRequest($user));
    $body = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($body['success'])->toBeTrue();
    expect($body)->toHaveKeys(['data', 'meta']);
    expect($body['data'])->toBeArray();
    expect($body['meta'])->toHaveKeys([
        'page', 'per_page', 'total', 'last_page',
    ]);
});

it('meta block includes applied_filters echo for non-empty filters', function () {
    $user = buildSearchUser();
    $controller = buildSearchController();

    $response = $controller->partner(searchRequest($user, [
        'age_from' => 25,
        'age_to' => 32,
        'religion' => ['Hindu'],
        'sort' => 'newest',
    ]));
    $meta = $response->getData(true)['meta'];

    expect($meta)->toHaveKey('applied_filters');
    expect($meta['applied_filters'])->toHaveKey('age_from');
    expect($meta['applied_filters']['age_from'])->toBe('25');
    expect($meta['applied_filters']['religion'])->toBe(['Hindu']);
    expect($meta['applied_filters']['sort'])->toBe('newest');
});

it('applied_filters strips empty / null / unset values', function () {
    $user = buildSearchUser();
    $controller = buildSearchController();

    $response = $controller->partner(searchRequest($user, [
        'age_from' => 25,
        'age_to' => '',           // stripped (empty string)
        'religion' => [],          // stripped (empty array)
    ]));
    $applied = $response->getData(true)['meta']['applied_filters'];

    expect($applied)->toHaveKey('age_from');
    expect($applied)->not->toHaveKey('age_to');
    expect($applied)->not->toHaveKey('religion');
});

/* ==================================================================
 |  per_page clamping
 | ================================================================== */

it('uses default per_page when not provided', function () {
    $user = buildSearchUser();
    $controller = buildSearchController();

    $controller->partner(searchRequest($user));

    expect($controller::$capturedPerPage)->toBe(SearchController::DEFAULT_PER_PAGE);
});

it('clamps per_page down to MAX_PER_PAGE when caller requests more', function () {
    $user = buildSearchUser();
    $controller = buildSearchController();

    $controller->partner(searchRequest($user, ['per_page' => 500]));

    expect($controller::$capturedPerPage)->toBe(SearchController::MAX_PER_PAGE);
});

it('clamps per_page up to 1 when caller requests zero or negative', function () {
    $user = buildSearchUser();
    $controller = buildSearchController();

    $controller->partner(searchRequest($user, ['per_page' => 0]));
    expect($controller::$capturedPerPage)->toBe(1);

    // Reset and try negative.
    buildSearchController::class;  // class exists
    $controller2 = buildSearchController();
    $controller2->partner(searchRequest($user, ['per_page' => -10]));
    expect($controller2::$capturedPerPage)->toBe(1);
});

it('respects explicit per_page within bounds', function () {
    $user = buildSearchUser();
    $controller = buildSearchController();

    $controller->partner(searchRequest($user, ['per_page' => 15]));

    expect($controller::$capturedPerPage)->toBe(15);
});

/* ==================================================================
 |  Constants (locks the Flutter contract)
 | ================================================================== */

it('exposes DEFAULT_PER_PAGE=20 and MAX_PER_PAGE=50', function () {
    expect(SearchController::DEFAULT_PER_PAGE)->toBe(20);
    expect(SearchController::MAX_PER_PAGE)->toBe(50);
});
