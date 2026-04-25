<?php

use App\Http\Resources\V1\ProfileCardResource;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| ProfileCardResource — shape contract
|--------------------------------------------------------------------------
| Verifies the UI-safe API checklist against ProfileCardResource:
|   1. Timestamps → ISO 8601 string or null (last_active_at)
|   2. Booleans   → real bool (is_shortlisted, is_blocked)
|   3. Arrays     → [] when empty (badges)
|   4. Optional   → always present with null (match_score, interest_status, etc.)
|   5. Photo URLs → delegated to PhotoResource (verified in its own test)
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
|
| These tests use in-memory Eloquent instances + setRelation() so they
| don't need a DB. Pure shape-transformation verification.
*/

/** Build an in-memory Profile with all the relations a ProfileCardResource reads. */
function buildCardProfile(array $overrides = []): Profile
{
    $user = new User();
    $user->exists = true;
    $user->forceFill(array_merge([
        'id' => 42,
        'email' => 'card-test@example.com',
        'phone' => '9876500000',
        'last_login_at' => Carbon::parse('2026-04-20 10:00:00'),
        'is_active' => true,
    ], $overrides['user'] ?? []));

    $profile = new Profile();
    $profile->exists = true;
    $profile->forceFill(array_merge([
        'id' => 100,
        'matri_id' => 'AM100042',
        'full_name' => 'Test User',
        'gender' => 'male',
        'date_of_birth' => Carbon::parse('1995-04-12'),
        'height' => '170 cm - 5 ft 07 inch',
        'is_approved' => true,
        'is_verified' => true,
        'is_vip' => false,
        'is_featured' => false,
        'is_active' => true,
        'created_at' => Carbon::now()->subDays(3),
    ], $overrides['profile'] ?? []));

    $profile->setRelation('user', $user);
    $profile->setRelation('religiousInfo', null);
    $profile->setRelation('educationDetail', null);
    $profile->setRelation('locationInfo', null);
    $profile->setRelation('primaryPhoto', null);

    return $profile;
}

it('returns envelope with all 19 keys', function () {
    $profile = buildCardProfile();
    $data = (new ProfileCardResource($profile))->resolve();

    // All 19 keys must be present — no conditional inclusion. match_badge
    // was added in week-3 step-15; defaults null for cards rendered
    // outside a scoring context (search/discover/dashboard).
    $expectedKeys = [
        'matri_id', 'full_name', 'age', 'height_cm', 'height_label',
        'religion', 'caste', 'native_state', 'occupation', 'education_short',
        'primary_photo', 'badges', 'last_active_at', 'last_active_label',
        'match_score', 'match_badge', 'is_shortlisted', 'interest_status', 'is_blocked',
    ];

    foreach ($expectedKeys as $key) {
        expect($data)->toHaveKey($key);
    }
});

it('match_score reads in-memory attribute when MatchingService set it', function () {
    $profile = buildCardProfile();
    $profile->setAttribute('match_score', 87);  // simulate MatchingService dynamic attr

    $data = (new ProfileCardResource($profile))->resolve();

    expect($data['match_score'])->toBe(87);
});

it('match_badge reads in-memory attribute when MatchingService set it', function () {
    $profile = buildCardProfile();
    $profile->setAttribute('match_badge', 'great');

    $data = (new ProfileCardResource($profile))->resolve();

    expect($data['match_badge'])->toBe('great');
});

it('match_badge defaults to null when MatchingService has not scored', function () {
    $profile = buildCardProfile();

    $data = (new ProfileCardResource($profile))->resolve();

    expect($data['match_badge'])->toBeNull();
});

it('returns age as integer when date_of_birth is set', function () {
    $profile = buildCardProfile();
    $data = (new ProfileCardResource($profile))->resolve();

    expect($data['age'])->toBeInt()->toBeGreaterThan(18);
});

it('returns age as null when date_of_birth is missing', function () {
    $profile = buildCardProfile(['profile' => ['date_of_birth' => null]]);
    $data = (new ProfileCardResource($profile))->resolve();

    expect($data['age'])->toBeNull();
});

it('parses height_cm as integer from height string', function () {
    $profile = buildCardProfile();
    $data = (new ProfileCardResource($profile))->resolve();

    expect($data['height_cm'])->toBe(170);
});

it('returns height_cm as null when height is missing', function () {
    $profile = buildCardProfile(['profile' => ['height' => null]]);
    $data = (new ProfileCardResource($profile))->resolve();

    expect($data['height_cm'])->toBeNull();
    expect($data['height_label'])->toBeNull();
});

it('returns badges as array (never null)', function () {
    $profile = buildCardProfile();
    $data = (new ProfileCardResource($profile))->resolve();

    expect($data['badges'])->toBeArray();
});

it('returns empty badges array for plain profile', function () {
    $profile = buildCardProfile([
        'profile' => [
            'is_verified' => false,
            'is_vip' => false,
            'is_featured' => false,
            'created_at' => Carbon::now()->subMonths(3),  // not "new"
        ],
    ]);
    $data = (new ProfileCardResource($profile))->resolve();

    expect($data['badges'])->toBeArray();
    expect($data['badges'])->toBeEmpty();
});

it('includes verified + new badges when flags match', function () {
    $profile = buildCardProfile([
        'profile' => [
            'is_verified' => true,
            'created_at' => Carbon::now()->subDays(2),
        ],
    ]);
    $data = (new ProfileCardResource($profile))->resolve();

    expect($data['badges'])->toContain('verified');
    expect($data['badges'])->toContain('new');
});

it('returns ISO 8601 last_active_at when user has last_login_at', function () {
    $profile = buildCardProfile();
    $data = (new ProfileCardResource($profile))->resolve();

    expect($data['last_active_at'])->toBeString();
    expect($data['last_active_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});

it('returns null last_active_at when user never logged in', function () {
    $profile = buildCardProfile(['user' => ['last_login_at' => null]]);
    $data = (new ProfileCardResource($profile))->resolve();

    expect($data['last_active_at'])->toBeNull();
    expect($data['last_active_label'])->toBeNull();
});

it('returns is_shortlisted as real bool when no viewer', function () {
    $profile = buildCardProfile();
    $data = (new ProfileCardResource($profile))->resolve();

    expect($data['is_shortlisted'])->toBeBool();
    expect($data['is_shortlisted'])->toBeFalse();
});

it('returns match_score and interest_status as null when no viewer', function () {
    $profile = buildCardProfile();
    $data = (new ProfileCardResource($profile))->resolve();

    expect($data['match_score'])->toBeNull();
    expect($data['interest_status'])->toBeNull();
});

it('returns primary_photo as null when profile has no primary photo', function () {
    $profile = buildCardProfile();
    $data = (new ProfileCardResource($profile))->resolve();

    expect($data['primary_photo'])->toBeNull();
});
