<?php

use App\Http\Controllers\Api\V1\SearchController;
use App\Models\Profile;
use App\Models\User;
use App\Services\ProfileAccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/*
|--------------------------------------------------------------------------
| SearchController::keyword + ::byMatriId
|--------------------------------------------------------------------------
| Controller-layer tests for two lookup flavours:
|   - /search/keyword — free-text LIKE search over 7 columns
|   - /search/id/{matriId} — direct lookup, 404 on any access-gate failure
|
| Both endpoints are DB-free via seams: executeKeywordQuery returns a
| pre-built paginator; findTargetByMatriId returns a pre-built Profile.
| Real-query behaviour is verified by Bruno smoke in step-16 against
| MySQL (LIKE + whereHas joins need related tables that SQLite :memory:
| doesn't provide).
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

function buildKeywordUser(bool $withProfile = true, string $gender = 'male'): User
{
    $user = new User();
    $user->exists = true;
    $user->forceFill([
        'id' => 7700,
        'email' => 'keyword@example.com',
        'phone' => '9871112222',
        'is_active' => true,
    ]);
    $user->setRelation('userMemberships', new \Illuminate\Database\Eloquent\Collection());

    if ($withProfile) {
        $profile = new Profile();
        $profile->exists = true;
        $profile->forceFill([
            'id' => 7700,
            'user_id' => 7700,
            'matri_id' => 'AM770000',
            'gender' => $gender,
            'date_of_birth' => Carbon::parse('1993-05-15'),
            'is_active' => true,
            'is_approved' => true,
            'is_hidden' => false,
            'suspension_status' => 'active',
            'show_profile_to' => 'all',
        ]);
        $profile->setRelation('user', $user);
        $profile->setRelation('partnerPreference', null);
        $user->setRelation('profile', $profile);
    } else {
        $user->setRelation('profile', null);
    }

    return $user;
}

/** Build a target Profile for matri_id lookup tests. */
function buildKeywordTarget(string $matriId, string $gender = 'female', array $overrides = []): Profile
{
    $user = new User();
    $user->exists = true;
    $user->forceFill([
        'id' => 7800,
        'email' => 'target@example.com',
        'is_active' => true,
    ]);
    // Pre-set empty collection so ProfileCardResource's is_premium
    // lookup short-circuits without hitting user_memberships.
    $user->setRelation('userMemberships', new \Illuminate\Database\Eloquent\Collection());

    $target = new Profile();
    $target->exists = true;
    $target->forceFill(array_merge([
        'id' => 7800,
        'user_id' => 7800,
        'matri_id' => $matriId,
        'gender' => $gender,
        'full_name' => 'Target User',
        'date_of_birth' => Carbon::parse('1996-08-20'),
        'is_active' => true,
        'is_approved' => true,
        'is_hidden' => false,
        'suspension_status' => 'active',
        'show_profile_to' => 'all',
    ], $overrides));
    $target->setRelation('user', $user);
    $target->setRelation('partnerPreference', null);
    $target->setRelation('photoPrivacySetting', null);
    $target->setRelation('religiousInfo', null);
    $target->setRelation('educationDetail', null);
    $target->setRelation('locationInfo', null);
    $target->setRelation('primaryPhoto', null);  // ProfileCardResource reads this

    return $target;
}

/**
 * Build a SearchController with both seams stubbed. executeKeywordQuery
 * returns a pre-built empty paginator; findTargetByMatriId returns the
 * passed-in target matched by matri_id (case-sensitive — caller passes
 * the uppercased form we expect).
 */
function buildKeywordController(?Profile $target = null, int $totalItems = 0): SearchController
{
    return new class(app(ProfileAccessService::class), $target, $totalItems) extends SearchController {
        public function __construct(
            ProfileAccessService $access,
            private ?Profile $stubbedTarget,
            private int $totalForStub,
        ) {
            parent::__construct($access);
        }

        protected function executeKeywordQuery(
            Profile $viewer,
            string $term,
            int $perPage,
        ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
            return new LengthAwarePaginator(
                items: [],
                total: $this->totalForStub,
                perPage: $perPage,
                currentPage: 1,
                options: ['path' => '/api/v1/search/keyword'],
            );
        }

        protected function findTargetByMatriId(string $matriId): ?Profile
        {
            return $this->stubbedTarget && $this->stubbedTarget->matri_id === $matriId
                ? $this->stubbedTarget
                : null;
        }
    };
}

function keywordRequest(User $user, array $query = [], string $path = '/api/v1/search/keyword'): Request
{
    $request = Request::create($path.'?'.http_build_query($query), 'GET');
    $request->setUserResolver(fn () => $user);

    return $request;
}

/* ==================================================================
 |  keyword — validation
 | ================================================================== */

it('keyword throws ValidationException when q is missing', function () {
    $user = buildKeywordUser();
    $controller = buildKeywordController();

    expect(fn () => $controller->keyword(keywordRequest($user)))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('keyword throws ValidationException when q is too short', function () {
    $user = buildKeywordUser();
    $controller = buildKeywordController();

    expect(fn () => $controller->keyword(keywordRequest($user, ['q' => 'a'])))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('keyword throws ValidationException when q exceeds 100 chars', function () {
    $user = buildKeywordUser();
    $controller = buildKeywordController();

    expect(fn () => $controller->keyword(keywordRequest($user, [
        'q' => str_repeat('x', 101),
    ])))->toThrow(\Illuminate\Validation\ValidationException::class);
});

/* ==================================================================
 |  keyword — guard + happy path
 | ================================================================== */

it('keyword returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildKeywordUser(withProfile: false);
    $controller = buildKeywordController();

    $response = $controller->keyword(keywordRequest($user, ['q' => 'Hindu']));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

it('keyword returns paginated envelope + query_term meta on happy path', function () {
    $user = buildKeywordUser();
    $controller = buildKeywordController(totalItems: 5);

    $response = $controller->keyword(keywordRequest($user, ['q' => 'Bangalore']));
    $body = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($body['success'])->toBeTrue();
    expect($body)->toHaveKeys(['data', 'meta']);
    expect($body['meta']['query_term'])->toBe('Bangalore');
    expect($body['meta']['total'])->toBe(5);
});

/* ==================================================================
 |  byMatriId
 | ================================================================== */

it('byMatriId returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildKeywordUser(withProfile: false);
    $controller = buildKeywordController();

    $response = $controller->byMatriId(keywordRequest($user, path: '/api/v1/search/id/AM100042'), 'AM100042');

    expect($response->getStatusCode())->toBe(422);
});

it('byMatriId returns 404 NOT_FOUND when matri_id does not exist', function () {
    $user = buildKeywordUser();
    $controller = buildKeywordController(target: null);

    $response = $controller->byMatriId(keywordRequest($user, path: '/api/v1/search/id/AM999999'), 'AM999999');

    expect($response->getStatusCode())->toBe(404);
    expect($response->getData(true)['error']['code'])->toBe('NOT_FOUND');
});

it('byMatriId normalises the matri_id to uppercase before lookup', function () {
    $user = buildKeywordUser();
    $target = buildKeywordTarget('AM700001', gender: 'female');
    $controller = buildKeywordController(target: $target);

    // Pass lowercase — controller must upper-case it to match the stub.
    $response = $controller->byMatriId(keywordRequest($user, path: '/api/v1/search/id/am700001'), 'am700001');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['matri_id'])->toBe('AM700001');
});

it('byMatriId returns 404 when target is same-gender (anti-enumeration)', function () {
    $user = buildKeywordUser(gender: 'male');
    // Same-gender target — access gate rejects with REASON_SAME_GENDER.
    $target = buildKeywordTarget('AM700002', gender: 'male');
    $controller = buildKeywordController(target: $target);

    $response = $controller->byMatriId(keywordRequest($user, path: '/api/v1/search/id/AM700002'), 'AM700002');

    // 404 not 403 — anti-enumeration, same as step-5's view-other endpoint.
    expect($response->getStatusCode())->toBe(404);
});

it('byMatriId returns 404 when target is suspended', function () {
    $user = buildKeywordUser(gender: 'male');
    $target = buildKeywordTarget('AM700003', gender: 'female', overrides: [
        'suspension_status' => 'suspended',
    ]);
    $controller = buildKeywordController(target: $target);

    $response = $controller->byMatriId(keywordRequest($user, path: '/api/v1/search/id/AM700003'), 'AM700003');

    expect($response->getStatusCode())->toBe(404);
});

it('byMatriId returns card on clean access path', function () {
    $user = buildKeywordUser(gender: 'male');
    $target = buildKeywordTarget('AM700004', gender: 'female');
    $controller = buildKeywordController(target: $target);

    $response = $controller->byMatriId(keywordRequest($user, path: '/api/v1/search/id/AM700004'), 'AM700004');
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($data)->toHaveKey('matri_id');
    expect($data['matri_id'])->toBe('AM700004');
});
