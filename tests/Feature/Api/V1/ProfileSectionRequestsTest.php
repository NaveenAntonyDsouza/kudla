<?php

use App\Http\Requests\Api\V1\Profile\UpdateContactSectionRequest;
use App\Http\Requests\Api\V1\Profile\UpdateEducationSectionRequest;
use App\Http\Requests\Api\V1\Profile\UpdateFamilySectionRequest;
use App\Http\Requests\Api\V1\Profile\UpdateHobbiesSectionRequest;
use App\Http\Requests\Api\V1\Profile\UpdateLocationSectionRequest;
use App\Http\Requests\Api\V1\Profile\UpdatePartnerSectionRequest;
use App\Http\Requests\Api\V1\Profile\UpdatePrimarySectionRequest;
use App\Http\Requests\Api\V1\Profile\UpdateReligiousSectionRequest;
use App\Http\Requests\Api\V1\Profile\UpdateSocialSectionRequest;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| FormRequest validation rules — 9 section updaters
|--------------------------------------------------------------------------
| Pure-PHP tests that instantiate each FormRequest, pull rules() via the
| public contract, and run the Validator facade against sample payloads.
| No HTTP, no DB, no FormRequest lifecycle magic — just the rule matrix.
|
| We DON'T re-test every single field rule here (that would be noisy).
| We DO lock the policy-level invariants:
|   - which fields are required
|   - which type constraints actually bite
|   - cross-field rules (gte / after_or_equal / required_if)
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

/** Helper: does this payload pass this FormRequest's rules? */
function passes(string $frClass, array $payload): bool
{
    $rules = (new $frClass())->rules();

    return Validator::make($payload, $rules)->passes();
}

/** Helper: does this payload fail on the given field? */
function failsOn(string $frClass, array $payload, string $field): bool
{
    $rules = (new $frClass())->rules();
    $v = Validator::make($payload, $rules);

    return $v->fails() && $v->errors()->has($field);
}

/* ==================================================================
 |  Primary
 | ================================================================== */

it('primary rejects payload missing mother_tongue', function () {
    expect(failsOn(UpdatePrimarySectionRequest::class, [], 'mother_tongue'))->toBeTrue();
});

it('primary accepts minimal valid payload (mother_tongue only)', function () {
    expect(passes(UpdatePrimarySectionRequest::class, [
        'mother_tongue' => 'Kannada',
    ]))->toBeTrue();
});

it('primary caps about_me at 5000 chars', function () {
    expect(failsOn(UpdatePrimarySectionRequest::class, [
        'mother_tongue' => 'Kannada',
        'about_me' => str_repeat('x', 5001),
    ], 'about_me'))->toBeTrue();
});

it('primary accepts languages_known as array of strings', function () {
    expect(passes(UpdatePrimarySectionRequest::class, [
        'mother_tongue' => 'Kannada',
        'languages_known' => ['English', 'Hindi'],
    ]))->toBeTrue();
});

/* ==================================================================
 |  Religious
 | ================================================================== */

it('religious rejects payload missing religion', function () {
    expect(failsOn(UpdateReligiousSectionRequest::class, [], 'religion'))->toBeTrue();
});

it('religious requires caste when religion=Hindu', function () {
    expect(failsOn(UpdateReligiousSectionRequest::class, [
        'religion' => 'Hindu',
    ], 'caste'))->toBeTrue();
});

it('religious requires denomination when religion=Christian', function () {
    expect(failsOn(UpdateReligiousSectionRequest::class, [
        'religion' => 'Christian',
    ], 'denomination'))->toBeTrue();
});

it('religious requires muslim_sect when religion=Muslim', function () {
    expect(failsOn(UpdateReligiousSectionRequest::class, [
        'religion' => 'Muslim',
    ], 'muslim_sect'))->toBeTrue();
});

it('religious accepts Hindu + caste', function () {
    expect(passes(UpdateReligiousSectionRequest::class, [
        'religion' => 'Hindu',
        'caste' => 'Brahmin',
    ]))->toBeTrue();
});

/* ==================================================================
 |  Education + Family (no required fields — full-optional sections)
 | ================================================================== */

it('education accepts fully empty payload', function () {
    expect(passes(UpdateEducationSectionRequest::class, []))->toBeTrue();
});

it('family rejects negative sibling counts', function () {
    expect(failsOn(UpdateFamilySectionRequest::class, [
        'brothers_married' => -1,
    ], 'brothers_married'))->toBeTrue();
});

/* ==================================================================
 |  Location (date range cross-field)
 | ================================================================== */

it('location accepts valid outstation date range', function () {
    expect(passes(UpdateLocationSectionRequest::class, [
        'outstation_leave_date_from' => '2026-05-01',
        'outstation_leave_date_to' => '2026-05-10',
    ]))->toBeTrue();
});

it('location rejects inverted outstation date range', function () {
    expect(failsOn(UpdateLocationSectionRequest::class, [
        'outstation_leave_date_from' => '2026-05-10',
        'outstation_leave_date_to' => '2026-05-01',
    ], 'outstation_leave_date_to'))->toBeTrue();
});

/* ==================================================================
 |  Contact
 | ================================================================== */

it('contact rejects invalid alternate_email', function () {
    expect(failsOn(UpdateContactSectionRequest::class, [
        'alternate_email' => 'not-an-email',
    ], 'alternate_email'))->toBeTrue();
});

it('contact silently ignores phone + email fields (not in rules)', function () {
    // Laravel's validated() drops keys not listed in rules(). This test
    // locks that: validation passes (rules don't mention phone/email),
    // and the fields would be absent from validated().
    $rules = (new UpdateContactSectionRequest())->rules();

    expect($rules)->not->toHaveKey('phone');
    expect($rules)->not->toHaveKey('email');
});

/* ==================================================================
 |  Hobbies (all optional, all arrays)
 | ================================================================== */

it('hobbies accepts fully empty payload (deselect everything)', function () {
    expect(passes(UpdateHobbiesSectionRequest::class, []))->toBeTrue();
});

it('hobbies accepts array-of-strings for each field', function () {
    expect(passes(UpdateHobbiesSectionRequest::class, [
        'hobbies' => ['Reading', 'Cooking'],
        'favorite_music' => ['Classical', 'Rock'],
    ]))->toBeTrue();
});

/* ==================================================================
 |  Social (5 URLs)
 | ================================================================== */

it('social rejects non-URL facebook_url', function () {
    expect(failsOn(UpdateSocialSectionRequest::class, [
        'facebook_url' => 'not a url',
    ], 'facebook_url'))->toBeTrue();
});

it('social accepts valid URL set', function () {
    expect(passes(UpdateSocialSectionRequest::class, [
        'facebook_url' => 'https://facebook.com/johndoe',
        'linkedin_url' => 'https://linkedin.com/in/johndoe',
    ]))->toBeTrue();
});

/* ==================================================================
 |  Partner (the heaviest section)
 | ================================================================== */

it('partner rejects age_to < age_from', function () {
    expect(failsOn(UpdatePartnerSectionRequest::class, [
        'age_from' => 30,
        'age_to' => 25,
    ], 'age_to'))->toBeTrue();
});

it('partner rejects height_to_cm < height_from_cm', function () {
    expect(failsOn(UpdatePartnerSectionRequest::class, [
        'height_from_cm' => 170,
        'height_to_cm' => 160,
    ], 'height_to_cm'))->toBeTrue();
});

it('partner rejects age_from below 18', function () {
    expect(failsOn(UpdatePartnerSectionRequest::class, [
        'age_from' => 15,
    ], 'age_from'))->toBeTrue();
});

it('partner accepts valid age + height + arrays', function () {
    expect(passes(UpdatePartnerSectionRequest::class, [
        'age_from' => 25,
        'age_to' => 32,
        'height_from_cm' => 160,
        'height_to_cm' => 180,
        'religions' => ['Hindu', 'Jain'],
        'mother_tongues' => ['Kannada', 'Tamil'],
    ]))->toBeTrue();
});

it('partner caps about_partner at 5000 chars', function () {
    expect(failsOn(UpdatePartnerSectionRequest::class, [
        'about_partner' => str_repeat('x', 5001),
    ], 'about_partner'))->toBeTrue();
});
