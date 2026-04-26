<?php

use App\Http\Controllers\Api\V1\IgnoredProfileController;
use App\Models\IgnoredProfile;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| IgnoredProfileController — index + toggle
|--------------------------------------------------------------------------
| Same test-seam pattern as BlockControllerTest. Toggle persists to
| inline `ignored_profiles`; index uses the protected `paginateIgnored`
| seam to inject in-memory profiles.
|
| Note: ignore (unlike block) has no same-gender guard — ignoring a
| same-gender user is harmless (they don't see you anyway).
*/

function buildIgnoreUser(int $id, bool $withProfile = true, ?string $gender = 'male'): User
{
    $u = new User();
    $u->exists = true;
    $u->forceFill(['id' => $id, 'email' => "ig{$id}@e.com", 'is_active' => true]);
    $u->setRelation('userMemberships', new EloquentCollection());

    if ($withProfile) {
        $u->setRelation('profile', buildIgnoreProfile($id, $u, $gender));
    } else {
        $u->setRelation('profile', null);
    }

    return $u;
}

function buildIgnoreProfile(int $id, ?User $user = null, ?string $gender = 'male', ?string $matriId = null): Profile
{
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
    ]);

    $p->setRelation('user', $user);
    $p->setRelation('religiousInfo', null);
    $p->setRelation('educationDetail', null);
    $p->setRelation('locationInfo', null);
    $p->setRelation('primaryPhoto', null);

    return $p;
}

function buildIgnoreController(?LengthAwarePaginator $paginator = null, array $matriIdMap = []): IgnoredProfileController
{
    return new class($paginator, $matriIdMap) extends IgnoredProfileController {
        public function __construct(
            private ?LengthAwarePaginator $stubbedPaginator,
            private array $matriIdMap,
        ) {}

        protected function paginateIgnored(Profile $viewer, int $perPage): LengthAwarePaginator
        {
            return $this->stubbedPaginator ?? new LengthAwarePaginator([], 0, $perPage);
        }

        protected function findTargetByMatriId(string $matriId): ?Profile
        {
            return $this->matriIdMap[$matriId] ?? null;
        }
    };
}

function ignoreRequest(User $user, string $method = 'GET', array $body = [], string $path = '/api/v1/ignored'): Request
{
    $r = Request::create($path, $method, $body);
    $r->setUserResolver(fn () => $user);

    return $r;
}

beforeEach(function () {
    if (! Schema::hasTable('ignored_profiles')) {
        Schema::create('ignored_profiles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('profile_id');
            $t->unsignedBigInteger('ignored_profile_id');
            $t->timestamps();
            $t->unique(['profile_id', 'ignored_profile_id']);
        });
    }
    // Empty stubs — ProfileCardResource queries these when rendering
    // with a viewer (is_shortlisted, interest_status, is_blocked).
    if (! Schema::hasTable('shortlists')) {
        Schema::create('shortlists', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('profile_id');
            $t->unsignedBigInteger('shortlisted_profile_id');
            $t->timestamps();
        });
    }
    if (! Schema::hasTable('interests')) {
        Schema::create('interests', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('sender_profile_id');
            $t->unsignedBigInteger('receiver_profile_id');
            $t->string('status')->default('pending');
            $t->timestamps();
        });
    }
    if (! Schema::hasTable('blocked_profiles')) {
        Schema::create('blocked_profiles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('profile_id');
            $t->unsignedBigInteger('blocked_profile_id');
            $t->timestamps();
        });
    }
});

afterEach(function () {
    Schema::dropIfExists('blocked_profiles');
    Schema::dropIfExists('interests');
    Schema::dropIfExists('shortlists');
    Schema::dropIfExists('ignored_profiles');
});

/* ==================================================================
 |  GET /ignored
 | ================================================================== */

it('index returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildIgnoreUser(100, withProfile: false);

    $response = buildIgnoreController()->index(ignoreRequest($user));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

it('index returns paginated ignored profile cards with meta', function () {
    $user = buildIgnoreUser(100);
    $target = buildIgnoreProfile(201, gender: 'female', matriId: 'AM000201');
    $paginator = new LengthAwarePaginator([$target], 1, 20, 1);

    $response = buildIgnoreController($paginator)->index(ignoreRequest($user));
    $payload = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($payload['data'])->toHaveCount(1);
    expect($payload['data'][0]['matri_id'])->toBe('AM000201');
});

/* ==================================================================
 |  POST /profiles/{matriId}/ignore-toggle
 | ================================================================== */

it('toggle returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildIgnoreUser(100, withProfile: false);

    $response = buildIgnoreController()->toggle(
        ignoreRequest($user, 'POST', path: '/api/v1/profiles/AM000201/ignore-toggle'),
        'AM000201',
    );

    expect($response->getStatusCode())->toBe(422);
});

it('toggle returns 404 when target matri_id is unknown', function () {
    $user = buildIgnoreUser(100);

    $response = buildIgnoreController()->toggle(ignoreRequest($user, 'POST'), 'AM999999');

    expect($response->getStatusCode())->toBe(404);
});

it('toggle returns 422 INVALID_TARGET on self-ignore', function () {
    $user = buildIgnoreUser(100);
    $self = $user->profile;

    $response = buildIgnoreController(matriIdMap: [$self->matri_id => $self])
        ->toggle(ignoreRequest($user, 'POST'), $self->matri_id);

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('INVALID_TARGET');
});

it('toggle creates a row + returns is_ignored=true on first call', function () {
    $user = buildIgnoreUser(100);
    $target = buildIgnoreProfile(201, gender: 'female', matriId: 'AM000201');

    $response = buildIgnoreController(matriIdMap: ['AM000201' => $target])
        ->toggle(ignoreRequest($user, 'POST'), 'AM000201');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['is_ignored'])->toBeTrue();
    expect(IgnoredProfile::where('profile_id', 100)
        ->where('ignored_profile_id', 201)
        ->exists())->toBeTrue();
});

it('toggle deletes the row + returns is_ignored=false on second call', function () {
    $user = buildIgnoreUser(100);
    $target = buildIgnoreProfile(201, gender: 'female', matriId: 'AM000201');
    IgnoredProfile::create(['profile_id' => 100, 'ignored_profile_id' => 201]);

    $response = buildIgnoreController(matriIdMap: ['AM000201' => $target])
        ->toggle(ignoreRequest($user, 'POST'), 'AM000201');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['is_ignored'])->toBeFalse();
    expect(IgnoredProfile::where('profile_id', 100)
        ->where('ignored_profile_id', 201)
        ->exists())->toBeFalse();
});

it('toggle does NOT 422 on same-gender (ignore is harmless cross-gender)', function () {
    $user = buildIgnoreUser(100, gender: 'male');
    $sameGender = buildIgnoreProfile(201, gender: 'male', matriId: 'AM000201');

    $response = buildIgnoreController(matriIdMap: ['AM000201' => $sameGender])
        ->toggle(ignoreRequest($user, 'POST'), 'AM000201');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['is_ignored'])->toBeTrue();
});
