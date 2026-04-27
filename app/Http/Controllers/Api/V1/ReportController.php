<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\Profile;
use App\Models\ProfileReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Report-a-profile endpoint.
 *
 *   POST /api/v1/profiles/{matriId}/report
 *
 * Reasons are sourced from ProfileReport::reasons() so the API stays
 * in sync with the admin Filament screen + the web report form (single
 * source of truth, no risk of drift).
 *
 * Duplicate-pending guard: a viewer can only have one pending report
 * per target. Submitting again while a previous report is still pending
 * returns 409 ALREADY_EXISTS — Flutter shows "we've got it, our team
 * is on it" instead of stacking duplicates.
 *
 * Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-10-block-report-ignore.md
 */
class ReportController extends BaseApiController
{
    /**
     * Submit a profile report.
     *
     * @authenticated
     *
     * @group Report
     *
     * @urlParam matriId string required Target's matri_id.
     *
     * @bodyParam reason string required One of: fake_profile, inappropriate_photo, harassment, fraud, already_married, wrong_info, other.
     * @bodyParam description string Optional. Max 1000 chars. Free-form context for the admin reviewer.
     *
     * @response 201 scenario="success" {
     *   "success": true,
     *   "data": {"report_id": 42, "status": "pending", "message": "Our team will review within 48 hours."}
     * }
     * @response 404 scenario="not-found" {"success": false, "error": {"code": "NOT_FOUND", "message": "Profile not available."}}
     * @response 409 scenario="duplicate" {"success": false, "error": {"code": "ALREADY_EXISTS", "message": "..."}}
     * @response 422 scenario="invalid-target" {"success": false, "error": {"code": "INVALID_TARGET", "message": "..."}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     * @response 422 scenario="validation" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "..."}}
     */
    public function store(Request $request, string $matriId): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return $this->profileRequired();
        }

        $data = $request->validate([
            'reason' => 'required|string|in:'.implode(',', array_keys(ProfileReport::reasons())),
            'description' => 'nullable|string|max:1000',
        ]);

        $target = $this->findTargetByMatriId($matriId);
        if (! $target) {
            return ApiResponse::error('NOT_FOUND', 'Profile not available.', null, 404);
        }

        if ($viewer->id === $target->id) {
            return ApiResponse::error(
                'INVALID_TARGET',
                'You cannot report your own profile.',
                null,
                422,
            );
        }

        // Block stacked reports: one pending report per (reporter, reported)
        // pair. Once admin closes (status != pending), the reporter can
        // submit a new one — the relationship may have escalated.
        $hasPending = ProfileReport::where('reporter_profile_id', $viewer->id)
            ->where('reported_profile_id', $target->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return ApiResponse::error(
                'ALREADY_EXISTS',
                'You already have a pending report for this profile. Our team will review within 48 hours.',
                null,
                409,
            );
        }

        $report = ProfileReport::create([
            'reporter_profile_id' => $viewer->id,
            'reported_profile_id' => $target->id,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
        ]);

        return ApiResponse::created([
            'report_id' => (int) $report->id,
            'status' => 'pending',
            'message' => 'Our team will review within 48 hours.',
        ]);
    }

    /* ==================================================================
     |  Test seam
     | ================================================================== */

    protected function findTargetByMatriId(string $matriId): ?Profile
    {
        return Profile::where('matri_id', $matriId)->first();
    }

    /* ==================================================================
     |  Helpers
     | ================================================================== */

    private function profileRequired(): JsonResponse
    {
        return ApiResponse::error(
            'PROFILE_REQUIRED',
            'Complete registration before reporting profiles.',
            null,
            422,
        );
    }
}
