<?php

use App\Http\Requests\Api\V1\Photo\UploadPhotoRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| UploadPhotoRequest — validation rule matrix
|--------------------------------------------------------------------------
| Pure-PHP tests. Exercises the rules() matrix against sample payloads
| without touching the HTTP layer or DB. Uses UploadedFile::fake() for
| file-shaped inputs.
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

function rules(): array
{
    return (new UploadPhotoRequest())->rules();
}

function passesUpload(array $data): bool
{
    return Validator::make($data, rules(), (new UploadPhotoRequest())->messages())->passes();
}

function failsUploadOn(array $data, string $field): bool
{
    $v = Validator::make($data, rules(), (new UploadPhotoRequest())->messages());

    return $v->fails() && $v->errors()->has($field);
}

/* ==================================================================
 |  Required fields
 | ================================================================== */

it('rejects empty payload', function () {
    expect(failsUploadOn([], 'photo'))->toBeTrue();
    expect(failsUploadOn([], 'photo_type'))->toBeTrue();
});

it('requires photo_type', function () {
    $file = UploadedFile::fake()->image('valid.jpg');

    expect(failsUploadOn(['photo' => $file], 'photo_type'))->toBeTrue();
});

it('requires photo', function () {
    expect(failsUploadOn(['photo_type' => 'album'], 'photo'))->toBeTrue();
});

/* ==================================================================
 |  photo_type whitelist
 | ================================================================== */

it('accepts profile / album / family as photo_type', function () {
    $file = UploadedFile::fake()->image('valid.jpg');

    foreach (['profile', 'album', 'family'] as $type) {
        expect(passesUpload(['photo' => $file, 'photo_type' => $type]))
            ->toBeTrue("photo_type={$type} should pass");
    }
});

it('rejects unknown photo_type values', function () {
    $file = UploadedFile::fake()->image('valid.jpg');

    foreach (['cover', 'avatar', 'banner', '', 'PROFILE'] as $bad) {
        expect(failsUploadOn(['photo' => $file, 'photo_type' => $bad], 'photo_type'))
            ->toBeTrue("photo_type={$bad} should fail");
    }
});

/* ==================================================================
 |  File constraints — mime + size
 | ================================================================== */

it('accepts jpg/jpeg/png/gif/webp images', function () {
    foreach (['photo.jpg', 'photo.jpeg', 'photo.png', 'photo.gif', 'photo.webp'] as $name) {
        $file = UploadedFile::fake()->image($name);
        expect(passesUpload(['photo' => $file, 'photo_type' => 'album']))
            ->toBeTrue("{$name} should pass");
    }
});

it('rejects non-image file types', function () {
    $file = UploadedFile::fake()->create('malicious.pdf', 100, 'application/pdf');

    expect(failsUploadOn(['photo' => $file, 'photo_type' => 'album'], 'photo'))->toBeTrue();
});

it('rejects files exceeding the configured size limit', function () {
    $maxMb = (int) config('matrimony.max_photo_size_mb', 5);
    // Fake a file one megabyte over the limit.
    $file = UploadedFile::fake()->image('too-big.jpg')->size(($maxMb + 1) * 1024);

    expect(failsUploadOn(['photo' => $file, 'photo_type' => 'album'], 'photo'))->toBeTrue();
});

it('accepts files at exactly the configured size limit', function () {
    $maxMb = (int) config('matrimony.max_photo_size_mb', 5);
    $file = UploadedFile::fake()->image('at-limit.jpg')->size($maxMb * 1024);

    expect(passesUpload(['photo' => $file, 'photo_type' => 'album']))->toBeTrue();
});

/* ==================================================================
 |  Custom error messages surface
 | ================================================================== */

it('surfaces friendly max-size message in MB (not KB)', function () {
    $maxMb = (int) config('matrimony.max_photo_size_mb', 5);
    $file = UploadedFile::fake()->image('too-big.jpg')->size(($maxMb + 1) * 1024);

    $v = Validator::make(
        ['photo' => $file, 'photo_type' => 'album'],
        rules(),
        (new UploadPhotoRequest())->messages(),
    );
    $v->fails();

    $msg = $v->errors()->first('photo');
    expect($msg)->toContain((string) $maxMb);
    expect($msg)->toContain('MB');
});
