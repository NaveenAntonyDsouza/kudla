<?php

use App\Models\IdProof;
use App\Support\IdProofFiles;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/*
| ID proof scans (Oct 2026): the upload failed on every site (two columns were
| never created) and the scans sat in the public folder. Now: the columns
| exist, scans are on the private disk, links are short-lived and signed, and
| only the last 4 characters of the ID number are kept.
*/

beforeEach(function () {
    Schema::dropIfExists('id_proofs');
    Schema::create('id_proofs', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('document_type');
        $t->string('document_url', 500);
        $t->string('cloudinary_public_id')->nullable();
        $t->string('verification_status')->default('pending');
        $t->text('rejection_reason')->nullable();
        $t->unsignedBigInteger('verified_by')->nullable();
        $t->timestamp('verified_at')->nullable();
        $t->timestamps();
    });
    (require database_path('migrations/2026_10_27_000001_add_number_and_back_to_id_proofs.php'))->up();
    Storage::fake('local');
    Storage::fake('public');
});

afterEach(fn () => Schema::dropIfExists('id_proofs'));

it('adds the columns the upload form saves', function () {
    expect(Schema::hasColumn('id_proofs', 'document_number'))->toBeTrue()
        ->and(Schema::hasColumn('id_proofs', 'document_back_url'))->toBeTrue();
});

it('keeps only the last 4 characters of the ID number', function () {
    expect(IdProofFiles::maskNumber('1234 5678 9012'))->toBe('XXXXXXXX9012')
        ->and(IdProofFiles::maskNumber('k1234567'))->toBe('XXXX4567')
        ->and(IdProofFiles::maskNumber('12'))->toBe('12')
        ->and(IdProofFiles::maskNumber('  '))->toBeNull();
});

it('stores scans privately and shows them only through a signed link', function () {
    $path = IdProofFiles::store(UploadedFile::fake()->image('aadhaar.jpg'), 100);
    $proof = IdProof::create(['profile_id' => 100, 'document_type' => 'Aadhaar Card', 'document_url' => $path, 'document_number' => 'XXXXXXXX9012']);

    Storage::disk('local')->assertExists($path);
    Storage::disk('public')->assertMissing($path);

    $url = IdProofFiles::url($proof);
    expect($url)->toContain('/id-proof-file/' . $proof->id . '/front')->toContain('signature=')
        ->and(IdProofFiles::url($proof, 'back'))->toBeNull();                    // no back side uploaded

    $this->get($url)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->get(route('id-proof.file', [$proof->id, 'front']))->assertForbidden();   // no signature
    $this->get(str_replace('/front', '/back', $url))->assertForbidden();             // changed link
});

it('still shows a scan saved in the public folder before, and deletes from both', function () {
    Storage::disk('public')->put('id-proofs/7/old.jpg', 'scan');
    $proof = IdProof::create(['profile_id' => 7, 'document_type' => 'Passport', 'document_url' => 'id-proofs/7/old.jpg']);

    $this->get(IdProofFiles::url($proof))->assertOk();

    IdProofFiles::delete('id-proofs/7/old.jpg');
    Storage::disk('public')->assertMissing('id-proofs/7/old.jpg');
});
