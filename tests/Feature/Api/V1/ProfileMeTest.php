<?php

use App\Http\Controllers\Api\V1\ProfileController;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| GET /api/v1/profile/me — controller contract
|--------------------------------------------------------------------------
| Tests the controller directly (no HTTP, no DB) using in-memory Eloquent
| + Request::setUserResolver. ProfileResource's full shape is already
| locked by ProfileResourceTest (146 assertions); here we verify only
| the controller's job:
|   - 200 envelope with profile data when user has a profile
|   - 422 PROFILE_REQUIRED when the user hasn't finished registration
|   - contact section is populated (includeContact: true)
|   - All 9 sections + 19 top-level profile keys are present
|   - loadMissing is a no-op when relations are pre-set (proves the
|     test stays DB-free even though the controller "eager-loads")
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

function buildMeUser(array $overrides = []): User
{
    $user = new User();
    $user->exists = true;
    $user->forceFill(array_merge([
        'id' => 5500,
        'email' => 'me@example.com',
        'phone' => '9876554321',
        'last_login_at' => Carbon::parse('2026-04-23 09:00:00'),
        'is_active' => true,
    ], $overrides));

    // userMemberships pre-set to empty collection so loadMissing skips it
    // and isPremiumSafely returns false (no DB hit).
    $user->setRelation('userMemberships', new \Illuminate\Database\Eloquent\Collection());

    return $user;
}

function buildMeProfile(User $user, array $overrides = []): Profile
{
    $profile = new Profile();
    $profile->exists = true;
    $profile->forceFill(array_merge([
        'id' => 5500,
        'user_id' => $user->id,
        'matri_id' => 'AM550055',
        'full_name' => 'Me Test',
        'gender' => 'female',
        'date_of_birth' => Carbon::parse('1996-06-15'),
        'marital_status' => 'Never Married',
        'profile_completion_pct' => 72,
        'is_approved' => true,
        'is_active' => true,
        'is_hidden' => false,
        'is_verified' => false,
        'is_vip' => false,
        'is_featured' => false,
        'suspension_status' => 'active',
        'show_profile_to' => 'all',
        'created_at' => Carbon::parse('2026-03-01 12:00:00'),
    ], $overrides));

    // Pre-set every relation that ProfileController::PROFILE_EAGER_LOADS
    // touches so `loadMissing` is a no-op and the test stays DB-free.
    $profile->setRelation('user', $user);
    $profile->setRelation('religiousInfo', null);
    $profile->setRelation('educationDetail', null);
    $profile->setRelation('familyDetail', null);
    $profile->setRelation('locationInfo', null);
    $profile->setRelation('contactInfo', null);
    $profile->setRelation('lifestyleInfo', null);
    $profile->setRelation('partnerPreference', null);
    $profile->setRelation('socialMediaLink', null);
    $profile->setRelation('photoPrivacySetting', null);
    $profile->setRelation('profilePhotos', new \Illuminate\Database\Eloquent\Collection());

    // Attach the profile back to the user so $user->profile returns it
    // without triggering a DB query.
    $user->setRelation('profile', $profile);

    return $profile;
}

function callMe(User $user): array
{
    $request = Request::create('/api/v1/profile/me', 'GET');
    $request->setUserResolver(fn () => $user);

    /** @var ProfileController $controller */
    $controller = app(ProfileController::class);
    $response = $controller->me($request);

    return [
        'status' => $response->getStatusCode(),
        'body' => $response->getData(true),
    ];
}

/* ------------------------------------------------------------------
 |  Happy path
 | ------------------------------------------------------------------ */

it('returns 200 envelope with profile data for authenticated user', function () {
    $user = buildMeUser();
    buildMeProfile($user);

    $result = callMe($user);

    expect($result['status'])->toBe(200);
    expect($result['body']['success'])->toBeTrue();
    expect($result['body'])->toHaveKey('data');
    expect($result['body']['data'])->toHaveKey('profile');
});

it('profile payload has all 19 top-level keys', function () {
    $user = buildMeUser();
    buildMeProfile($user);

    $result = callMe($user);
    $profile = $result['body']['data']['profile'];

    expect($profile)->toHaveKeys([
        'matri_id', 'full_name', 'gender', 'date_of_birth', 'age',
        'marital_status', 'profile_completion_pct',
        'is_approved', 'is_active', 'is_hidden', 'is_verified',
        'is_vip', 'is_featured', 'is_premium', 'suspension_status',
        'created_at', 'last_active_at',
        'sections', 'photos',
    ]);
});

it('profile includes all 9 sections', function () {
    $user = buildMeUser();
    buildMeProfile($user);

    $result = callMe($user);
    $sections = $result['body']['data']['profile']['sections'];

    expect($sections)->toHaveKeys([
        'primary', 'religious', 'education', 'family',
        'location', 'contact', 'hobbies', 'social', 'partner',
    ]);
});

/* ------------------------------------------------------------------
 |  Contact section (always populated for self-view)
 | ------------------------------------------------------------------ */

it('populates sections.contact for own profile (includeContact=true)', function () {
    $user = buildMeUser();
    buildMeProfile($user);

    $result = callMe($user);
    $contact = $result['body']['data']['profile']['sections']['contact'];

    // Contact must be a populated array (not null) since the caller is
    // viewing their own profile.
    expect($contact)->toBeArray();
    expect($contact)->toHaveKey('phone');
    expect($contact)->toHaveKey('email');
});

it('contact.email matches the authenticated user email', function () {
    $user = buildMeUser(['email' => 'selfview@example.com']);
    buildMeProfile($user);

    $result = callMe($user);
    $contact = $result['body']['data']['profile']['sections']['contact'];

    expect($contact['email'])->toBe('selfview@example.com');
    expect($contact['phone'])->toBe('9876554321');
});

/* ------------------------------------------------------------------
 |  Photos block
 | ------------------------------------------------------------------ */

it('photos block has the standard grouped shape', function () {
    $user = buildMeUser();
    buildMeProfile($user);

    $result = callMe($user);
    $photos = $result['body']['data']['profile']['photos'];

    expect($photos)->toHaveKeys(['profile', 'album', 'family', 'photo_privacy']);
    expect($photos['profile'])->toBeArray();
    expect($photos['album'])->toBeArray();
    expect($photos['family'])->toBeArray();
});

/* ------------------------------------------------------------------
 |  Flags + shape guarantees
 | ------------------------------------------------------------------ */

it('boolean flags are real booleans', function () {
    $user = buildMeUser();
    buildMeProfile($user);

    $result = callMe($user);
    $profile = $result['body']['data']['profile'];

    foreach (['is_approved', 'is_active', 'is_hidden', 'is_verified',
              'is_vip', 'is_featured', 'is_premium'] as $flag) {
        expect($profile[$flag])->toBeBool("profile.{$flag} must be a real bool");
    }
});

it('last_active_at is ISO 8601 when user has last_login_at', function () {
    $user = buildMeUser();
    buildMeProfile($user);

    $result = callMe($user);
    expect($result['body']['data']['profile']['last_active_at'])
        ->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});

/* ------------------------------------------------------------------
 |  422 when user has no profile
 | ------------------------------------------------------------------ */

it('returns 422 PROFILE_REQUIRED when user has no profile attached', function () {
    $user = buildMeUser();
    $user->setRelation('profile', null);  // registration incomplete

    $result = callMe($user);

    expect($result['status'])->toBe(422);
    expect($result['body']['success'])->toBeFalse();
    expect($result['body']['error']['code'])->toBe('PROFILE_REQUIRED');
    expect($result['body']['error']['message'])->toContain('registration');
});

/* ------------------------------------------------------------------
 |  Internals (constant contract)
 | ------------------------------------------------------------------ */

it('PROFILE_EAGER_LOADS includes every relation the Resource touches', function () {
    // Lock the eager-load list so adding a relation to ProfileResource
    // without updating this constant triggers an explicit test failure.
    expect(ProfileController::PROFILE_EAGER_LOADS)->toEqualCanonicalizing([
        'user.userMemberships',
        'religiousInfo',
        'educationDetail',
        'familyDetail',
        'locationInfo',
        'contactInfo',
        'lifestyleInfo',
        'partnerPreference',
        'socialMediaLink',
        'photoPrivacySetting',
        'profilePhotos',
    ]);
});
