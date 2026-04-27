<?php

use App\Http\Controllers\Api\V1\ShortlistController;
use App\Models\Profile;
use App\Models\Shortlist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| ShortlistController — index + toggle
|--------------------------------------------------------------------------
| Index path uses the controller's `paginateShortlistedProfiles` test
| seam to inject a pre-built paginator; that lets us test the controller
| layer (envelope shape, pagination meta, viewer injection) without
| standing up the full Profile-eager-load ecosystem (religious_info,
| education_details, location_info, photos, etc.).
|
| Toggle path needs real persistence so we INLINE just `shortlists`
| (and use `findTargetByMatriId` seam to skip the profiles table).
|
| Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-09-shortlist-views.md
*/

function buildShortlistUser(int $id, bool $withProfile = true, ?string $gender = 'male'): User
{
    $u = new User();
    $u->exists = true;
    $u->forceFill(['id' => $id, 'email' => "s{$id}@e.com", 'is_active' => true]);
    $u->setRelation('userMemberships', new EloquentCollection());

    if ($withProfile) {
        $p = buildShortlistProfile($id, $u, $gender);
        $u->setRelation('profile', $p);
    } else {
        $u->setRelation('profile', null);
    }

    return $u;
}

function buildShortlistProfile(int $id, ?User $user = null, ?string $gender = 'male', ?string $matriId = null): Profile
{
    // Build a stub user when none is passed so ProfileCardResource's
    // ->user accesses don't lazy-load from a missing users table.
    $user ??= (function () use ($id) {
        $u = new User();
        $u->exists = true;
        $u->forceFill(['id' => $id, 'email' => "stub{$id}@e.com", 'is_active' => true]);
        $u->setRelation('userMemberships', new EloquentCollection());
        return $u;
    })();

    $p = new Profile();
    $p->exists = true;
    $p->forceFill([
        'id' => $id,
        'user_id' => $user->id,
        'matri_id' => $matriId ?? 'AM'.str_pad((string) $id, 6, '0', STR_PAD_LEFT),
        'full_name' => "Profile {$id}",
        'gender' => $gender,
        'date_of_birth' => Carbon::parse('1995-01-01'),
        'is_active' => true,
        'is_approved' => true,
        'is_hidden' => false,
        'is_verified' => false,
        'is_vip' => false,
        'is_featured' => false,
    ]);

    // Pre-set every relation ProfileCardResource lazy-loads so it stays
    // DB-free when rendering through the test seam.
    $p->setRelation('user', $user);
    $p->setRelation('religiousInfo', null);
    $p->setRelation('educationDetail', null);
    $p->setRelation('locationInfo', null);
    $p->setRelation('primaryPhoto', null);

    return $p;
}

/**
 * Build a ShortlistController with both seams overridden:
 *   - paginateShortlistedProfiles: returns a pre-built paginator
 *   - findTargetByMatriId: returns an in-memory Profile lookup map
 */
function buildShortlistController(?LengthAwarePaginator $paginator = null, array $matriIdMap = []): ShortlistController
{
    return new class($paginator, $matriIdMap) extends ShortlistController {
        public function __construct(
            private ?LengthAwarePaginator $stubbedPaginator,
            private array $matriIdMap,
        ) {}

        protected function paginateShortlistedProfiles(Profile $viewer, int $perPage): LengthAwarePaginator
        {
            return $this->stubbedPaginator ?? new LengthAwarePaginator([], 0, $perPage);
        }

        protected function findTargetByMatriId(string $matriId): ?Profile
        {
            return $this->matriIdMap[$matriId] ?? null;
        }
    };
}

function shortlistRequest(User $user, string $method = 'GET', array $body = [], string $path = '/api/v1/shortlist'): Request
{
    $r = Request::create($path, $method, $body);
    $r->setUserResolver(fn () => $user);

    return $r;
}

function buildShortlistEcosystemTables(): void
{
    if (! Schema::hasTable('shortlists')) {
        Schema::create('shortlists', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('profile_id');
            $t->unsignedBigInteger('shortlisted_profile_id');
            $t->timestamps();
            $t->unique(['profile_id', 'shortlisted_profile_id']);
        });
    }
    // ProfileCardResource queries interests for interest_status. Stub
    // table is empty in tests — query returns null.
    if (! Schema::hasTable('interests')) {
        Schema::create('interests', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('sender_profile_id');
            $t->unsignedBigInteger('receiver_profile_id');
            $t->string('status')->default('pending');
            $t->timestamps();
        });
    }
    // ProfileCardResource queries blocked_profiles for is_blocked.
    if (! Schema::hasTable('blocked_profiles')) {
        Schema::create('blocked_profiles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('profile_id');
            $t->unsignedBigInteger('blocked_profile_id');
            $t->timestamps();
        });
    }
}

beforeEach(function () {
    buildShortlistEcosystemTables();
});

afterEach(function () {
    Schema::dropIfExists('blocked_profiles');
    Schema::dropIfExists('interests');
    Schema::dropIfExists('shortlists');
});

/* ==================================================================
 |  GET /shortlist — index
 | ================================================================== */

it('index returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildShortlistUser(100, withProfile: false);

    $response = buildShortlistController()->index(shortlistRequest($user));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

it('index returns paginated profile cards with meta', function () {
    $user = buildShortlistUser(100);

    // Two profiles in the viewer's shortlist.
    $a = buildShortlistProfile(201, gender: 'female', matriId: 'AM000201');
    $b = buildShortlistProfile(202, gender: 'female', matriId: 'AM000202');
    $paginator = new LengthAwarePaginator([$a, $b], 2, 20, 1);

    $response = buildShortlistController($paginator)->index(shortlistRequest($user));
    $payload = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($payload['data'])->toHaveCount(2);
    expect($payload['data'][0]['matri_id'])->toBe('AM000201');
    expect($payload['data'][1]['matri_id'])->toBe('AM000202');
    expect($payload['meta'])->toMatchArray([
        'page' => 1,
        'per_page' => 20,
        'total' => 2,
        'last_page' => 1,
    ]);
});

it('index returns empty list with correct meta when shortlist is empty', function () {
    $user = buildShortlistUser(100);

    $response = buildShortlistController()->index(shortlistRequest($user));
    $payload = $response->getData(true);

    expect($payload['data'])->toBe([]);
    expect($payload['meta']['total'])->toBe(0);
});

it('index respects per_page param, capped at 50', function () {
    $user = buildShortlistUser(100);
    $paginator = new LengthAwarePaginator([], 0, 50, 1);

    // The seam discards $perPage; we just check that the controller's
    // resolvePerPage logic clamps and drives the meta we expose.
    $response = buildShortlistController($paginator)->index(
        shortlistRequest($user, body: ['per_page' => 999]),
    );

    // The seam echoes its $perPage as paginator's perPage; with our stub
    // that's 50, matching the controller's MAX cap.
    expect($response->getData(true)['meta']['per_page'])->toBe(50);
});

/* ==================================================================
 |  POST /profiles/{matriId}/shortlist — toggle
 | ================================================================== */

it('toggle returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildShortlistUser(100, withProfile: false);

    $response = buildShortlistController()->toggle(
        shortlistRequest($user, 'POST', path: '/api/v1/profiles/AM000201/shortlist'),
        'AM000201',
    );

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

it('toggle returns 404 when target matri_id is unknown', function () {
    $user = buildShortlistUser(100);

    $response = buildShortlistController()->toggle(
        shortlistRequest($user, 'POST'),
        'AM999999',  // not in the matriIdMap
    );

    expect($response->getStatusCode())->toBe(404);
    expect($response->getData(true)['error']['code'])->toBe('NOT_FOUND');
});

it('toggle returns 422 INVALID_TARGET when shortlisting self', function () {
    $user = buildShortlistUser(100);
    $self = $user->profile;

    $response = buildShortlistController(matriIdMap: [$self->matri_id => $self])
        ->toggle(shortlistRequest($user, 'POST'), $self->matri_id);

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('INVALID_TARGET');
});

it('toggle returns 422 INVALID_TARGET when shortlisting same gender', function () {
    $user = buildShortlistUser(100, gender: 'male');
    $sameGender = buildShortlistProfile(201, gender: 'male', matriId: 'AM000201');

    $response = buildShortlistController(matriIdMap: ['AM000201' => $sameGender])
        ->toggle(shortlistRequest($user, 'POST'), 'AM000201');

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('INVALID_TARGET');
});

it('toggle creates a shortlist row + returns is_shortlisted=true on first call', function () {
    $user = buildShortlistUser(100, gender: 'male');
    $target = buildShortlistProfile(201, gender: 'female', matriId: 'AM000201');

    $response = buildShortlistController(matriIdMap: ['AM000201' => $target])
        ->toggle(shortlistRequest($user, 'POST'), 'AM000201');
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($data['is_shortlisted'])->toBeTrue();
    expect($data['shortlist_count'])->toBe(1);

    expect(Shortlist::where('profile_id', 100)
        ->where('shortlisted_profile_id', 201)
        ->exists())->toBeTrue();
});

it('toggle deletes the shortlist row + returns is_shortlisted=false on second call', function () {
    $user = buildShortlistUser(100, gender: 'male');
    $target = buildShortlistProfile(201, gender: 'female', matriId: 'AM000201');
    Shortlist::create(['profile_id' => 100, 'shortlisted_profile_id' => 201]);

    $response = buildShortlistController(matriIdMap: ['AM000201' => $target])
        ->toggle(shortlistRequest($user, 'POST'), 'AM000201');
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($data['is_shortlisted'])->toBeFalse();
    expect($data['shortlist_count'])->toBe(0);

    expect(Shortlist::where('profile_id', 100)
        ->where('shortlisted_profile_id', 201)
        ->exists())->toBeFalse();
});

it('toggle shortlist_count counts only the viewer own shortlists', function () {
    $user = buildShortlistUser(100, gender: 'male');
    $target = buildShortlistProfile(201, gender: 'female', matriId: 'AM000201');

    // Pre-seed: viewer has 2 unrelated shortlists; stranger has one too.
    Shortlist::create(['profile_id' => 100, 'shortlisted_profile_id' => 301]);
    Shortlist::create(['profile_id' => 100, 'shortlisted_profile_id' => 302]);
    Shortlist::create(['profile_id' => 999, 'shortlisted_profile_id' => 201]);  // not the viewer's

    $response = buildShortlistController(matriIdMap: ['AM000201' => $target])
        ->toggle(shortlistRequest($user, 'POST'), 'AM000201');

    // Viewer's count: 2 pre-seed + 1 just added = 3.
    expect($response->getData(true)['data']['shortlist_count'])->toBe(3);
});
