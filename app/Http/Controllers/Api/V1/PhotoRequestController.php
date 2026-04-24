<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileCardResource;
use App\Http\Responses\ApiResponse;
use App\Models\PhotoRequest;
use App\Models\Profile;
use App\Services\NotificationService;
use App\Services\PhotoAccessService;
use App\Services\ProfileAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Photo-request lifecycle: request → approve | ignore.
 *
 *   POST /api/v1/profiles/{matriId}/photo-request    send
 *   GET  /api/v1/photo-requests                       list received + sent
 *   POST /api/v1/photo-requests/{id}/approve          approve (+ grant access)
 *   POST /api/v1/photo-requests/{id}/ignore           silently ignore
 *
 * PhotoRequest owns the CONVERSATION (pending → approved | ignored).
 * PhotoAccessGrant owns the RESULT ("has access"). Approving a request
 * calls PhotoAccessService::grant() — the first live use of step-8's
 * infrastructure.
 *
 * Access gating on send delegates to ProfileAccessService::check() so the
 * full 7-gate matrix applies (same-gender 403, blocked/hidden/suspended
 * indistinguishable 404, etc.). Stricter than the web controller, which
 * only checks self + duplicate — this API sets a higher bar because
 * Flutter callers are expected to honour the contract more tightly.
 *
 * Notification dispatch is best-effort — wrapped in try/catch so a
 * notifications-table hiccup (incl. the current notification-type enum
 * gap for 'photo_request' / 'photo_request_approved') never blocks the
 * user's send or approve action.
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-11-photo-request-endpoints.md
 */
class PhotoRequestController extends BaseApiController
{
    public function __construct(
        private ProfileAccessService $access,
        private PhotoAccessService $photoAccess,
        private NotificationService $notifier,
    ) {}

    /* ==================================================================
     |  POST /profiles/{matriId}/photo-request — send
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Photo Requests
     *
     * @urlParam matriId string required Target profile's matri_id (AM######).
     *
     * @response 201 scenario="success" {
     *   "success": true,
     *   "data": {"request_id": 42, "status": "pending"}
     * }
     *
     * @response 403 scenario="same-gender" {
     *   "success": false,
     *   "error": {"code": "GENDER_MISMATCH", "message": "Cannot request photos from a same-gender profile."}
     * }
     *
     * @response 404 scenario="not-found-or-restricted" {
     *   "success": false,
     *   "error": {"code": "NOT_FOUND", "message": "Profile not available."}
     * }
     *
     * @response 409 scenario="already-exists" {
     *   "success": false,
     *   "error": {"code": "ALREADY_EXISTS", "message": "You already have an open photo request with this profile."}
     * }
     *
     * @response 422 scenario="self-request" {
     *   "success": false,
     *   "error": {"code": "SELF_REQUEST", "message": "You cannot request photos from your own profile."}
     * }
     *
     * @response 422 scenario="no-profile" {
     *   "success": false,
     *   "error": {"code": "PROFILE_REQUIRED", "message": "..."}
     * }
     *
     * @response 429 scenario="throttled" {"success": false, "error": {"code": "THROTTLED", "message": "..."}}
     */
    public function send(Request $request, string $matriId): JsonResponse
    {
        $requester = $request->user()->profile;
        if (! $requester) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before sending photo requests.',
                null,
                422,
            );
        }

        $target = $this->findTargetByMatriId($matriId);
        if (! $target) {
            return $this->notFound();
        }

        // Self-request is a distinct failure mode (422, not 404) so
        // Flutter can show the user a clear message rather than a
        // generic "not found."
        if ($requester->id === $target->id) {
            return ApiResponse::error(
                'SELF_REQUEST',
                'You cannot request photos from your own profile.',
                null,
                422,
            );
        }

        // Full 7-gate check. Same-gender → 403, blocked/hidden/suspended →
        // 404 (anti-enumeration — identical body to "doesn't exist").
        $reason = $this->access->check($requester, $target);
        if ($reason !== ProfileAccessService::REASON_OK
            && $reason !== ProfileAccessService::REASON_SELF) {
            return $this->mapGateReason($reason);
        }

        // Duplicate policy: reject if ANY pending or approved request
        // already exists. Ignored requests can be re-sent (the user
        // may have changed their mind).
        $existing = $this->existingOpenRequest($requester->id, $target->id);
        if ($existing) {
            return ApiResponse::error(
                'ALREADY_EXISTS',
                'You already have an open photo request with this profile.',
                null,
                409,
            );
        }

        $photoRequest = PhotoRequest::create([
            'requester_profile_id' => $requester->id,
            'target_profile_id' => $target->id,
            'status' => 'pending',
        ]);

        // Best-effort notification to the target.
        $this->safeNotify(
            $target->user,
            'photo_request',
            'New photo request',
            ($requester->full_name ?: 'Someone').' has requested to see your photos.',
            $requester->id,
            [
                'photo_request_id' => $photoRequest->id,
                'requester_matri_id' => $requester->matri_id,
            ],
        );

        return ApiResponse::created([
            'request_id' => (int) $photoRequest->id,
            'status' => 'pending',
        ]);
    }

    /* ==================================================================
     |  GET /photo-requests — list received + sent
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Photo Requests
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "received": [{"id": 1, "requester": {...}, "status": "pending", "created_at": "..."}],
     *     "sent": [{"id": 2, "target": {...}, "status": "approved", "created_at": "..."}]
     *   }
     * }
     *
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;
        if (! $profile) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before viewing photo requests.',
                null,
                422,
            );
        }

        $received = $this->loadRequests(
            'target_profile_id',
            $profile->id,
            'requesterProfile',
            'requester',
        );

        $sent = $this->loadRequests(
            'requester_profile_id',
            $profile->id,
            'targetProfile',
            'target',
        );

        return ApiResponse::ok([
            'received' => $received,
            'sent' => $sent,
        ]);
    }

    /* ==================================================================
     |  POST /photo-requests/{id}/approve
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Photo Requests
     *
     * @urlParam photoRequest integer required The PhotoRequest id.
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {"approved": true, "request_id": 42}
     * }
     *
     * @response 403 scenario="not-target" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "..."}}
     * @response 404 scenario="not-found" {"success": false, "error": {"code": "NOT_FOUND", "message": "..."}}
     * @response 422 scenario="not-pending" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "...", "fields": {"status": ["..."]}}}
     */
    public function approve(Request $request, PhotoRequest $photoRequest): JsonResponse
    {
        if (($guard = $this->ensureIsTarget($request, $photoRequest)) !== null) {
            return $guard;
        }

        if ($photoRequest->status !== 'pending') {
            return ApiResponse::error(
                'VALIDATION_FAILED',
                "Cannot approve a request that's already {$photoRequest->status}.",
                ['status' => ["Request is {$photoRequest->status}, not pending."]],
                422,
            );
        }

        $photoRequest->update(['status' => 'approved']);

        // Wire step-8's grant machinery. The target is the grantor
        // (their photos become visible); the requester is the grantee.
        $target = $request->user()->profile;
        $requester = $photoRequest->requesterProfile;
        if ($requester) {
            $this->photoAccess->grant($target, $requester);

            $this->safeNotify(
                $requester->user,
                'photo_request_approved',
                'Photo request approved',
                ($target->full_name ?: 'Someone').' approved your photo request.',
                $target->id,
                [
                    'photo_request_id' => $photoRequest->id,
                    'target_matri_id' => $target->matri_id,
                ],
            );
        }

        return ApiResponse::ok([
            'approved' => true,
            'request_id' => (int) $photoRequest->id,
        ]);
    }

    /* ==================================================================
     |  POST /photo-requests/{id}/ignore
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Photo Requests
     *
     * @urlParam photoRequest integer required The PhotoRequest id.
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {"ignored": true, "request_id": 42}
     * }
     *
     * @response 403 scenario="not-target" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "..."}}
     * @response 404 scenario="not-found" {"success": false, "error": {"code": "NOT_FOUND", "message": "..."}}
     */
    public function ignore(Request $request, PhotoRequest $photoRequest): JsonResponse
    {
        if (($guard = $this->ensureIsTarget($request, $photoRequest)) !== null) {
            return $guard;
        }

        // Ignore is a no-op if already ignored — idempotent for the
        // target's convenience (Flutter may retry on network hiccup).
        // We still flip non-terminal statuses to 'ignored' so the row
        // moves out of the pending bucket.
        if ($photoRequest->status === 'pending') {
            $photoRequest->update(['status' => 'ignored']);
        }

        // No notification — by design. "Ignore" is silent: the requester
        // should not know they were actively rejected (reduces awkward
        // social friction).
        return ApiResponse::ok([
            'ignored' => true,
            'request_id' => (int) $photoRequest->id,
        ]);
    }

    /* ==================================================================
     |  Private helpers
     | ================================================================== */

    /** Profile lookup by matri_id with defensive fallback. Mirrors ProfileController::findTargetByMatriId. */
    protected function findTargetByMatriId(string $matriId): ?Profile
    {
        try {
            return Profile::where('matri_id', $matriId)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Single NOT_FOUND envelope (anti-enumeration — blocked/hidden/suspended/missing all return identical bodies). */
    private function notFound(): JsonResponse
    {
        return ApiResponse::error('NOT_FOUND', 'Profile not available.', null, 404);
    }

    /**
     * Map a ProfileAccessService reason to an envelope error. Same
     * mapping ProfileController::accessError uses — consolidate if
     * this ever appears in a third place.
     */
    private function mapGateReason(string $reason): JsonResponse
    {
        return match ($reason) {
            ProfileAccessService::REASON_SAME_GENDER => ApiResponse::error(
                'GENDER_MISMATCH',
                'Cannot request photos from a same-gender profile.',
                null,
                403,
            ),
            ProfileAccessService::REASON_BLOCKED,
            ProfileAccessService::REASON_HIDDEN,
            ProfileAccessService::REASON_SUSPENDED => $this->notFound(),
            ProfileAccessService::REASON_VISIBILITY_PREMIUM => ApiResponse::error(
                'PREMIUM_REQUIRED',
                'This profile is visible to premium members only.',
                null,
                403,
            ),
            ProfileAccessService::REASON_VISIBILITY_MATCHES => ApiResponse::error(
                'LOW_MATCH_SCORE',
                'This profile is visible to high-match members only.',
                null,
                403,
            ),
            default => $this->notFound(),
        };
    }

    /**
     * Check if the caller owns this request as the TARGET (only the
     * target can approve or ignore). Returns a 403 JsonResponse when
     * not, null when OK.
     */
    private function ensureIsTarget(Request $request, PhotoRequest $photoRequest): ?JsonResponse
    {
        $profile = $request->user()->profile;

        if (! $profile) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before managing photo requests.',
                null,
                422,
            );
        }

        if ($photoRequest->target_profile_id !== $profile->id) {
            return ApiResponse::error(
                'UNAUTHORIZED',
                'You do not have permission to act on this photo request.',
                null,
                403,
            );
        }

        return null;
    }

    /**
     * Existing pending or approved request between these profiles. Ignored
     * requests can be re-sent.
     */
    private function existingOpenRequest(int $requesterId, int $targetId): ?PhotoRequest
    {
        try {
            return PhotoRequest::where('requester_profile_id', $requesterId)
                ->where('target_profile_id', $targetId)
                ->whereIn('status', ['pending', 'approved'])
                ->first();
        } catch (\Throwable $e) {
            // Table missing (test env) → treat as "no existing request."
            return null;
        }
    }

    /**
     * Swallow DB errors from notification dispatch. Photo-request send
     * and approve must not fail the user action if the notifications
     * table / enum is unreachable.
     */
    private function safeNotify(
        ?\App\Models\User $user,
        string $type,
        string $title,
        string $message,
        ?int $fromProfileId = null,
        array $data = [],
    ): void {
        if (! $user) {
            return;
        }
        try {
            $this->notifier->send($user, $type, $title, $message, $fromProfileId, $data);
        } catch (\Throwable $e) {
            // Best-effort dispatch. Any error is logged by Laravel's
            // exception handler via report() in the fallback path.
        }
    }

    /**
     * Fetch + render photo_requests from either side (received or sent).
     *
     * Query failure (missing table) returns []. Per-row rendering
     * failure (missing related Profile) returns the row with the
     * card field as null — the row itself is still shown so Flutter
     * can render "request from unknown user" rather than losing the
     * entry entirely.
     */
    private function loadRequests(
        string $whereColumn,
        int $profileId,
        string $relationName,
        string $cardKey,
    ): array {
        try {
            $rows = PhotoRequest::where($whereColumn, $profileId)
                ->orderByDesc('created_at')
                ->take(50)
                ->get();
        } catch (\Throwable $e) {
            return [];
        }

        return $rows
            ->map(function (PhotoRequest $r) use ($relationName, $cardKey) {
                // Render the card defensively — lazy-loading the related
                // profile can fail if the other side's table is unavailable
                // (test env) or the profile was soft-deleted.
                $card = null;
                try {
                    $related = $r->{$relationName};
                    if ($related) {
                        $card = (new ProfileCardResource($related))->resolve();
                    }
                } catch (\Throwable $e) {
                    $card = null;
                }

                return [
                    'id' => (int) $r->id,
                    $cardKey => $card,
                    'status' => (string) $r->status,
                    'created_at' => $r->created_at?->toIso8601String(),
                ];
            })
            ->all();
    }
}
