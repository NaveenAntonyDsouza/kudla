<?php

namespace App\Http\Requests\Api\V1\Photo;

use App\Http\Requests\Api\V1\ApiFormRequest;

/**
 * Validates POST /api/v1/photos (multipart upload).
 *
 * Two fields:
 *   photo       required, image/* mime, max size from config/matrimony.
 *   photo_type  required, one of: profile | album | family.
 *
 * Mime whitelist + max size MUST match what ImageProcessingService can
 * actually handle, because bypassing the controller's validation (e.g.
 * via a direct file upload) would expose the image pipeline to
 * untested formats. Intervention Image + WatermarkService support
 * jpg/jpeg/png/gif/webp — so does the web-side PhotoController, and so
 * does this FormRequest.
 *
 * Per-type slot limits (profile=1, album=9, family=3) are enforced in
 * the controller rather than here because checking them requires
 * counting the authenticated user's existing photos — it's a business
 * rule against live DB state, not a shape rule against the payload.
 *
 * Reference:
 *   docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-09-photo-crud-endpoints.md
 */
class UploadPhotoRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $maxKilobytes = (int) config('matrimony.max_photo_size_mb', 5) * 1024;

        return [
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,gif,webp',
                "max:{$maxKilobytes}",
            ],
            'photo_type' => [
                'required',
                'in:profile,album,family',
            ],
        ];
    }

    public function messages(): array
    {
        // Surface the specific size cap in the user-visible error rather
        // than Laravel's default "The photo must not be greater than
        // {n} kilobytes." — MB is what humans think in.
        $maxMb = (int) config('matrimony.max_photo_size_mb', 5);

        return [
            'photo.max' => "The photo must not be larger than {$maxMb} MB.",
            'photo.image' => 'The uploaded file must be an image (jpg, jpeg, png, gif, or webp).',
            'photo.mimes' => 'Only jpg, jpeg, png, gif, and webp formats are supported.',
            'photo_type.in' => 'photo_type must be one of: profile, album, family.',
        ];
    }
}
