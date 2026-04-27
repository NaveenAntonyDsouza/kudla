<?php

use App\Http\Controllers\Api\V1\ProfileViewController;
use App\Models\Profile;
use App\Models\ProfileView;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| ProfileViewController — GET /views (viewed_by + i_viewed)
|--------------------------------------------------------------------------
| Pagination + premium-gating logic exercised through the controller's
| four protected seams (`countViewedBy`, `paginateViewedBy`,
| `paginateIViewed`) so we never hit the full Profile-eager-load
| ecosystem.
|
| The total_count count() path needs a real `profile_views` table to
| keep the production query behaviour intact in tests; everything else
| is in-memory.
|
| Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-09-shortlist-views.md
*/

function buildPVUser(int $id, bool $withProfile = true, bool $premium = false): User
{
    $u = new User();
    $u->exists = true;
    $u->forceFill(['id' => $id, 'email' => "v{$id}@e.com", 'is_active' => true]);
    // The User stub override below swaps isPremium() — we don't rely
    // on userMemberships at all in these tests.
    $u->setRelation('userMemberships', new EloquentCollection());
    $u->__pvIsPremium = $premium;  // dynamic flag read by the controller subclass

    if ($withProfile) {
        $u->setRelation('profile', buildPVProfile($id, $u));
    } else {
        $u->setRelation('profile', null);
    }

    return $u;
}

function buildPVProfile(int $id, ?User $user = null, ?string $matriId = null): Profile
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
        'gender' => 'female',
        'date_of_birth' => Carbon::parse('1995-01-01'),
        'is_active' => true,
        'is_approved' => true,
        'is_hidden' => false,
        'is_verified' => false,
        'is_vip' => false,
        'is_featured' => false,
    ]);

    $p->setRelation('user', $user);
    $p->setRelation('religiousInfo', null);
    $p->setRelation('educationDetail', null);
    $p->setRelation('locationInfo', null);
    $p->setRelation('primaryPhoto', null);

    return $p;
}

/** Build a ProfileView with viewerProfile / viewedProfile pre-set. */
function buildPVRow(?Profile $viewer = null, ?Profile $viewed = null, ?Carbon $when = null): ProfileView
{
    $pv = new ProfileView();
    $pv->exists = true;
    $pv->forceFill([
        'id' => random_int(1, 999999),
        'viewer_profile_id' => $viewer?->id,
        'viewed_profile_id' => $viewed?->id,
        'viewed_at' => $when ?? Carbon::now(),
    ]);
    if ($viewer) {
        $pv->setRelation('viewerProfile', $viewer);
    }
    if ($viewed) {
        $pv->setRelation('viewedProfile', $viewed);
    }

    return $pv;
}

/**
 * ProfileViewController with all four seams overridden:
 *   countViewedBy / paginateViewedBy / paginateIViewed / resolvePremium
 *
 * `resolvePremium` is overridden via a dynamic User flag (__pvIsPremium)
 * because User::isPremium() would otherwise hit the real
 * user_memberships table.
 */
function buildPVController(?int $totalCount = null, ?LengthAwarePaginator $viewedByPaginator = null, ?LengthAwarePaginator $iViewedPaginator = null): ProfileViewController
{
    return new class($totalCount, $viewedByPaginator, $iViewedPaginator) extends ProfileViewController {
        public function __construct(
            private ?int $stubbedTotal,
            private ?LengthAwarePaginator $stubbedViewedBy,
            private ?LengthAwarePaginator $stubbedIViewed,
        ) {}

        protected function countViewedBy(Profile $viewer): int
        {
            return $this->stubbedTotal ?? 0;
        }

        protected function paginateViewedBy(Profile $viewer, int $perPage): LengthAwarePaginator
        {
            return $this->stubbedViewedBy ?? new LengthAwarePaginator([], 0, $perPage);
        }

        protected function paginateIViewed(Profile $viewer, int $perPage): LengthAwarePaginator
        {
            return $this->stubbedIViewed ?? new LengthAwarePaginator([], 0, $perPage);
        }
    };
}

function pvRequest(User $user, array $query = []): Request
{
    $r = Request::create('/api/v1/views', 'GET', $query);
    $r->setUserResolver(fn () => $user);
    // Swap isPremium on the user via a runtime override — we read
    // $user->__pvIsPremium then patch the controller's resolvePremium.
    return $r;
}

/**
 * Build a ProfileViewController whose resolvePremium method honours
 * the test User's dynamic __pvIsPremium flag — bypassing the real
 * isPremium() DB lookup.
 */
function buildPVControllerWithPremium(bool $premium, ?int $totalCount = null, ?LengthAwarePaginator $viewedByPaginator = null, ?LengthAwarePaginator $iViewedPaginator = null): ProfileViewController
{
    return new class($premium, $totalCount, $viewedByPaginator, $iViewedPaginator) extends ProfileViewController {
        public function __construct(
            private bool $premium,
            private ?int $stubbedTotal,
            private ?LengthAwarePaginator $stubbedViewedBy,
            private ?LengthAwarePaginator $stubbedIViewed,
        ) {}

        protected function countViewedBy(Profile $viewer): int
        {
            return $this->stubbedTotal ?? 0;
        }

        protected function paginateViewedBy(Profile $viewer, int $perPage): LengthAwarePaginator
        {
            return $this->stubbedViewedBy ?? new LengthAwarePaginator([], 0, $perPage);
        }

        protected function paginateIViewed(Profile $viewer, int $perPage): LengthAwarePaginator
        {
            return $this->stubbedIViewed ?? new LengthAwarePaginator([], 0, $perPage);
        }

        protected function resolvePremium($user): bool
        {
            return $this->premium;
        }
    };
}

function buildPVTablesEcosystem(): void
{
    if (! Schema::hasTable('profile_views')) {
        Schema::create('profile_views', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('viewer_profile_id');
            $t->unsignedBigInteger('viewed_profile_id');
            $t->timestamp('viewed_at')->useCurrent();
            $t->index(['viewed_profile_id', 'viewed_at']);
            $t->index(['viewer_profile_id', 'viewed_at']);
        });
    }
    // ProfileCardResource queries interests / blocked_profiles / shortlists
    // when rendering with a viewer.
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
    buildPVTablesEcosystem();
});

afterEach(function () {
    Schema::dropIfExists('shortlists');
    Schema::dropIfExists('blocked_profiles');
    Schema::dropIfExists('interests');
    Schema::dropIfExists('profile_views');
});

/* ==================================================================
 |  Guard — no profile
 | ================================================================== */

it('returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildPVUser(100, withProfile: false);

    $response = buildPVController()->index(pvRequest($user));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

/* ==================================================================
 |  tab=viewed_by — premium-gated
 | ================================================================== */

it('viewed_by free user: total_count exposed, viewers empty', function () {
    $user = buildPVUser(100);

    $response = buildPVControllerWithPremium(premium: false, totalCount: 7)
        ->index(pvRequest($user));
    $payload = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($payload['data'])->toMatchArray([
        'tab' => 'viewed_by',
        'is_premium' => false,
        'total_count' => 7,
        'viewers' => [],
    ]);
    expect($payload['meta']['total'])->toBe(0);   // empty paginator
});

it('viewed_by premium: paginated viewer profile cards latest-first', function () {
    $user = buildPVUser(100);
    $viewerA = buildPVProfile(201, matriId: 'AM000201');
    $viewerB = buildPVProfile(202, matriId: 'AM000202');

    $rows = new \Illuminate\Support\Collection([
        buildPVRow(viewer: $viewerA, viewed: $user->profile, when: Carbon::parse('2026-04-26 10:00:00')),
        buildPVRow(viewer: $viewerB, viewed: $user->profile, when: Carbon::parse('2026-04-26 11:00:00')),
    ]);
    // Pre-built paginator simulates the controller's "latest-first" query.
    $paginator = new LengthAwarePaginator($rows, 2, 20, 1);

    $response = buildPVControllerWithPremium(premium: true, totalCount: 2, viewedByPaginator: $paginator)
        ->index(pvRequest($user));
    $payload = $response->getData(true);

    expect($payload['data']['is_premium'])->toBeTrue();
    expect($payload['data']['total_count'])->toBe(2);
    expect($payload['data']['viewers'])->toHaveCount(2);
    expect($payload['data']['viewers'][0]['matri_id'])->toBe('AM000201');
    expect($payload['data']['viewers'][1]['matri_id'])->toBe('AM000202');
    expect($payload['meta']['total'])->toBe(2);
});

it('viewed_by handles ProfileView rows whose viewerProfile relation is null (deleted profile)', function () {
    $user = buildPVUser(100);
    $stillThere = buildPVProfile(201, matriId: 'AM000201');

    $rows = new \Illuminate\Support\Collection([
        buildPVRow(viewer: $stillThere, viewed: $user->profile),
        buildPVRow(viewer: null, viewed: $user->profile),  // viewer was deleted
    ]);
    $paginator = new LengthAwarePaginator($rows, 2, 20, 1);

    $response = buildPVControllerWithPremium(premium: true, totalCount: 2, viewedByPaginator: $paginator)
        ->index(pvRequest($user));

    // Only the existing viewer is rendered; the null one is silently dropped.
    expect($response->getData(true)['data']['viewers'])->toHaveCount(1);
});

/* ==================================================================
 |  tab=i_viewed — always available
 | ================================================================== */

it('i_viewed returns paginated profiles the viewer has viewed', function () {
    $user = buildPVUser(100);
    $a = buildPVProfile(201, matriId: 'AM000201');
    $b = buildPVProfile(202, matriId: 'AM000202');

    $rows = new \Illuminate\Support\Collection([
        buildPVRow(viewer: $user->profile, viewed: $a, when: Carbon::parse('2026-04-26 10:00:00')),
        buildPVRow(viewer: $user->profile, viewed: $b, when: Carbon::parse('2026-04-26 11:00:00')),
    ]);
    $paginator = new LengthAwarePaginator($rows, 2, 20, 1);

    $response = buildPVControllerWithPremium(premium: false, iViewedPaginator: $paginator)
        ->index(pvRequest($user, query: ['tab' => 'i_viewed']));
    $payload = $response->getData(true);

    expect($payload['data']['tab'])->toBe('i_viewed');
    expect($payload['data']['viewed_profiles'])->toHaveCount(2);
    expect($payload['data']['viewed_profiles'][0]['matri_id'])->toBe('AM000201');
});

it('i_viewed is always available — free user gets the full list', function () {
    $user = buildPVUser(100);
    $target = buildPVProfile(201, matriId: 'AM000201');
    $rows = new \Illuminate\Support\Collection([
        buildPVRow(viewer: $user->profile, viewed: $target),
    ]);
    $paginator = new LengthAwarePaginator($rows, 1, 20, 1);

    // Free user — no premium gate on i_viewed.
    $response = buildPVControllerWithPremium(premium: false, iViewedPaginator: $paginator)
        ->index(pvRequest($user, query: ['tab' => 'i_viewed']));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['viewed_profiles'])->toHaveCount(1);
});

it('invalid tab defaults to viewed_by', function () {
    $user = buildPVUser(100);

    $response = buildPVControllerWithPremium(premium: false, totalCount: 0)
        ->index(pvRequest($user, query: ['tab' => 'random-junk']));

    expect($response->getData(true)['data']['tab'])->toBe('viewed_by');
});

it('viewed_by total_count uses real profile_views table count', function () {
    $user = buildPVUser(100);
    // Insert 3 rows where viewer's profile is the viewed party.
    ProfileView::create(['viewer_profile_id' => 201, 'viewed_profile_id' => $user->profile->id, 'viewed_at' => now()]);
    ProfileView::create(['viewer_profile_id' => 202, 'viewed_profile_id' => $user->profile->id, 'viewed_at' => now()]);
    ProfileView::create(['viewer_profile_id' => 203, 'viewed_profile_id' => $user->profile->id, 'viewed_at' => now()]);
    ProfileView::create(['viewer_profile_id' => 204, 'viewed_profile_id' => 999, 'viewed_at' => now()]);  // not the viewer

    // Use the REAL controller (not the seam-overridden subclass) so
    // countViewedBy hits the actual profile_views table. We still need
    // to override resolvePremium to keep User::isPremium() out of the
    // path — the real controller's premium check would hit
    // user_memberships which we haven't set up.
    $controller = new class extends ProfileViewController {
        protected function resolvePremium($user): bool { return false; }
    };

    $response = $controller->index(pvRequest($user));

    expect($response->getData(true)['data']['total_count'])->toBe(3);
});
