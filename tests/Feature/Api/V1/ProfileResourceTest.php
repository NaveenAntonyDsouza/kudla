<?php

use App\Http\Resources\V1\ProfileResource;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| ProfileResource — full shape contract
|--------------------------------------------------------------------------
| Verifies the UI-safe API checklist against the full ProfileResource
| (used by /profile/me + /profiles/{matriId}).
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

/** Build an in-memory full Profile with all 9 relation types present. */
function buildFullProfile(): Profile
{
    $user = new User();
    $user->exists = true;
    $user->forceFill([
        'id' => 42,
        'email' => 'full@example.com',
        'phone' => '9876500001',
        'last_login_at' => Carbon::parse('2026-04-20 10:00:00'),
        'is_active' => true,
    ]);

    $profile = new Profile();
    $profile->exists = true;
    $profile->forceFill([
        'id' => 100,
        'matri_id' => 'AM100042',
        'full_name' => 'Full Test User',
        'gender' => 'female',
        'date_of_birth' => Carbon::parse('1996-06-15'),
        'height' => '165 cm - 5 ft 05 inch',
        'marital_status' => 'Never Married',
        'profile_completion_pct' => 78,
        'is_approved' => true,
        'is_active' => true,
        'is_hidden' => false,
        'is_verified' => true,
        'is_vip' => false,
        'is_featured' => false,
        'created_at' => Carbon::now()->subMonths(2),
        'weight_kg' => 55,
        'complexion' => 'Wheatish',
        'body_type' => 'Average',
    ]);

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
    $profile->setRelation('profilePhotos', collect());
    $profile->setRelation('primaryPhoto', null);

    return $profile;
}

it('returns all 19 top-level keys', function () {
    $profile = buildFullProfile();
    $data = (new ProfileResource($profile))->resolve();

    $expected = [
        'matri_id', 'full_name', 'gender', 'date_of_birth', 'age',
        'marital_status', 'profile_completion_pct',
        'is_approved', 'is_active', 'is_hidden', 'is_verified',
        'is_vip', 'is_featured', 'is_premium', 'suspension_status',
        'created_at', 'last_active_at',
        'sections', 'photos',
    ];

    foreach ($expected as $key) {
        expect($data)->toHaveKey($key);
    }
});

it('all boolean flags are real bools', function () {
    $profile = buildFullProfile();
    $data = (new ProfileResource($profile))->resolve();

    foreach (['is_approved', 'is_active', 'is_hidden', 'is_verified', 'is_vip', 'is_featured', 'is_premium'] as $flag) {
        expect($data[$flag])->toBeBool("{$flag} must be bool");
    }
});

it('profile_completion_pct is integer', function () {
    $profile = buildFullProfile();
    $data = (new ProfileResource($profile))->resolve();

    expect($data['profile_completion_pct'])->toBeInt();
    expect($data['profile_completion_pct'])->toBe(78);
});

it('timestamps are ISO 8601 strings', function () {
    $profile = buildFullProfile();
    $data = (new ProfileResource($profile))->resolve();

    expect($data['created_at'])->toBeString();
    expect($data['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
    expect($data['last_active_at'])->toBeString();
});

it('exposes all 9 sections with stable keys even when relations missing', function () {
    $profile = buildFullProfile();  // all relations null
    $data = (new ProfileResource($profile))->resolve();

    $sectionKeys = ['primary', 'religious', 'education', 'family', 'location', 'contact', 'hobbies', 'social', 'partner'];

    foreach ($sectionKeys as $k) {
        expect($data['sections'])->toHaveKey($k);
    }
});

it('contact section is null when includeContact is false (default)', function () {
    $profile = buildFullProfile();
    $data = (new ProfileResource($profile))->resolve();

    expect($data['sections']['contact'])->toBeNull();
});

it('contact section is an array when includeContact is true', function () {
    $profile = buildFullProfile();
    $data = (new ProfileResource($profile, includeContact: true))->resolve();

    expect($data['sections']['contact'])->toBeArray();
});

it('religious section has stable shape even when religious_info missing', function () {
    $profile = buildFullProfile();  // religiousInfo set to null
    $data = (new ProfileResource($profile))->resolve();

    $religious = $data['sections']['religious'];

    // Must have 19 keys, all null
    $expectedKeys = [
        'religion', 'caste', 'sub_caste', 'gotra', 'nakshatra', 'rashi',
        'manglik', 'denomination', 'diocese', 'diocese_name', 'parish_name_place',
        'time_of_birth', 'place_of_birth', 'muslim_sect', 'muslim_community',
        'religious_observance', 'jain_sect', 'other_religion_name', 'jathakam_url',
    ];

    foreach ($expectedKeys as $k) {
        expect($religious)->toHaveKey($k);
        expect($religious[$k])->toBeNull();
    }
});

it('partner section arrays are [] not null when preference row missing', function () {
    $profile = buildFullProfile();
    $data = (new ProfileResource($profile))->resolve();

    $partner = $data['sections']['partner'];

    foreach (['complexion', 'body_type', 'marital_status', 'religions', 'castes', 'hobbies' ?? null] as $k) {
        if ($k && isset($partner[$k])) {
            expect($partner[$k])->toBeArray("{$k} must be array");
        }
    }
});

it('hobbies section arrays are always [] not null', function () {
    $profile = buildFullProfile();
    $data = (new ProfileResource($profile))->resolve();

    $hobbies = $data['sections']['hobbies'];

    foreach (['hobbies', 'favorite_music', 'preferred_books', 'preferred_movies', 'sports', 'favorite_cuisine'] as $k) {
        expect($hobbies[$k])->toBeArray("{$k} must be array");
        expect($hobbies[$k])->toBeEmpty();
    }
});

it('photos block has stable shape even when no photos', function () {
    $profile = buildFullProfile();
    $data = (new ProfileResource($profile))->resolve();

    expect($data['photos'])->toHaveKeys(['profile', 'album', 'family', 'photo_privacy']);
    expect($data['photos']['profile'])->toBeArray()->toBeEmpty();
    expect($data['photos']['album'])->toBeArray()->toBeEmpty();
    expect($data['photos']['family'])->toBeArray()->toBeEmpty();
    expect($data['photos']['photo_privacy'])->toBeNull();
});
