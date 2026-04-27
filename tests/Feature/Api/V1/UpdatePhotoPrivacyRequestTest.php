<?php

use App\Http\Requests\Api\V1\Photo\UpdatePhotoPrivacyRequest;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| UpdatePhotoPrivacyRequest — validation rule matrix
|--------------------------------------------------------------------------
| Pure-PHP tests. No HTTP, no DB. Locks:
|   - Each of the 4 privacy fields accepts only the 3-value enum
|   - Empty payload fails via the "require at least one" after() hook
|   - Null values are accepted per-field (filtered out by the controller
|     so null never overwrites a saved value)
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

/**
 * Helper — run the FormRequest's rules + after() hook against a payload.
 *
 * The after() hook now reads from `$v->getData()` rather than `$this`,
 * so we don't need to seed the FormRequest's internal bag — just pass
 * the payload directly into the Validator.
 */
function validatePrivacy(array $data): \Illuminate\Contracts\Validation\Validator
{
    $fr = new UpdatePhotoPrivacyRequest();
    $fr->setContainer(app());

    $validator = Validator::make($data, $fr->rules());
    $fr->withValidator($validator);

    return $validator;
}

function passesPrivacy(array $data): bool
{
    return validatePrivacy($data)->passes();
}

function failsPrivacyOn(array $data, string $field): bool
{
    $v = validatePrivacy($data);

    return $v->fails() && $v->errors()->has($field);
}

/* ==================================================================
 |  "Require at least one" invariant
 | ================================================================== */

it('rejects a fully empty payload', function () {
    expect(failsPrivacyOn([], 'privacy_level'))->toBeTrue();
});

it('rejects a payload where every field is null', function () {
    expect(failsPrivacyOn([
        'privacy_level' => null,
        'profile_photo_privacy' => null,
        'album_photos_privacy' => null,
        'family_photos_privacy' => null,
    ], 'privacy_level'))->toBeTrue();
});

/* ==================================================================
 |  Enum whitelist — accepts the 3 allowed values
 | ================================================================== */

it('accepts each valid enum value for privacy_level', function () {
    foreach (['visible_to_all', 'interest_accepted', 'hidden'] as $level) {
        expect(passesPrivacy(['privacy_level' => $level]))
            ->toBeTrue("privacy_level={$level} should pass");
    }
});

it('rejects invalid enum values', function () {
    // Empty string is coerced to null by `nullable` — tested separately
    // via the "require at least one" invariant, not here.
    foreach (['public', 'private', 'members_only', 'VISIBLE_TO_ALL'] as $bad) {
        expect(failsPrivacyOn(['privacy_level' => $bad], 'privacy_level'))
            ->toBeTrue("privacy_level={$bad} should fail");
    }
});

/* ==================================================================
 |  Per-type fields accept the same enum
 | ================================================================== */

it('accepts valid enum values on per-type fields', function () {
    expect(passesPrivacy([
        'profile_photo_privacy' => 'visible_to_all',
        'album_photos_privacy' => 'interest_accepted',
        'family_photos_privacy' => 'hidden',
    ]))->toBeTrue();
});

it('rejects invalid enum on profile_photo_privacy', function () {
    expect(failsPrivacyOn([
        'profile_photo_privacy' => 'open',
    ], 'profile_photo_privacy'))->toBeTrue();
});

/* ==================================================================
 |  Partial updates — accepted
 | ================================================================== */

it('accepts a payload with only one field set', function () {
    expect(passesPrivacy(['album_photos_privacy' => 'hidden']))->toBeTrue();
});

it('accepts null alongside a set field (null is filtered by controller)', function () {
    // Enough to pass "at least one" because family_photos_privacy is non-null.
    expect(passesPrivacy([
        'privacy_level' => null,
        'family_photos_privacy' => 'interest_accepted',
    ]))->toBeTrue();
});
