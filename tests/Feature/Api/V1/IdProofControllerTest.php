<?php

use App\Http\Controllers\Api\V1\IdProofController;
use App\Models\IdProof;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| IdProofController — show + store + destroy
|--------------------------------------------------------------------------
| Real Eloquent against an inline `id_proofs` table, with
| Storage::fake('public') so file IO is in-memory. Uses
| UploadedFile::fake() to simulate multipart uploads.
*/

function buildIdProofUser(int $id, bool $withProfile = true): User
{
    $u = new User();
    $u->exists = true;
    $u->forceFill(['id' => $id, 'email' => "i{$id}@e.com", 'is_active' => true]);
    $u->setRelation('userMemberships', new EloquentCollection());

    if ($withProfile) {
        $p = new Profile();
        $p->exists = true;
        $p->forceFill([
            'id' => $id,
            'user_id' => $id,
            'matri_id' => 'AM'.str_pad((string) $id, 6, '0', STR_PAD_LEFT),
            'gender' => 'male',
            'is_active' => true,
            'is_approved' => true,
        ]);
        $p->setRelation('user', $u);
        $u->setRelation('profile', $p);
    } else {
        $u->setRelation('profile', null);
    }

    return $u;
}

function idProofRequest(User $user, string $method = 'GET', array $body = [], array $files = [], string $path = '/api/v1/id-proof'): Request
{
    $r = Request::create($path, $method, $body, [], $files);
    $r->setUserResolver(fn () => $user);

    return $r;
}

function makeUploadedDoc(string $name = 'aadhaar.jpg', int $kb = 200): UploadedFile
{
    return UploadedFile::fake()->image($name, 800, 600)->size($kb);
}

beforeEach(function () {
    if (! Schema::hasTable('id_proofs')) {
        Schema::create('id_proofs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('profile_id');
            // String not enum — flexible for tests across SQLite (CHECK)
            // and to allow the validator to be the gate, not the DB.
            $t->string('document_type', 30);
            $t->string('document_url', 500);
            $t->string('cloudinary_public_id', 255)->nullable();
            $t->string('verification_status', 20)->default('pending');
            $t->string('rejection_reason', 500)->nullable();
            $t->unsignedBigInteger('verified_by')->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
        });
    }
    Storage::fake('public');
});

afterEach(function () {
    Schema::dropIfExists('id_proofs');
});

/* ==================================================================
 |  GET /id-proof
 | ================================================================== */

it('show returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildIdProofUser(100, withProfile: false);

    $response = app(IdProofController::class)->show(idProofRequest($user));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

it('show returns id_proof=null when viewer has no submission yet', function () {
    $user = buildIdProofUser(100);

    $response = app(IdProofController::class)->show(idProofRequest($user));
    $payload = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($payload['data']['id_proof'])->toBeNull();
    // Always present — Flutter renders the dropdown from this list.
    expect($payload['data']['accepted_types'])->toBeArray()->not->toBeEmpty();
    expect($payload['data']['accepted_types'][0])->toHaveKeys(['value', 'label']);
});

it('show returns the latest submission for the viewer', function () {
    $user = buildIdProofUser(100);
    Carbon::setTestNow('2026-04-26 09:00:00');
    IdProof::create([
        'profile_id' => 100, 'document_type' => 'aadhaar',
        'document_url' => 'id-proofs/100/old.jpg', 'verification_status' => 'rejected',
        'rejection_reason' => 'blurry',
    ]);
    Carbon::setTestNow('2026-04-26 10:00:00');
    IdProof::create([
        'profile_id' => 100, 'document_type' => 'passport',
        'document_url' => 'id-proofs/100/new.jpg', 'verification_status' => 'pending',
    ]);
    Carbon::setTestNow();

    $response = app(IdProofController::class)->show(idProofRequest($user));
    $idProof = $response->getData(true)['data']['id_proof'];

    expect($idProof['document_type'])->toBe('passport');
    expect($idProof['verification_status'])->toBe('pending');
    expect($idProof['rejection_reason'])->toBeNull();
    expect($idProof['document_url'])->toContain('id-proofs/100/new.jpg');
});

it('show only returns the viewers own submission (multi-tenant safety)', function () {
    $owner = buildIdProofUser(100);
    IdProof::create([
        'profile_id' => 100, 'document_type' => 'aadhaar',
        'document_url' => 'id-proofs/100/owner.jpg', 'verification_status' => 'pending',
    ]);
    IdProof::create([
        'profile_id' => 999, 'document_type' => 'voter_id',
        'document_url' => 'id-proofs/999/stranger.jpg', 'verification_status' => 'approved',
    ]);

    $response = app(IdProofController::class)->show(idProofRequest($owner));

    expect($response->getData(true)['data']['id_proof']['document_type'])->toBe('aadhaar');
});

/* ==================================================================
 |  POST /id-proof
 | ================================================================== */

it('store uploads file + persists row + returns 201', function () {
    $user = buildIdProofUser(100);

    $response = app(IdProofController::class)->store(idProofRequest(
        $user, 'POST',
        body: ['document_type' => 'aadhaar'],
        files: ['document' => makeUploadedDoc()],
    ));
    $payload = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(201);
    expect($payload['id_proof']['document_type'])->toBe('aadhaar');
    expect($payload['id_proof']['verification_status'])->toBe('pending');

    $row = IdProof::where('profile_id', 100)->first();
    expect($row)->not->toBeNull();
    expect($row->document_type)->toBe('aadhaar');
    expect($row->document_url)->toStartWith('id-proofs/100/');
    Storage::disk('public')->assertExists($row->document_url);
});

it('store replaces previous submission — old row + file removed', function () {
    $user = buildIdProofUser(100);

    // Pre-seed a previous submission with a real file.
    Storage::disk('public')->put('id-proofs/100/old.jpg', 'fake-bytes');
    $previous = IdProof::create([
        'profile_id' => 100,
        'document_type' => 'aadhaar',
        'document_url' => 'id-proofs/100/old.jpg',
        'verification_status' => 'pending',
    ]);

    app(IdProofController::class)->store(idProofRequest(
        $user, 'POST',
        body: ['document_type' => 'passport'],
        files: ['document' => makeUploadedDoc(name: 'passport.jpg')],
    ));

    expect(IdProof::find($previous->id))->toBeNull();
    Storage::disk('public')->assertMissing('id-proofs/100/old.jpg');
    expect(IdProof::where('profile_id', 100)->count())->toBe(1);
});

it('store returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildIdProofUser(100, withProfile: false);

    $response = app(IdProofController::class)->store(idProofRequest(
        $user, 'POST',
        body: ['document_type' => 'aadhaar'],
        files: ['document' => makeUploadedDoc()],
    ));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

it('store rejects unknown document_type', function () {
    $user = buildIdProofUser(100);

    expect(fn () => app(IdProofController::class)->store(idProofRequest(
        $user, 'POST',
        body: ['document_type' => 'pan_card'],  // not in the schema enum
        files: ['document' => makeUploadedDoc()],
    )))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('store rejects oversized file (> 5 MB)', function () {
    $user = buildIdProofUser(100);

    expect(fn () => app(IdProofController::class)->store(idProofRequest(
        $user, 'POST',
        body: ['document_type' => 'aadhaar'],
        files: ['document' => makeUploadedDoc(kb: 6000)],  // 6 MB
    )))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('store rejects unsupported mime type', function () {
    $user = buildIdProofUser(100);

    expect(fn () => app(IdProofController::class)->store(idProofRequest(
        $user, 'POST',
        body: ['document_type' => 'aadhaar'],
        files: ['document' => UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream')],
    )))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('store accepts pdf documents', function () {
    $user = buildIdProofUser(100);

    $response = app(IdProofController::class)->store(idProofRequest(
        $user, 'POST',
        body: ['document_type' => 'voter_id'],
        files: ['document' => UploadedFile::fake()->create('voterid.pdf', 200, 'application/pdf')],
    ));

    expect($response->getStatusCode())->toBe(201);
});

/* ==================================================================
 |  DELETE /id-proof/{idProof}
 | ================================================================== */

it('destroy deletes the row + file + returns 200', function () {
    $user = buildIdProofUser(100);
    Storage::disk('public')->put('id-proofs/100/doc.jpg', 'fake-bytes');
    $row = IdProof::create([
        'profile_id' => 100, 'document_type' => 'aadhaar',
        'document_url' => 'id-proofs/100/doc.jpg', 'verification_status' => 'pending',
    ]);

    $response = app(IdProofController::class)->destroy(
        idProofRequest($user, 'DELETE'),
        $row,
    );

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['deleted'])->toBeTrue();
    expect(IdProof::find($row->id))->toBeNull();
    Storage::disk('public')->assertMissing('id-proofs/100/doc.jpg');
});

it('destroy returns 403 when not the owner', function () {
    $owner = buildIdProofUser(100);
    $stranger = buildIdProofUser(200);
    $row = IdProof::create([
        'profile_id' => 100, 'document_type' => 'aadhaar',
        'document_url' => 'id-proofs/100/doc.jpg', 'verification_status' => 'pending',
    ]);

    $response = app(IdProofController::class)->destroy(
        idProofRequest($stranger, 'DELETE'),
        $row,
    );

    expect($response->getStatusCode())->toBe(403);
    expect($response->getData(true)['error']['code'])->toBe('UNAUTHORIZED');
    expect(IdProof::find($row->id))->not->toBeNull();
});

it('destroy returns 422 ALREADY_VERIFIED when status=approved', function () {
    $user = buildIdProofUser(100);
    $row = IdProof::create([
        'profile_id' => 100, 'document_type' => 'aadhaar',
        'document_url' => 'id-proofs/100/doc.jpg', 'verification_status' => 'approved',
    ]);

    $response = app(IdProofController::class)->destroy(
        idProofRequest($user, 'DELETE'),
        $row,
    );

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('ALREADY_VERIFIED');
    expect(IdProof::find($row->id))->not->toBeNull();
});

it('destroy returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildIdProofUser(100, withProfile: false);
    $row = IdProof::create([
        'profile_id' => 100, 'document_type' => 'aadhaar',
        'document_url' => 'id-proofs/100/doc.jpg', 'verification_status' => 'pending',
    ]);

    $response = app(IdProofController::class)->destroy(
        idProofRequest($user, 'DELETE'),
        $row,
    );

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});
