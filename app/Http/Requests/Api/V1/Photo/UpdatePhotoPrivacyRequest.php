<?php

namespace App\Http\Requests\Api\V1\Photo;

use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Contracts\Validation\Validator;

/**
 * Validates POST /api/v1/photos/privacy.
 *
 * Four fields, all optional (PATCH-like semantics) — callers send only
 * the privacy levels they want to change:
 *
 *   privacy_level             (legacy — applies to every photo type)
 *   profile_photo_privacy     (per-type override)
 *   album_photos_privacy      (per-type override)
 *   family_photos_privacy     (per-type override)
 *
 * Each accepts one of: visible_to_all | interest_accepted | hidden
 * (same enum the PhotoPrivacySetting model constants expose and the web
 * controller validates against).
 *
 * The withValidator() hook rejects an empty payload — calling this
 * endpoint with nothing-to-update makes no sense. Mirrors the web
 * controller's "require at least one field" behaviour.
 *
 * Reference:
 *   docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-10-photo-privacy-endpoint.md
 */
class UpdatePhotoPrivacyRequest extends ApiFormRequest
{
    /** Allowed privacy levels — matches PhotoPrivacySetting constants. */
    private const LEVELS = [
        'visible_to_all',
        'interest_accepted',
        'hidden',
    ];

    public function rules(): array
    {
        $levelRule = 'nullable|in:'.implode(',', self::LEVELS);

        return [
            'privacy_level' => $levelRule,
            'profile_photo_privacy' => $levelRule,
            'album_photos_privacy' => $levelRule,
            'family_photos_privacy' => $levelRule,
        ];
    }

    /**
     * Reject a payload that doesn't set at least one privacy field to
     * a non-null value. Laravel's validator runs rules per-field, so
     * we use the cross-field `after` hook to enforce the "require one"
     * invariant.
     *
     * Reads from `$v->getData()` rather than `$this->only(...)` so the
     * hook works regardless of how the FormRequest was constructed —
     * essential for the test-helper path that doesn't seed the internal
     * request bag the same way HTTP dispatch does.
     */
    public function withValidator(Validator $validator): void
    {
        $fields = array_keys($this->rules());

        $validator->after(function (Validator $v) use ($fields) {
            $data = $v->getData();
            $hasOne = false;
            foreach ($fields as $field) {
                if (($data[$field] ?? null) !== null) {
                    $hasOne = true;
                    break;
                }
            }

            if (! $hasOne) {
                $v->errors()->add(
                    'privacy_level',
                    'Provide at least one privacy field to update.',
                );
            }
        });
    }

    /**
     * Scribe body-parameter metadata. All four fields are nullable + send
     * at-least-one is enforced cross-field (see withValidator above), which
     * Scribe cannot infer from rules() alone.
     */
    public function bodyParameters(): array
    {
        $description = 'One of: visible_to_all | interest_accepted | hidden. Send at least one privacy field per request.';

        return [
            'privacy_level' => [
                'description' => "Legacy field — applies the same privacy level to every photo type. {$description}",
                'type' => 'string',
                'enum' => self::LEVELS,
                'required' => false,
                'example' => 'visible_to_all',
            ],
            'profile_photo_privacy' => [
                'description' => "Per-type override for the profile (primary) photo. {$description}",
                'type' => 'string',
                'enum' => self::LEVELS,
                'required' => false,
                'example' => 'visible_to_all',
            ],
            'album_photos_privacy' => [
                'description' => "Per-type override for album photos. {$description}",
                'type' => 'string',
                'enum' => self::LEVELS,
                'required' => false,
                'example' => 'interest_accepted',
            ],
            'family_photos_privacy' => [
                'description' => "Per-type override for family photos. {$description}",
                'type' => 'string',
                'enum' => self::LEVELS,
                'required' => false,
                'example' => 'interest_accepted',
            ],
        ];
    }
}
