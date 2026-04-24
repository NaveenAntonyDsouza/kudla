<?php

use App\Http\Controllers\Api\V1\ProfileController;
use App\Models\Profile;
use App\Models\User;
use App\Services\MatchingService;
use App\Services\ProfileAccessService;
use App\Services\ProfileViewService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| GET /api/v1/profiles/{matriId} — 7 gates + viewer context
|--------------------------------------------------------------------------
| Tests the controller directly (no HTTP, no DB). An anonymous subclass
| of ProfileController overrides the `findTargetByMatriId` seam so tests
| can supply pre-built in-memory target profiles without hitting the
| `profiles` table (which the SQLite :memory: test DB lacks).
|
| Gate outcomes under test:
|   REASON_SAME_GENDER      → 403 GENDER_MISMATCH
|   REASON_SUSPENDED        → 404 NOT_FOUND      (anti-enumeration)
|   REASON_HIDDEN           → 404 NOT_FOUND      (anti-enumeration)
|   REASON_VISIBILITY_PREMIUM → 403 PREMIUM_REQUIRED
|   REASON_OK               → 200 happy path + viewer_context fields
|   REASON_SELF             → 200 self-view (includeContact=true, context=null)
|
| Missing target / missing viewer profile produce their own errors
| before the gate is consulted.
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

/** Factory: User with profile attached (registration complete). */
function buildShowUser(array $overrides = []): User
{
    $user = new User();
    $user->exists = true;
    $user->forceFill(array_merge([
        'id' => 8800,
        'email' => 'viewer@example.com',
        'phone' => '9870000000',
        'last_login_at' => Carbon::parse('2026-04-23 09:00:00'),
        'is_active' => true,
    ], $overrides));
    $user->setRelation('userMemberships', new \Illuminate\Database\Eloquent\Collection());

    return $user;
}

/** Factory: Profile with every ProfileResource relation pre-set (DB-free). */
function buildShowProfile(User $user, array $overrides = []): Profile
{
    $profile = new Profile();
    $profile->exists = true;
    $profile->forceFill(array_merge([
        'id' => 8800,
        'user_id' => $user->id,
        'matri_id' => 'AM880088',
        'full_name' => 'Viewer Test',
        'gender' => 'male',
        'date_of_birth' => Carbon::parse('1993-01-10'),
        'marital_status' => 'Never Married',
        'profile_completion_pct' => 82,
        'is_approved' => true,
        'is_active' => true,
        'is_hidden' => false,
        'is_verified' => false,
        'is_vip' => false,
        'is_featured' => false,
        'suspension_status' => 'active',
        'show_profile_to' => 'all',
        'created_at' => Carbon::parse('2026-03-15 10:00:00'),
    ], $overrides));

    $profile->setRelation('user', $user);
    foreach ([
        'religiousInfo', 'educationDetail', 'familyDetail',
        'locationInfo', 'contactInfo', 'lifestyleInfo',
        'partnerPreference', 'socialMediaLink', 'photoPrivacySetting',
    ] as $rel) {
        $profile->setRelation($rel, null);
    }
    $profile->setRelation('profilePhotos', new \Illuminate\Database\Eloquent\Collection());
    $user->setRelation('profile', $profile);

    return $profile;
}

/**
 * Build a ProfileController that returns $target for any matri_id matching
 * $target->matri_id. Everything else (null target) → 404 path.
 */
function buildShowController(?Profile $target = null): ProfileController
{
    return new class(
        app(ProfileAccessService::class),
        app(MatchingService::class),
        app(ProfileViewService::class),
        $target,
    ) extends ProfileController {
        public function __construct(
            ProfileAccessService $access,
            MatchingService $matching,
            ProfileViewService $viewer,
            private ?Profile $stubbedTarget,
        ) {
            parent::__construct($access, $matching, $viewer);
        }

        protected function findTargetByMatriId(string $matriId): ?Profile
        {
            return $this->stubbedTarget && $this->stubbedTarget->matri_id === $matriId
                ? $this->stubbedTarget
                : null;
        }
    };
}

/** Dispatch show() with the given viewer + stubbed target + matriId param. */
function callShow(User $viewer, ?Profile $target, string $matriId): array
{
    $request = Request::create("/api/v1/profiles/{$matriId}", 'GET');
    $request->setUserResolver(fn () => $viewer);

    $controller = buildShowController($target);
    $response = $controller->show($request, $matriId);

    return [
        'status' => $response->getStatusCode(),
        'body' => $response->getData(true),
    ];
}

/* ==================================================================
 |  Pre-gate failures (target/viewer missing)
 | ================================================================== */

it('returns 404 NOT_FOUND when matri_id does not exist', function () {
    $viewer = buildShowUser();
    buildShowProfile($viewer);

    $result = callShow($viewer, null, 'AM999999');

    expect($result['status'])->toBe(404);
    expect($result['body']['success'])->toBeFalse();
    expect($result['body']['error']['code'])->toBe('NOT_FOUND');
    expect($result['body']['error']['message'])->toBe('Profile not available.');
});

it('returns 422 PROFILE_REQUIRED when viewer has not completed registration', function () {
    $viewer = buildShowUser();
    $viewer->setRelation('profile', null);  // no viewer profile

    // Target must exist to get past the find step.
    $targetUser = buildShowUser(['id' => 8801, 'email' => 't@e.com']);
    $target = buildShowProfile($targetUser, ['id' => 8801, 'matri_id' => 'AM880089', 'gender' => 'female']);

    $result = callShow($viewer, $target, 'AM880089');

    expect($result['status'])->toBe(422);
    expect($result['body']['error']['code'])->toBe('PROFILE_REQUIRED');
});

/* ==================================================================
 |  Gate failures
 | ================================================================== */

it('returns 403 GENDER_MISMATCH for same-gender view', function () {
    $viewer = buildShowUser();
    buildShowProfile($viewer, ['gender' => 'male']);

    $targetUser = buildShowUser(['id' => 8801]);
    $target = buildShowProfile($targetUser, ['id' => 8801, 'matri_id' => 'AM880089', 'gender' => 'male']);

    $result = callShow($viewer, $target, 'AM880089');

    expect($result['status'])->toBe(403);
    expect($result['body']['error']['code'])->toBe('GENDER_MISMATCH');
});

it('returns 404 NOT_FOUND when target is suspended', function () {
    $viewer = buildShowUser();
    buildShowProfile($viewer, ['gender' => 'male']);

    $targetUser = buildShowUser(['id' => 8801]);
    $target = buildShowProfile($targetUser, [
        'id' => 8801, 'matri_id' => 'AM880089', 'gender' => 'female',
        'suspension_status' => 'suspended',
    ]);

    $result = callShow($viewer, $target, 'AM880089');

    expect($result['status'])->toBe(404);
    expect($result['body']['error']['code'])->toBe('NOT_FOUND');
});

it('returns 404 NOT_FOUND when target is hidden and no prior interest', function () {
    $viewer = buildShowUser();
    buildShowProfile($viewer, ['gender' => 'male']);

    $targetUser = buildShowUser(['id' => 8801]);
    $target = buildShowProfile($targetUser, [
        'id' => 8801, 'matri_id' => 'AM880089', 'gender' => 'female',
        'is_hidden' => true,
    ]);

    $result = callShow($viewer, $target, 'AM880089');

    expect($result['status'])->toBe(404);
    expect($result['body']['error']['code'])->toBe('NOT_FOUND');
});

it('returns 403 PREMIUM_REQUIRED when target show_profile_to=premium and viewer not premium', function () {
    $viewer = buildShowUser();
    buildShowProfile($viewer, ['gender' => 'male']);

    $targetUser = buildShowUser(['id' => 8801]);
    $target = buildShowProfile($targetUser, [
        'id' => 8801, 'matri_id' => 'AM880089', 'gender' => 'female',
        'show_profile_to' => 'premium',
    ]);

    $result = callShow($viewer, $target, 'AM880089');

    expect($result['status'])->toBe(403);
    expect($result['body']['error']['code'])->toBe('PREMIUM_REQUIRED');
});

it('returns 403 LOW_MATCH_SCORE when target show_profile_to=matches and viewer has no preferences', function () {
    $viewer = buildShowUser();
    // Viewer has no partnerPreference → matchScoreAbove returns false → 403 LOW_MATCH_SCORE
    buildShowProfile($viewer, ['gender' => 'male']);

    $targetUser = buildShowUser(['id' => 8801]);
    $target = buildShowProfile($targetUser, [
        'id' => 8801, 'matri_id' => 'AM880089', 'gender' => 'female',
        'show_profile_to' => 'matches',
    ]);

    $result = callShow($viewer, $target, 'AM880089');

    expect($result['status'])->toBe(403);
    expect($result['body']['error']['code'])->toBe('LOW_MATCH_SCORE');
});

/* ==================================================================
 |  Happy path — REASON_OK
 | ================================================================== */

it('returns 200 envelope for clean opposite-gender view', function () {
    $viewer = buildShowUser();
    buildShowProfile($viewer, ['gender' => 'male']);

    $targetUser = buildShowUser(['id' => 8801, 'email' => 'target@example.com']);
    $target = buildShowProfile($targetUser, [
        'id' => 8801, 'matri_id' => 'AM880089', 'gender' => 'female',
    ]);

    $result = callShow($viewer, $target, 'AM880089');

    expect($result['status'])->toBe(200);
    expect($result['body']['success'])->toBeTrue();
    expect($result['body']['data'])->toHaveKeys([
        'profile', 'match_score', 'interest_status',
        'is_shortlisted', 'is_blocked', 'photo_request_status',
        'can_view_contact',
    ]);
});

it('happy path profile has full 19-key + 9-section shape', function () {
    $viewer = buildShowUser();
    buildShowProfile($viewer, ['gender' => 'male']);

    $targetUser = buildShowUser(['id' => 8801]);
    $target = buildShowProfile($targetUser, [
        'id' => 8801, 'matri_id' => 'AM880089', 'gender' => 'female',
    ]);

    $result = callShow($viewer, $target, 'AM880089');
    $profile = $result['body']['data']['profile'];

    expect($profile)->toHaveKeys([
        'matri_id', 'full_name', 'gender', 'age', 'is_premium',
        'sections', 'photos',
    ]);
    expect($profile['sections'])->toHaveKeys([
        'primary', 'religious', 'education', 'family',
        'location', 'contact', 'hobbies', 'social', 'partner',
    ]);
});

it('hides contact (sections.contact=null) when viewer not premium', function () {
    $viewer = buildShowUser();
    buildShowProfile($viewer, ['gender' => 'male']);

    $targetUser = buildShowUser(['id' => 8801]);
    $target = buildShowProfile($targetUser, [
        'id' => 8801, 'matri_id' => 'AM880089', 'gender' => 'female',
    ]);

    $result = callShow($viewer, $target, 'AM880089');

    expect($result['body']['data']['can_view_contact'])->toBeFalse();
    expect($result['body']['data']['profile']['sections']['contact'])->toBeNull();
});

it('match_score is null when viewer has no partner_preference', function () {
    $viewer = buildShowUser();
    buildShowProfile($viewer, ['gender' => 'male']);  // partnerPreference already set to null

    $targetUser = buildShowUser(['id' => 8801]);
    $target = buildShowProfile($targetUser, [
        'id' => 8801, 'matri_id' => 'AM880089', 'gender' => 'female',
    ]);

    $result = callShow($viewer, $target, 'AM880089');

    expect($result['body']['data']['match_score'])->toBeNull();
});

it('defensive fields default sensibly when underlying tables are missing', function () {
    $viewer = buildShowUser();
    buildShowProfile($viewer, ['gender' => 'male']);

    $targetUser = buildShowUser(['id' => 8801]);
    $target = buildShowProfile($targetUser, [
        'id' => 8801, 'matri_id' => 'AM880089', 'gender' => 'female',
    ]);

    $result = callShow($viewer, $target, 'AM880089');
    $data = $result['body']['data'];

    // No interests/shortlists/photo_requests tables in test DB → safe fallbacks
    expect($data['interest_status'])->toBeNull();
    expect($data['is_shortlisted'])->toBeFalse();
    expect($data['photo_request_status'])->toBeNull();
    expect($data['is_blocked'])->toBeFalse();  // always false on happy path
});

/* ==================================================================
 |  REASON_SELF — viewing own profile via /profiles/{my-matri-id}
 | ================================================================== */

it('self-view (viewer.id === target.id) returns 200 with contact populated', function () {
    $viewer = buildShowUser();
    $profile = buildShowProfile($viewer);

    // Same profile instance serves as both viewer.profile AND the lookup target.
    $result = callShow($viewer, $profile, $profile->matri_id);

    expect($result['status'])->toBe(200);
    expect($result['body']['data']['can_view_contact'])->toBeTrue();
    expect($result['body']['data']['profile']['sections']['contact'])->toBeArray();
});

it('self-view zeroes out viewer_context fields (no match_score vs self, etc.)', function () {
    $viewer = buildShowUser();
    $profile = buildShowProfile($viewer);

    $result = callShow($viewer, $profile, $profile->matri_id);
    $data = $result['body']['data'];

    expect($data['match_score'])->toBeNull();
    expect($data['interest_status'])->toBeNull();
    expect($data['is_shortlisted'])->toBeFalse();
    expect($data['is_blocked'])->toBeFalse();
    expect($data['photo_request_status'])->toBeNull();
});
