<?php

use App\Http\Controllers\Api\V1\MatchController;
use App\Models\PartnerPreference;
use App\Models\Profile;
use App\Models\User;
use App\Services\MatchingService;
use App\Services\ProfileAccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| MatchController — /matches/my · /matches/mutual · /matches/score/{matriId}
|--------------------------------------------------------------------------
| Stubs MatchingService via a recording fake bound through the container.
| The fake captures calls + returns pre-built paginators / score arrays
| so tests stay DB-free + lockable.
|
| Score-endpoint cache is exercised via real Cache::put / Cache::get
| against the array driver (CACHE_STORE=array per phpunit.xml). Each
| test starts with Cache::flush() to isolate.
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

/** Recording MatchingService — captures calls and returns pre-set responses. */
class FakeMatchingService extends MatchingService
{
    /** @var array<int, array{method: string, args: array}> */
    public array $calls = [];

    public function __construct(
        public ?LengthAwarePaginator $matchesResponse = null,
        public ?LengthAwarePaginator $mutualResponse = null,
        public ?array $scoreResponse = null,
    ) {}  // skip parent constructor

    public function getMatches(Profile $profile, int $perPage = 20): LengthAwarePaginator
    {
        $this->calls[] = ['method' => 'getMatches', 'args' => ['profile_id' => $profile->id, 'per_page' => $perPage]];

        return $this->matchesResponse ?? new LengthAwarePaginator([], 0, $perPage);
    }

    public function getMutualMatches(Profile $profile, int $perPage = 20): LengthAwarePaginator
    {
        $this->calls[] = ['method' => 'getMutualMatches', 'args' => ['profile_id' => $profile->id, 'per_page' => $perPage]];

        return $this->mutualResponse ?? new LengthAwarePaginator([], 0, $perPage);
    }

    public function calculateScore(Profile $candidate, PartnerPreference $prefs): array
    {
        $this->calls[] = ['method' => 'calculateScore', 'args' => ['candidate_id' => $candidate->id]];

        return $this->scoreResponse ?? [
            'score' => 75,
            'breakdown' => [],
            'badge' => 'good',
        ];
    }
}

/** Build a User+Profile (or no profile) for match tests. */
function buildMatchUser(int $id = 9100, string $gender = 'male', bool $withProfile = true, bool $withPrefs = false): User
{
    $user = new User();
    $user->exists = true;
    $user->forceFill([
        'id' => $id,
        'email' => "match{$id}@example.com",
        'phone' => "98000000{$id}",
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
            'full_name' => "Match {$id}",
            'gender' => $gender,
            'date_of_birth' => Carbon::parse('1995-01-01'),
            'is_active' => true,
            'is_approved' => true,
            'is_hidden' => false,
            'suspension_status' => 'active',
            'show_profile_to' => 'all',
        ]);
        $profile->setRelation('user', $user);
        $profile->setRelation('photoPrivacySetting', null);

        if ($withPrefs) {
            $prefs = new PartnerPreference();
            $prefs->exists = true;
            $prefs->forceFill([
                'id' => $id,
                'profile_id' => $id,
                'age_from' => 25,
                'age_to' => 35,
            ]);
            $profile->setRelation('partnerPreference', $prefs);
        } else {
            $profile->setRelation('partnerPreference', null);
        }

        $user->setRelation('profile', $profile);
    } else {
        $user->setRelation('profile', null);
    }

    return $user;
}

/** Build an in-memory target Profile for /matches/score lookups. */
function buildMatchTarget(string $matriId, string $gender = 'female', array $overrides = []): Profile
{
    $u = new User();
    $u->exists = true;
    $u->forceFill(['id' => 9999, 'email' => 't@e.com', 'is_active' => true]);
    $u->setRelation('userMemberships', new \Illuminate\Database\Eloquent\Collection());

    $t = new Profile();
    $t->exists = true;
    $t->forceFill(array_merge([
        'id' => 9999,
        'user_id' => 9999,
        'matri_id' => $matriId,
        'gender' => $gender,
        'full_name' => 'Target',
        'date_of_birth' => Carbon::parse('1996-08-20'),
        'is_active' => true,
        'is_approved' => true,
        'is_hidden' => false,
        'suspension_status' => 'active',
        'show_profile_to' => 'all',
    ], $overrides));
    $t->setRelation('user', $u);
    $t->setRelation('partnerPreference', null);
    $t->setRelation('photoPrivacySetting', null);

    return $t;
}

/**
 * Build a MatchController with the fake MatchingService injected and
 * an optional stubbed target profile for findTargetByMatriId.
 */
function buildMatchController(?Profile $target = null, ?FakeMatchingService $fake = null): MatchController
{
    $fake ??= new FakeMatchingService();

    return new class($fake, app(ProfileAccessService::class), $target) extends MatchController {
        public function __construct(
            FakeMatchingService $matches,
            ProfileAccessService $access,
            private ?Profile $stubbedTarget,
        ) {
            parent::__construct($matches, $access);
        }

        protected function findTargetByMatriId(string $matriId): ?Profile
        {
            return $this->stubbedTarget && $this->stubbedTarget->matri_id === $matriId
                ? $this->stubbedTarget
                : null;
        }
    };
}

function matchRequest(User $user, array $query = [], string $path = '/api/v1/matches'): Request
{
    $r = Request::create($path.'?'.http_build_query($query), 'GET');
    $r->setUserResolver(fn () => $user);

    return $r;
}

beforeEach(function () {
    Cache::flush();
});

/* ==================================================================
 |  /matches/my
 | ================================================================== */

it('my returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildMatchUser(withProfile: false);

    $response = buildMatchController()->my(matchRequest($user));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

it('my returns paginated envelope on happy path', function () {
    $user = buildMatchUser();
    $fake = new FakeMatchingService(
        matchesResponse: new LengthAwarePaginator([], 7, 20, 1),
    );
    $controller = buildMatchController(fake: $fake);

    $response = $controller->my(matchRequest($user));
    $body = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($body)->toHaveKeys(['data', 'meta']);
    expect($body['meta']['total'])->toBe(7);
    expect($fake->calls[0]['method'])->toBe('getMatches');
});

it('my returns total=0 gracefully when viewer has no preferences', function () {
    // FakeMatchingService default response is an empty paginator,
    // which is what real getMatches() returns when partnerPreference is null.
    $user = buildMatchUser();  // withPrefs: false (default)
    $controller = buildMatchController();

    $response = $controller->my(matchRequest($user));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['meta']['total'])->toBe(0);
});

/* ==================================================================
 |  /matches/mutual
 | ================================================================== */

it('mutual returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildMatchUser(withProfile: false);

    $response = buildMatchController()->mutual(matchRequest($user));

    expect($response->getStatusCode())->toBe(422);
});

it('mutual returns paginated envelope on happy path', function () {
    $user = buildMatchUser();
    $fake = new FakeMatchingService(
        mutualResponse: new LengthAwarePaginator([], 3, 20, 1),
    );
    $controller = buildMatchController(fake: $fake);

    $response = $controller->mutual(matchRequest($user));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['meta']['total'])->toBe(3);
    expect($fake->calls[0]['method'])->toBe('getMutualMatches');
});

/* ==================================================================
 |  /matches/score/{matriId}
 | ================================================================== */

it('score returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildMatchUser(withProfile: false);

    $response = buildMatchController()->score(
        matchRequest($user, path: '/api/v1/matches/score/AM999999'),
        'AM999999',
    );

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

it('score returns 404 NOT_FOUND when target matri_id does not exist', function () {
    $user = buildMatchUser(withPrefs: true);
    $controller = buildMatchController(target: null);  // no target stubbed

    $response = $controller->score(
        matchRequest($user, path: '/api/v1/matches/score/AM999999'),
        'AM999999',
    );

    expect($response->getStatusCode())->toBe(404);
    expect($response->getData(true)['error']['code'])->toBe('NOT_FOUND');
});

it('score returns 403 GENDER_MISMATCH for same-gender target', function () {
    $user = buildMatchUser(gender: 'male', withPrefs: true);
    $target = buildMatchTarget('AM910001', gender: 'male');
    $controller = buildMatchController(target: $target);

    $response = $controller->score(
        matchRequest($user, path: '/api/v1/matches/score/AM910001'),
        'AM910001',
    );

    expect($response->getStatusCode())->toBe(403);
    expect($response->getData(true)['error']['code'])->toBe('GENDER_MISMATCH');
});

it('score returns 404 NOT_FOUND when target is suspended (anti-enumeration)', function () {
    $user = buildMatchUser(gender: 'male', withPrefs: true);
    $target = buildMatchTarget('AM910001', gender: 'female', overrides: [
        'suspension_status' => 'suspended',
    ]);
    $controller = buildMatchController(target: $target);

    $response = $controller->score(
        matchRequest($user, path: '/api/v1/matches/score/AM910001'),
        'AM910001',
    );

    expect($response->getStatusCode())->toBe(404);
});

it('score returns 422 PREFERENCES_REQUIRED when viewer has no partner_preference', function () {
    $user = buildMatchUser(gender: 'male', withPrefs: false);  // no prefs
    $target = buildMatchTarget('AM910001', gender: 'female');
    $controller = buildMatchController(target: $target);

    $response = $controller->score(
        matchRequest($user, path: '/api/v1/matches/score/AM910001'),
        'AM910001',
    );

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PREFERENCES_REQUIRED');
});

it('score returns score+breakdown+badge on happy path with cached:false', function () {
    $user = buildMatchUser(gender: 'male', withPrefs: true);
    $target = buildMatchTarget('AM910001', gender: 'female');
    $fake = new FakeMatchingService(
        scoreResponse: ['score' => 87, 'breakdown' => [['criterion' => 'religion', 'matched' => true]], 'badge' => 'great'],
    );
    $controller = buildMatchController(target: $target, fake: $fake);

    $response = $controller->score(
        matchRequest($user, path: '/api/v1/matches/score/AM910001'),
        'AM910001',
    );
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($data['score'])->toBe(87);
    expect($data['badge'])->toBe('great');
    expect($data['cached'])->toBeFalse();
    expect($data['breakdown'])->toBeArray();
});

it('score second call returns cached:true without re-invoking calculateScore', function () {
    $user = buildMatchUser(gender: 'male', withPrefs: true);
    $target = buildMatchTarget('AM910001', gender: 'female');
    $fake = new FakeMatchingService(
        scoreResponse: ['score' => 87, 'breakdown' => [], 'badge' => 'great'],
    );
    $controller = buildMatchController(target: $target, fake: $fake);

    // First call — fills cache.
    $controller->score(matchRequest($user, path: '/api/v1/matches/score/AM910001'), 'AM910001');
    expect(count($fake->calls))->toBe(1);

    // Second call — must read from cache, not re-invoke service.
    $response = $controller->score(matchRequest($user, path: '/api/v1/matches/score/AM910001'), 'AM910001');
    expect(count($fake->calls))->toBe(1);  // unchanged
    expect($response->getData(true)['data']['cached'])->toBeTrue();
    expect($response->getData(true)['data']['score'])->toBe(87);
});

it('score normalises lowercase matri_id to uppercase before lookup', function () {
    $user = buildMatchUser(gender: 'male', withPrefs: true);
    $target = buildMatchTarget('AM910001', gender: 'female');
    $controller = buildMatchController(target: $target);

    $response = $controller->score(
        matchRequest($user, path: '/api/v1/matches/score/am910001'),
        'am910001',  // lowercase input
    );

    // Stub matches AM910001 (uppercase) — the controller upper-cased the input.
    expect($response->getStatusCode())->toBe(200);
});

/* ==================================================================
 |  Constants
 | ================================================================== */

it('exposes DEFAULT_PER_PAGE=20, MAX_PER_PAGE=50, SCORE_CACHE_TTL_SECONDS=86400', function () {
    expect(MatchController::DEFAULT_PER_PAGE)->toBe(20);
    expect(MatchController::MAX_PER_PAGE)->toBe(50);
    expect(MatchController::SCORE_CACHE_TTL_SECONDS)->toBe(86_400);
});
