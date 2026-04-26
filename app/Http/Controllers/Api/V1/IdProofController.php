<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\IdProof;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * KYC ID-proof endpoints.
 *
 *   GET    /api/v1/id-proof                current status (or null)
 *   POST   /api/v1/id-proof                multipart upload (replaces previous)
 *   DELETE /api/v1/id-proof/{idProof}      withdraw (blocked once approved)
 *
 * Single active record per profile — uploading replaces any previous
 * submission (and removes its file from storage).
 *
 * Storage uses the `public` disk: simple, ships out-of-the-box on a
 * fresh CodeCanyon install with no Cloudinary / S3 configuration. The
 * id_proofs.cloudinary_public_id column stays around for buyers who
 * want to layer a Cloudinary upload on top later.
 *
 * Schema reconciliation (notes for anyone reading the doc):
 *   - document_type ENUM is the snake_case form
 *     (aadhaar | passport | voter_id | driving_license).
 *     The step-11 doc lists Title-Case strings + PAN — those don't
 *     exist on the schema.
 *   - document_number / document_back_url columns aren't in the
 *     migration; we don't accept them. Buyer can extend later.
 *   - verification_status values are pending | approved | rejected
 *     (the doc said "verified" — wrong).
 *
 * Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-11-id-proof.md
 */
class IdProofController extends BaseApiController
{
    /** Doc types accepted — must match the schema ENUM exactly. */
    private const ACCEPTED_TYPES = [
        'aadhaar' => 'Aadhaar Card',
        'passport' => 'Passport',
        'voter_id' => 'Voter ID',
        'driving_license' => 'Driving License',
    ];

    /** Storage disk — public so the URL is reachable by Filament admin reviewers. */
    private const DISK = 'public';

    /** Per-profile upload directory under the disk. */
    private const STORAGE_PREFIX = 'id-proofs';

    /* ==================================================================
     |  GET /id-proof
     | ================================================================== */

    /**
     * Get the viewer's current ID-proof submission + the catalogue of accepted document types.
     *
     * @authenticated
     *
     * @group ID Proof
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "id_proof": {"id": 12, "document_type": "aadhaar", "document_url": "https://.../id-proofs/...", "verification_status": "pending", "rejection_reason": null, "submitted_at": "2026-04-26T...", "verified_at": null},
     *     "accepted_types": [{"value": "aadhaar", "label": "Aadhaar Card"}, ...]
     *   }
     * }
     * @response 200 scenario="no-submission" {
     *   "success": true,
     *   "data": {"id_proof": null, "accepted_types": [...]}
     * }
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function show(Request $request): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return $this->profileRequired();
        }

        $idProof = IdProof::where('profile_id', $viewer->id)
            ->latest()
            ->first();

        return ApiResponse::ok([
            'id_proof' => $idProof ? $this->shape($idProof) : null,
            'accepted_types' => $this->acceptedTypesPayload(),
        ]);
    }

    /* ==================================================================
     |  POST /id-proof
     | ================================================================== */

    /**
     * Upload (or replace) the viewer's ID proof. Multipart body.
     *
     * Replaces any existing submission — both the row AND the underlying
     * file are removed first. Always lands in `pending` state so the
     * admin reviews from scratch.
     *
     * @authenticated
     *
     * @group ID Proof
     *
     * @bodyParam document_type string required One of: aadhaar, passport, voter_id, driving_license.
     * @bodyParam document file required JPG/PNG/PDF/WEBP, max 5 MB.
     *
     * @response 201 scenario="success" {
     *   "success": true,
     *   "data": {"id_proof": {"id": 12, "document_type": "aadhaar", "document_url": "...", "verification_status": "pending", "submitted_at": "..."}}
     * }
     * @response 422 scenario="validation" {"success": false, "error": {"code": "VALIDATION_FAILED", ...}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function store(Request $request): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return $this->profileRequired();
        }

        $data = $request->validate([
            'document_type' => 'required|string|in:'.implode(',', array_keys(self::ACCEPTED_TYPES)),
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
        ]);

        // Wipe any prior submission (row + file). Must run BEFORE we store
        // the new file so a partial failure doesn't strand both old and
        // new files in storage.
        $this->removeExistingFor($viewer->id);

        $path = $request->file('document')->store(
            self::STORAGE_PREFIX.'/'.$viewer->id,
            self::DISK,
        );

        $idProof = IdProof::create([
            'profile_id' => $viewer->id,
            'document_type' => $data['document_type'],
            'document_url' => $path,
            'verification_status' => 'pending',
        ]);

        return ApiResponse::created([
            'id_proof' => $this->shape($idProof),
        ]);
    }

    /* ==================================================================
     |  DELETE /id-proof/{idProof}
     | ================================================================== */

    /**
     * Withdraw a pending or rejected submission. Approved IDs cannot be
     * deleted by the user — they need admin intervention so the verified
     * badge isn't gameable.
     *
     * @authenticated
     *
     * @group ID Proof
     *
     * @urlParam idProof integer required ID-proof row id.
     *
     * @response 200 scenario="success" {"success": true, "data": {"deleted": true}}
     * @response 403 scenario="not-owner" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "..."}}
     * @response 422 scenario="already-verified" {"success": false, "error": {"code": "ALREADY_VERIFIED", "message": "..."}}
     */
    public function destroy(Request $request, IdProof $idProof): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return $this->profileRequired();
        }

        if ($idProof->profile_id !== $viewer->id) {
            return ApiResponse::error(
                'UNAUTHORIZED',
                'You do not have permission to delete this ID proof.',
                null,
                403,
            );
        }

        if ($idProof->verification_status === 'approved') {
            return ApiResponse::error(
                'ALREADY_VERIFIED',
                'Approved IDs cannot be deleted. Contact support if you need to update.',
                null,
                422,
            );
        }

        $this->deleteFile($idProof->document_url);
        $idProof->delete();

        return ApiResponse::ok(['deleted' => true]);
    }

    /* ==================================================================
     |  Helpers
     | ================================================================== */

    /**
     * API shape for a single IdProof row. Document URL is rendered
     * absolute via Storage::url so Flutter doesn't need to know the
     * disk's public-path convention.
     *
     * @return array<string,mixed>
     */
    private function shape(IdProof $p): array
    {
        return [
            'id' => (int) $p->id,
            'document_type' => (string) $p->document_type,
            'document_url' => $p->document_url ? Storage::disk(self::DISK)->url($p->document_url) : null,
            'verification_status' => (string) $p->verification_status,
            'rejection_reason' => $p->rejection_reason,
            'submitted_at' => $p->created_at?->toIso8601String(),
            'verified_at' => $p->verified_at?->toIso8601String(),
        ];
    }

    /** Stable shape for the accepted_types dropdown — value + label pairs. */
    private function acceptedTypesPayload(): array
    {
        $out = [];
        foreach (self::ACCEPTED_TYPES as $value => $label) {
            $out[] = ['value' => $value, 'label' => $label];
        }

        return $out;
    }

    /**
     * Remove ALL existing id_proof rows + files for a profile. Called
     * before a new upload so the per-profile invariant "exactly one
     * active submission at a time" holds.
     */
    private function removeExistingFor(int $profileId): void
    {
        $previous = IdProof::where('profile_id', $profileId)->get();
        foreach ($previous as $row) {
            $this->deleteFile($row->document_url);
            $row->delete();
        }
    }

    /**
     * Delete the file backing a given path. Best-effort — a missing file
     * isn't a failure (storage is best-effort here; the row drop is
     * authoritative).
     */
    private function deleteFile(?string $path): void
    {
        if (empty($path)) {
            return;
        }

        try {
            Storage::disk(self::DISK)->delete($path);
        } catch (\Throwable $e) {
            // Tolerated — the row deletion is what matters for the user.
        }
    }

    private function profileRequired(): JsonResponse
    {
        return ApiResponse::error(
            'PROFILE_REQUIRED',
            'Complete registration before submitting ID proof.',
            null,
            422,
        );
    }
}
