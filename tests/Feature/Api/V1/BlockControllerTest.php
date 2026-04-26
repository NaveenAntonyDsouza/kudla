<?php

use App\Http\Controllers\Api\V1\BlockController;
use App\Models\BlockedProfile;
use App\Models\Interest;
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
| BlockController — index + block + unblock
|--------------------------------------------------------------------------
| Index uses the protected `paginateBlocked` seam to inject a pre-built
| paginator with in-memory profiles. Block/unblock paths persist to
| inline `blocked_profiles` so we exercise the real firstOrCreate /
| delete logic.
|
| Block side-effects (interest cancellation, shortlist removal) are
| verified end-to-end against inline `interests` + `shortlists` tables.
*/

function buildBlockUser(int $id, bool $withProfile = true, ?string $gender = 'male'): User
{
    $u = new User();
    $u->exists = true;
    $u->forceFill(['id' => $id, 'email' => "b{$id}@e.com", 'is_active' => true]);
    $u->setRelation('userMemberships', new EloquentCollection());

    if ($withProfile) {
        $u->setRelation('profile', buildBlockProfile($id, $u, $gender));
    } else {
        $u->setRelation('profile', null);
    }

    return $u;
}

function buildBlockProfile(int $id, ?User $user = null, ?string $gender = 'male', ?string $matriId = null): Profile
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

function buildBlockController(?LengthAwarePaginator $paginator = null, array $matriIdMap = []): BlockController
{
    return new class($paginator, $matriIdMap) extends BlockController {
        public function __construct(
            private ?LengthAwarePaginator $stubbedPaginator,
            private array $matriIdMap,
        ) {}

        protected function paginateBlocked(Profile $viewer, int $perPage): LengthAwarePaginator
        {
            return $this->stubbedPaginator ?? new LengthAwarePaginator([], 0, $perPage);
        }

        protected function findTargetByMatriId(string $matriId): ?Profile
        {
            return $this->matriIdMap[$matriId] ?? null;
        }
    };
}

function blockRequest(User $user, string $method = 'GET', array $body = [], string $path = '/api/v1/blocked'): Request
{
    $r = Request::create($path, $method, $body);
    $r->setUserResolver(fn () => $user);

    return $r;
}

function buildBlockEcosystemTables(): void
{
    if (! Schema::hasTable('blocked_profiles')) {
        Schema::create('blocked_profiles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('profile_id');
            $t->unsignedBigInteger('blocked_profile_id');
            $t->timestamps();
            $t->unique(['profile_id', 'blocked_profile_id']);
        });
    }
    if (! Schema::hasTable('interests')) {
        Schema::create('interests', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('sender_profile_id');
            $t->unsignedBigInteger('receiver_profile_id');
            $t->string('status')->default('pending');
            $t->timestamp('cancelled_at')->nullable();
            $t->timestamps();
        });
    }
    if (! Schema::hasTable('shortlists')) {
        Schema::create('shortlists', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('profile_id');
            $t->unsignedBigInteger('shortlisted_profile_id');
            $t->timestamps();
        });
    }
}

beforeEach(function () {
    buildBlockEcosystemTables();
});

afterEach(function () {
    Schema::dropIfExists('shortlists');
    Schema::dropIfExists('interests');
    Schema::dropIfExists('blocked_profiles');
});

/* ==================================================================
 |  GET /blocked
 | ================================================================== */

it('index returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildBlockUser(100, withProfile: false);

    $response = buildBlockController()->index(blockRequest($user));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

it('index returns paginated blocked profile cards with meta', function () {
    $user = buildBlockUser(100);
    $a = buildBlockProfile(201, gender: 'female', matriId: 'AM000201');
    $paginator = new LengthAwarePaginator([$a], 1, 20, 1);

    $response = buildBlockController($paginator)->index(blockRequest($user));
    $payload = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($payload['data'])->toHaveCount(1);
    expect($payload['data'][0]['matri_id'])->toBe('AM000201');
    expect($payload['meta']['total'])->toBe(1);
});

/* ==================================================================
 |  POST /profiles/{matriId}/block
 | ================================================================== */

it('block returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildBlockUser(100, withProfile: false);

    $response = buildBlockController()->block(
        blockRequest($user, 'POST', path: '/api/v1/profiles/AM000201/block'),
        'AM000201',
    );

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

it('block returns 404 when target matri_id is unknown', function () {
    $user = buildBlockUser(100);

    $response = buildBlockController()->block(blockRequest($user, 'POST'), 'AM999999');

    expect($response->getStatusCode())->toBe(404);
});

it('block returns 422 INVALID_TARGET on self-block', function () {
    $user = buildBlockUser(100);
    $self = $user->profile;

    $response = buildBlockController(matriIdMap: [$self->matri_id => $self])
        ->block(blockRequest($user, 'POST'), $self->matri_id);

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('INVALID_TARGET');
});

it('block returns 422 INVALID_TARGET on same-gender block', function () {
    $user = buildBlockUser(100, gender: 'male');
    $sameGender = buildBlockProfile(201, gender: 'male', matriId: 'AM000201');

    $response = buildBlockController(matriIdMap: ['AM000201' => $sameGender])
        ->block(blockRequest($user, 'POST'), 'AM000201');

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('INVALID_TARGET');
});

it('block creates a blocked_profiles row + returns blocked=true', function () {
    $user = buildBlockUser(100, gender: 'male');
    $target = buildBlockProfile(201, gender: 'female', matriId: 'AM000201');

    $response = buildBlockController(matriIdMap: ['AM000201' => $target])
        ->block(blockRequest($user, 'POST'), 'AM000201');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['blocked'])->toBeTrue();
    expect(BlockedProfile::where('profile_id', 100)
        ->where('blocked_profile_id', 201)
        ->exists())->toBeTrue();
});

it('block is idempotent — second call does not create duplicate', function () {
    $user = buildBlockUser(100, gender: 'male');
    $target = buildBlockProfile(201, gender: 'female', matriId: 'AM000201');

    $controller = buildBlockController(matriIdMap: ['AM000201' => $target]);

    // First call creates.
    $r1 = $controller->block(blockRequest($user, 'POST'), 'AM000201');
    expect($r1->getStatusCode())->toBe(200);

    // Second call no-ops (firstOrCreate).
    $r2 = $controller->block(blockRequest($user, 'POST'), 'AM000201');
    expect($r2->getStatusCode())->toBe(200);

    expect(BlockedProfile::where('profile_id', 100)
        ->where('blocked_profile_id', 201)
        ->count())->toBe(1);
});

it('block cancels pending interests in either direction', function () {
    $user = buildBlockUser(100, gender: 'male');
    $target = buildBlockProfile(201, gender: 'female', matriId: 'AM000201');

    // Pre-seed: pending interest from viewer → target, AND target → viewer.
    Interest::create(['sender_profile_id' => 100, 'receiver_profile_id' => 201, 'status' => 'pending']);
    Interest::create(['sender_profile_id' => 201, 'receiver_profile_id' => 100, 'status' => 'pending']);
    // Plus an accepted interest that should NOT be cancelled.
    Interest::create(['sender_profile_id' => 100, 'receiver_profile_id' => 201, 'status' => 'accepted']);

    buildBlockController(matriIdMap: ['AM000201' => $target])
        ->block(blockRequest($user, 'POST'), 'AM000201');

    // Both pending → cancelled.
    expect(Interest::where('status', 'pending')->count())->toBe(0);
    expect(Interest::where('status', 'cancelled')->count())->toBe(2);
    // Accepted untouched.
    expect(Interest::where('status', 'accepted')->count())->toBe(1);
});

it('block removes the viewer shortlist of target (one-sided)', function () {
    $user = buildBlockUser(100, gender: 'male');
    $target = buildBlockProfile(201, gender: 'female', matriId: 'AM000201');

    // Viewer has shortlisted target. Target has shortlisted viewer.
    Shortlist::create(['profile_id' => 100, 'shortlisted_profile_id' => 201]);
    Shortlist::create(['profile_id' => 201, 'shortlisted_profile_id' => 100]);

    buildBlockController(matriIdMap: ['AM000201' => $target])
        ->block(blockRequest($user, 'POST'), 'AM000201');

    // Viewer's shortlist of target → removed.
    expect(Shortlist::where('profile_id', 100)->where('shortlisted_profile_id', 201)->exists())->toBeFalse();
    // Target's shortlist of viewer → preserved (target's business).
    expect(Shortlist::where('profile_id', 201)->where('shortlisted_profile_id', 100)->exists())->toBeTrue();
});

/* ==================================================================
 |  POST /profiles/{matriId}/unblock
 | ================================================================== */

it('unblock deletes the blocked_profiles row + returns blocked=false', function () {
    $user = buildBlockUser(100, gender: 'male');
    $target = buildBlockProfile(201, gender: 'female', matriId: 'AM000201');
    BlockedProfile::create(['profile_id' => 100, 'blocked_profile_id' => 201]);

    $response = buildBlockController(matriIdMap: ['AM000201' => $target])
        ->unblock(blockRequest($user, 'POST'), 'AM000201');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['blocked'])->toBeFalse();
    expect(BlockedProfile::where('profile_id', 100)->where('blocked_profile_id', 201)->exists())->toBeFalse();
});

it('unblock is idempotent — no row to delete still returns success', function () {
    $user = buildBlockUser(100, gender: 'male');
    $target = buildBlockProfile(201, gender: 'female', matriId: 'AM000201');

    $response = buildBlockController(matriIdMap: ['AM000201' => $target])
        ->unblock(blockRequest($user, 'POST'), 'AM000201');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['blocked'])->toBeFalse();
});
