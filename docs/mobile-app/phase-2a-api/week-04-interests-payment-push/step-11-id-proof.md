# Step 11 — ID Proof Endpoints

## Goal
- `GET /api/v1/id-proof` — current status
- `POST /api/v1/id-proof` — upload (multipart)
- `DELETE /api/v1/id-proof/{idProof}` — withdraw

## Procedure

### 1. `IdProofController`

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\IdProof;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdProofController extends BaseApiController
{
    /** @authenticated @group ID Proof */
    public function show(Request $request): JsonResponse
    {
        $idProof = IdProof::where('profile_id', $request->user()->profile->id)->latest()->first();

        return ApiResponse::ok([
            'id_proof' => $idProof ? [
                'id' => $idProof->id,
                'document_type' => $idProof->document_type,
                'document_number' => $this->maskNumber($idProof->document_number),
                'front_url' => $idProof->document_url ? url($idProof->document_url) : null,
                'back_url' => $idProof->document_back_url ? url($idProof->document_back_url) : null,
                'verification_status' => $idProof->verification_status,
                'rejection_reason' => $idProof->rejection_reason,
                'submitted_at' => $idProof->created_at->toIso8601String(),
                'verified_at' => $idProof->verified_at?->toIso8601String(),
            ] : null,
            'accepted_types' => ['Passport', 'Voter ID', 'Aadhaar Card', 'Driving License', 'PAN Card'],
        ]);
    }

    /** @authenticated @group ID Proof */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'document_type' => 'required|in:Passport,Voter ID,Aadhaar Card,Driving License,PAN Card',
            'document_number' => 'required|string|max:50',
            'front' => 'required|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
            'back' => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
        ]);

        $profile = $request->user()->profile;
        $dir = "id-proofs/{$profile->id}";

        // Delete any existing (one active at a time)
        IdProof::where('profile_id', $profile->id)->delete();

        $frontPath = $request->file('front')->store($dir, 'public');
        $backPath = $request->hasFile('back') ? $request->file('back')->store($dir, 'public') : null;

        $idProof = IdProof::create([
            'profile_id' => $profile->id,
            'document_type' => $data['document_type'],
            'document_number' => $data['document_number'],
            'document_url' => "/storage/{$frontPath}",
            'document_back_url' => $backPath ? "/storage/{$backPath}" : null,
            'verification_status' => 'pending',
        ]);

        return ApiResponse::created([
            'id_proof' => [
                'id' => $idProof->id,
                'verification_status' => 'pending',
                'submitted_at' => $idProof->created_at->toIso8601String(),
            ],
        ]);
    }

    /** @authenticated @group ID Proof */
    public function destroy(Request $request, IdProof $idProof): JsonResponse
    {
        abort_if($idProof->profile_id !== $request->user()->profile->id, 403);
        abort_if($idProof->verification_status === 'verified', 422, 'Cannot delete verified ID.');

        // Remove files
        if ($idProof->document_url) \Storage::disk('public')->delete(str_replace('/storage/', '', $idProof->document_url));
        if ($idProof->document_back_url) \Storage::disk('public')->delete(str_replace('/storage/', '', $idProof->document_back_url));

        $idProof->delete();
        return ApiResponse::ok(['deleted' => true]);
    }

    private function maskNumber(string $n): string
    {
        $len = strlen($n);
        if ($len <= 4) return $n;
        return str_repeat('X', $len - 4) . substr($n, -4);
    }
}
```

### 2. Routes

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/id-proof', [\App\Http\Controllers\Api\V1\IdProofController::class, 'show']);
    Route::post('/id-proof', [\App\Http\Controllers\Api\V1\IdProofController::class, 'store']);
    Route::delete('/id-proof/{idProof}', [\App\Http\Controllers\Api\V1\IdProofController::class, 'destroy']);
});
```

## Verification
- [ ] GET returns null if no ID submitted
- [ ] Upload replaces previous pending submission
- [ ] Document number is masked in response
- [ ] Verified ID cannot be deleted by user

## Commit
```bash
git commit -am "phase-2a wk-04: step-11 ID proof endpoints"
```

## Next step
→ [step-12-settings.md](step-12-settings.md)
