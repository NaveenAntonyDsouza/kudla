<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\NotificationResource;
use App\Http\Responses\ApiResponse;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-side of the notifications surface.
 *
 *   GET  /api/v1/notifications              paginated list (?filter=unread, ?page, ?per_page)
 *   GET  /api/v1/notifications/unread-count badge sync
 *   POST /api/v1/notifications/{notification}/read   mark single read
 *   POST /api/v1/notifications/read-all     bulk mark read
 *
 * The ENVELOPE meta on the index endpoint carries `unread_count` so
 * Flutter can refresh the badge from the same payload it already
 * fetched — no second roundtrip. The /unread-count endpoint stays
 * around for the case where the list isn't open (badge polling).
 *
 * Mutation endpoints (read / read-all) delegate to NotificationService
 * so future side effects (cache busts, push-state cleanup, etc.) live
 * in one place.
 *
 * Anti-enumeration: mark-read returns 403 (not 404) when the
 * notification belongs to another user — implicit 404 on missing row
 * is still surfaced via Laravel route-model binding.
 *
 * Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-08-notification-endpoints.md
 */
class NotificationController extends BaseApiController
{
    /** Default per-page for the index endpoint. Cap matches the rest of v1. */
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 50;

    public function __construct(private NotificationService $notificationService) {}

    /* ==================================================================
     |  GET /notifications
     | ================================================================== */

    /**
     * Paginated list of the viewer's notifications, latest first.
     *
     * @authenticated
     *
     * @group Notifications
     *
     * @queryParam filter string Optional. "unread" to limit to unread items.
     * @queryParam page integer Optional. Pagination page. Default 1.
     * @queryParam per_page integer Optional. Items per page (1-50). Default 20.
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": [{"id": 1, "type": "interest_received", "title": "...", "message": "...", "data": {"interest_id": 42}, "is_read": false, "created_at": "2026-04-26T...", "icon_type": "interest", "from_profile_id": 7}],
     *   "meta": {"page": 1, "per_page": 20, "total": 1, "last_page": 1, "unread_count": 1}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $perPage = max(1, min(self::MAX_PER_PAGE, (int) $request->query('per_page', self::DEFAULT_PER_PAGE)));

        $query = Notification::query()->where('user_id', $userId);
        if ($request->query('filter') === 'unread') {
            $query->where('is_read', false);
        }

        $paginator = $query->latest()->paginate($perPage);

        return ApiResponse::paginated($paginator, NotificationResource::class, [
            'unread_count' => $this->notificationService->getUnreadCount($request->user()),
        ]);
    }

    /* ==================================================================
     |  GET /notifications/unread-count
     | ================================================================== */

    /**
     * Quick unread-count for the badge. Cheap query — backed by the
     * `(user_id, is_read)` index on the notifications table.
     *
     * @authenticated
     *
     * @group Notifications
     *
     * @response 200 scenario="success" {"success": true, "data": {"unread_count": 12}}
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return ApiResponse::ok([
            'unread_count' => $this->notificationService->getUnreadCount($request->user()),
        ]);
    }

    /* ==================================================================
     |  POST /notifications/{notification}/read
     | ================================================================== */

    /**
     * Mark a single notification read. Idempotent — no-ops on an
     * already-read row, still returns 200. Returns 403 (not 404) when
     * the notification belongs to a different user, to avoid leaking
     * which ids exist.
     *
     * @authenticated
     *
     * @group Notifications
     *
     * @urlParam notification integer required Notification id.
     *
     * @response 200 scenario="success" {"success": true, "data": {"notification": {"id": 1, "is_read": true}}}
     * @response 403 scenario="not-owner" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "..."}}
     * @response 404 scenario="not-found" {"success": false, "error": {"code": "NOT_FOUND", "message": "..."}}
     */
    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return ApiResponse::error(
                'UNAUTHORIZED',
                'You do not have permission to mark this notification.',
                null,
                403,
            );
        }

        $this->notificationService->markAsRead($notification);

        return ApiResponse::ok([
            'notification' => [
                'id' => (int) $notification->id,
                'is_read' => true,
            ],
        ]);
    }

    /* ==================================================================
     |  POST /notifications/read-all
     | ================================================================== */

    /**
     * Mark every unread notification for the viewer as read. Returns
     * the count that flipped — 0 when the inbox is already empty,
     * useful for clients to skip a needless badge refresh.
     *
     * @authenticated
     *
     * @group Notifications
     *
     * @response 200 scenario="success" {"success": true, "data": {"marked_read": 12}}
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        $this->notificationService->markAllAsRead($request->user());

        return ApiResponse::ok(['marked_read' => $count]);
    }
}
