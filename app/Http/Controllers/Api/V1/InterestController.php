<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileCardResource;
use App\Http\Responses\ApiResponse;
use App\Models\Interest;
use App\Models\Profile;
use App\Services\InterestService;
use App\Services\ProfileAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Interest lifecycle endpoints — the conversation core of the matrimony
 * app.
 *
 *   GET    /api/v1/interests                            list with tabs
 *   GET    /api/v1/interests/{interest}                 single interest + replies
 *   POST   /api/v1/profiles/{matriId}/interest          send
 *   POST   /api/v1/interests/{interest}/accept          accept
 *   POST   /api/v1/interests/{interest}/decline         decline
 *   POST   /api/v1/interests/{interest}/cancel          cancel (within 24h)
 *   POST   /api/v1/interests/{interest}/star            toggle star
 *   POST   /api/v1/interests/{interest}/trash           toggle trash
 *   POST   /api/v1/interests/{interest}/messages        send chat reply
 *
 * Most of the heavy lifting (block check, daily limit, duplicate
 * detection, 30-day cooldown after decline, premium-or-Plus-tier check
 * for custom text + chat) lives in App\Services\InterestService. The
 * controller's job is request shaping + ownership/window guards +
 * error mapping.
 *
 * Service exceptions (\InvalidArgumentException, \RuntimeException) are
 * caught and rendered as 422 INVALID_INTEREST envelopes carrying the
 * exception's message — Flutter can show it verbatim. A future buffer
 * task can introduce a typed InterestException with stable error
 * codes; for MVP, the message-based contract is workable.
 *
 * Anti-enumeration: same pattern as step-5/13 — when the viewer isn't
 * a party to the interest, we return 403 (ownership) rather than 404
 * since the interest's existence is already implied by the URL.
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-01-interest-endpoints.md
 */
class InterestController extends BaseApiController
{
    /** Default per-page on the list endpoint. */
    public const DEFAULT_PER_PAGE = 20;

    /** Hard cap on per-page. */
    public const MAX_PER_PAGE = 50;

    /**
     * Window during which a sender can cancel a pending interest.
     * After this passes the interest is locked (Flutter shows
     * "cancellation window expired"). Sourced from config so admin
     * can tune; defaults to 24h to match the existing config key.
     */
    public function cancelWindowHours(): int
    {
        return (int) config('matrimony.cancel_interest_window_hours', 24);
    }

    public function __construct(
        private InterestService $interests,
        private ProfileAccessService $access,
    ) {}

    /* ==================================================================
     |  GET /interests
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Interests
     *
     * @queryParam tab string One of: all (default), received, sent, accepted, declined, starred, trash.
     * @queryParam per_page integer Default 20, max 50.
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": [{"id": 42, "direction": "received", "status": "pending"}],
     *   "meta": {"page": 1, "per_page": 20, "total": 12, "last_page": 1, "tab": "received"}
     * }
     *
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before viewing interests.',
                null,
                422,
            );
        }

        $tab = (string) $request->query('tab', 'all');
        $perPage = $this->resolvePerPage($request);

        $paginator = $this->executeListQuery($viewer, $tab, $perPage);

        // Render each Interest via the toCard helper. ApiResponse::paginated
        // doesn't know how to inject the viewer for shape rendering, so we
        // map manually + ship the items as a plain array. Meta is built
        // from the paginator.
        $items = collect($paginator->items())
            ->map(fn (Interest $i) => $this->toCard($i, $viewer))
            ->all();

        return ApiResponse::ok($items, [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'tab' => $tab,
        ]);
    }

    /**
     * Build the paginated query for the list endpoint. Protected seam
     * so tests can return a pre-built paginator without touching the
     * interests table.
     */
    protected function executeListQuery(Profile $viewer, string $tab, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Interest::query()->with(['senderProfile.user', 'receiverProfile.user', 'replies']);

        $this->applyTab($query, $viewer, $tab);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /** Apply tab-specific filters to the interests query. */
    private function applyTab(Builder $query, Profile $viewer, string $tab): void
    {
        match ($tab) {
            'received' => $query
                ->where('receiver_profile_id', $viewer->id)
                ->where('status', 'pending')
                ->where('is_trashed_by_receiver', false),

            'sent' => $query
                ->where('sender_profile_id', $viewer->id)
                ->where('status', 'pending')
                ->where('is_trashed_by_sender', false),

            'accepted' => $query
                ->where(function ($q) use ($viewer) {
                    $q->where('sender_profile_id', $viewer->id)
                        ->orWhere('receiver_profile_id', $viewer->id);
                })
                ->where('status', 'accepted'),

            'declined' => $query
                ->where(function ($q) use ($viewer) {
                    $q->where('sender_profile_id', $viewer->id)
                        ->orWhere('receiver_profile_id', $viewer->id);
                })
                ->where('status', 'declined'),

            'starred' => $query
                ->where(function ($q) use ($viewer) {
                    $q->where(function ($q2) use ($viewer) {
                        $q2->where('sender_profile_id', $viewer->id)
                            ->where('is_starred_by_sender', true);
                    })->orWhere(function ($q2) use ($viewer) {
                        $q2->where('receiver_profile_id', $viewer->id)
                            ->where('is_starred_by_receiver', true);
                    });
                }),

            'trash' => $query
                ->where(function ($q) use ($viewer) {
                    $q->where(function ($q2) use ($viewer) {
                        $q2->where('sender_profile_id', $viewer->id)
                            ->where('is_trashed_by_sender', true);
                    })->orWhere(function ($q2) use ($viewer) {
                        $q2->where('receiver_profile_id', $viewer->id)
                            ->where('is_trashed_by_receiver', true);
                    });
                }),

            // 'all' or unknown tab — every interest the viewer is party
            // to that hasn't been trashed by them. Includes pending,
            // accepted, declined, etc. Lets Flutter show a unified inbox.
            default => $query
                ->where(function ($q) use ($viewer) {
                    $q->where(function ($q2) use ($viewer) {
                        $q2->where('sender_profile_id', $viewer->id)
                            ->where('is_trashed_by_sender', false);
                    })->orWhere(function ($q2) use ($viewer) {
                        $q2->where('receiver_profile_id', $viewer->id)
                            ->where('is_trashed_by_receiver', false);
                    });
                }),
        };
    }

    /* ==================================================================
     |  GET /interests/{interest}
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Interests
     *
     * @urlParam interest integer required Interest id.
     *
     * @response 200 scenario="success" {"success": true, "data": {"id": 42, "status": "accepted", "replies": []}}
     * @response 403 scenario="not-party" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "..."}}
     * @response 404 scenario="not-found" {"success": false, "error": {"code": "NOT_FOUND", "message": "..."}}
     */
    public function show(Request $request, Interest $interest): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return $this->profileRequired();
        }

        if (! $this->isPartyTo($interest, $viewer)) {
            return $this->notParty();
        }

        $interest->loadMissing(['senderProfile.user', 'receiverProfile.user', 'replies']);

        return ApiResponse::ok($this->toCard($interest, $viewer));
    }

    /* ==================================================================
     |  POST /profiles/{matriId}/interest
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Interests
     *
     * @urlParam matriId string required Target's matri_id.
     *
     * @bodyParam template_id string Opaque template identifier (optional).
     * @bodyParam custom_message string Personalized text (optional, premium-gated unless target's plan has allows_free_member_chat=true).
     *
     * @response 201 scenario="success" {"success": true, "data": {"id": 42, "status": "pending"}}
     * @response 404 scenario="target-not-found" {"success": false, "error": {"code": "NOT_FOUND", "message": "Profile not available."}}
     * @response 422 scenario="invalid-interest" {"success": false, "error": {"code": "INVALID_INTEREST", "message": "Daily interest limit reached..."}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function send(Request $request, string $matriId): JsonResponse
    {
        $sender = $request->user()->profile;
        if (! $sender) {
            return $this->profileRequired();
        }

        $target = $this->findTargetByMatriId(strtoupper($matriId));
        if (! $target) {
            return $this->lookupNotFound();
        }

        $data = $request->validate([
            'template_id' => 'nullable|string|max:50',
            'custom_message' => 'nullable|string|max:500',
        ]);

        try {
            $interest = $this->interests->send(
                $sender,
                $target,
                $data['template_id'] ?? null,
                $data['custom_message'] ?? null,
            );
        } catch (\Throwable $e) {
            return $this->serviceError($e);
        }

        $interest->loadMissing(['senderProfile.user', 'receiverProfile.user', 'replies']);

        return ApiResponse::created($this->toCard($interest, $sender));
    }

    /* ==================================================================
     |  POST /interests/{interest}/accept
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Interests
     *
     * @bodyParam template_id string Optional reply template id.
     * @bodyParam custom_message string Optional acceptance message text.
     *
     * @response 200 scenario="success" {"success": true, "data": {"id": 42, "status": "accepted"}}
     * @response 403 scenario="not-receiver" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "..."}}
     * @response 422 scenario="not-pending" {"success": false, "error": {"code": "INVALID_INTEREST", "message": "..."}}
     */
    public function accept(Request $request, Interest $interest): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return $this->profileRequired();
        }

        // Only the receiver can accept.
        if ($interest->receiver_profile_id !== $viewer->id) {
            return $this->notParty();
        }

        if ($interest->status !== 'pending') {
            return ApiResponse::error(
                'INVALID_INTEREST',
                "This interest is already {$interest->status} and cannot be accepted.",
                null,
                422,
            );
        }

        $data = $request->validate([
            'template_id' => 'nullable|string|max:50',
            'custom_message' => 'nullable|string|max:500',
        ]);

        try {
            $this->interests->accept(
                $interest,
                $data['template_id'] ?? null,
                $data['custom_message'] ?? null,
            );
        } catch (\Throwable $e) {
            return $this->serviceError($e);
        }

        $interest->loadMissing(['senderProfile.user', 'receiverProfile.user', 'replies']);

        return ApiResponse::ok($this->toCard($interest, $viewer));
    }

    /* ==================================================================
     |  POST /interests/{interest}/decline
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Interests
     *
     * @bodyParam template_id string Optional reply template id.
     * @bodyParam custom_message string Optional decline message text.
     * @bodyParam silent boolean If true, no notification fires to the sender. Default false.
     *
     * @response 200 scenario="success" {"success": true, "data": {"id": 42, "status": "declined"}}
     * @response 403 scenario="not-receiver" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "..."}}
     * @response 422 scenario="not-pending" {"success": false, "error": {"code": "INVALID_INTEREST", "message": "..."}}
     */
    public function decline(Request $request, Interest $interest): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return $this->profileRequired();
        }

        if ($interest->receiver_profile_id !== $viewer->id) {
            return $this->notParty();
        }

        if ($interest->status !== 'pending') {
            return ApiResponse::error(
                'INVALID_INTEREST',
                "This interest is already {$interest->status} and cannot be declined.",
                null,
                422,
            );
        }

        $data = $request->validate([
            'template_id' => 'nullable|string|max:50',
            'custom_message' => 'nullable|string|max:500',
            'silent' => 'nullable|boolean',
        ]);

        try {
            $this->interests->decline(
                $interest,
                $data['template_id'] ?? null,
                $data['custom_message'] ?? null,
                (bool) ($data['silent'] ?? false),
            );
        } catch (\Throwable $e) {
            return $this->serviceError($e);
        }

        $interest->loadMissing(['senderProfile.user', 'receiverProfile.user', 'replies']);

        return ApiResponse::ok($this->toCard($interest, $viewer));
    }

    /* ==================================================================
     |  POST /interests/{interest}/cancel
     | ================================================================== */

    /**
     * Sender cancels a pending interest within the cancel window.
     *
     * @authenticated
     *
     * @group Interests
     *
     * @response 200 scenario="success" {"success": true, "data": {"id": 42, "status": "cancelled"}}
     * @response 403 scenario="not-sender" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "..."}}
     * @response 422 scenario="not-pending" {"success": false, "error": {"code": "INVALID_INTEREST", "message": "..."}}
     * @response 422 scenario="window-expired" {"success": false, "error": {"code": "CANCEL_WINDOW_EXPIRED", "message": "..."}}
     */
    public function cancel(Request $request, Interest $interest): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return $this->profileRequired();
        }

        // Only the SENDER can cancel a pending interest.
        if ($interest->sender_profile_id !== $viewer->id) {
            return $this->notParty();
        }

        if ($interest->status !== 'pending') {
            return ApiResponse::error(
                'INVALID_INTEREST',
                "This interest is already {$interest->status} and cannot be cancelled.",
                null,
                422,
            );
        }

        // 24h cancel window — sketch's rule (web doesn't enforce this).
        $window = $this->cancelWindowHours();
        $hoursElapsed = $interest->created_at?->diffInHours(Carbon::now()) ?? PHP_INT_MAX;
        if ($hoursElapsed >= $window) {
            return ApiResponse::error(
                'CANCEL_WINDOW_EXPIRED',
                "Interests can only be cancelled within {$window} hours of sending.",
                null,
                422,
            );
        }

        try {
            $this->interests->cancel($interest);
        } catch (\Throwable $e) {
            return $this->serviceError($e);
        }

        $interest->loadMissing(['senderProfile.user', 'receiverProfile.user', 'replies']);

        return ApiResponse::ok($this->toCard($interest, $viewer));
    }

    /* ==================================================================
     |  POST /interests/{interest}/star
     | ================================================================== */

    /**
     * Toggle the viewer-side star flag (is_starred_by_sender for the
     * sender, is_starred_by_receiver for the receiver). Used for
     * favourite-marking interests in the inbox.
     *
     * @authenticated
     *
     * @group Interests
     *
     * @response 200 scenario="success" {"success": true, "data": {"id": 42, "is_starred": true}}
     * @response 403 scenario="not-party" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "..."}}
     */
    public function star(Request $request, Interest $interest): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return $this->profileRequired();
        }

        if (! $this->isPartyTo($interest, $viewer)) {
            return $this->notParty();
        }

        // Toggle the column matching the viewer's role in this interest.
        $column = $interest->sender_profile_id === $viewer->id
            ? 'is_starred_by_sender'
            : 'is_starred_by_receiver';

        $interest->update([$column => ! $interest->{$column}]);

        return ApiResponse::ok([
            'id' => (int) $interest->id,
            'is_starred' => (bool) $interest->{$column},
        ]);
    }

    /* ==================================================================
     |  POST /interests/{interest}/trash
     | ================================================================== */

    /**
     * Toggle the viewer-side trash flag. Trashed interests are hidden
     * from the default "all" inbox tab but still visible in "trash".
     *
     * @authenticated
     *
     * @group Interests
     *
     * @response 200 scenario="success" {"success": true, "data": {"id": 42, "is_trashed": true}}
     * @response 403 scenario="not-party" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "..."}}
     */
    public function trash(Request $request, Interest $interest): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return $this->profileRequired();
        }

        if (! $this->isPartyTo($interest, $viewer)) {
            return $this->notParty();
        }

        $column = $interest->sender_profile_id === $viewer->id
            ? 'is_trashed_by_sender'
            : 'is_trashed_by_receiver';

        $interest->update([$column => ! $interest->{$column}]);

        return ApiResponse::ok([
            'id' => (int) $interest->id,
            'is_trashed' => (bool) $interest->{$column},
        ]);
    }

    /* ==================================================================
     |  POST /interests/{interest}/messages
     | ================================================================== */

    /**
     * Send a chat reply in an accepted interest thread. Premium-gated
     * via InterestService::sendMessage — but if the OTHER party holds
     * a plan with allows_free_member_chat=true, free senders may also
     * reply (Bharat-Platinum convention; see Commit A).
     *
     * @authenticated
     *
     * @group Interests
     *
     * @bodyParam message string required Message text (1-2000 chars).
     *
     * @response 201 scenario="success" {"success": true, "data": {"reply_id": 7, "interest_id": 42}}
     * @response 403 scenario="not-party" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "..."}}
     * @response 422 scenario="not-accepted" {"success": false, "error": {"code": "INVALID_INTEREST", "message": "..."}}
     * @response 422 scenario="not-premium" {"success": false, "error": {"code": "INVALID_INTEREST", "message": "Upgrade to a paid plan to send messages."}}
     * @response 429 scenario="throttled" {"success": false, "error": {"code": "THROTTLED", "message": "..."}}
     */
    public function reply(Request $request, Interest $interest): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return $this->profileRequired();
        }

        if (! $this->isPartyTo($interest, $viewer)) {
            return $this->notParty();
        }

        $data = $request->validate([
            'message' => 'required|string|min:1|max:2000',
        ]);

        try {
            $reply = $this->interests->sendMessage($interest, $viewer, $data['message']);
        } catch (\Throwable $e) {
            return $this->serviceError($e);
        }

        return ApiResponse::created([
            'reply_id' => (int) $reply->id,
            'interest_id' => (int) $interest->id,
        ]);
    }

    /* ==================================================================
     |  GET /interests/{interest}/messages — chat polling
     | ================================================================== */

    /** Hard cap on per-poll reply count — guards against abusive dumps. */
    public const MESSAGES_MAX_LIMIT = 100;

    /** Default per-poll reply count. */
    public const MESSAGES_DEFAULT_LIMIT = 50;

    /**
     * List replies in an interest thread (chat polling). Flutter calls
     * this on a timer (~10s) while the chat screen is open.
     *
     * Cursor-style pagination via `?after=N` — returns replies with
     * `id > after`, ordered by id ascending. Initial poll uses
     * `?after=0` (or omits) to fetch the whole history; subsequent
     * polls send the previous response's `latest_message_id`.
     *
     * Returns the current thread status alongside so Flutter can react
     * if the interest was blocked / cancelled while the user was idle.
     *
     * Pairs with POST /interests/{interest}/messages from step-1 —
     * write counterpart is `reply()`, read counterpart is this method.
     *
     * @authenticated
     *
     * @group Interests
     *
     * @urlParam interest integer required Interest id.
     *
     * @queryParam after integer Return replies with id > this. Default 0 (full history).
     * @queryParam limit integer Default 50, max 100.
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "replies": [
     *       {"id": 7, "from": "me", "type": "message", "template_id": null, "text": "Hi!", "created_at": "2026-04-25T..."}
     *     ],
     *     "latest_message_id": 7,
     *     "thread_status": "accepted",
     *     "polled_at": "2026-04-25T..."
     *   }
     * }
     *
     * @response 403 scenario="not-party" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "..."}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     * @response 429 scenario="throttled" {"success": false, "error": {"code": "THROTTLED", "message": "..."}}
     */
    public function messages(Request $request, Interest $interest): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return $this->profileRequired();
        }

        if (! $this->isPartyTo($interest, $viewer)) {
            return $this->notParty();
        }

        $after = max((int) $request->query('after', 0), 0);
        $limit = min(
            max((int) $request->query('limit', self::MESSAGES_DEFAULT_LIMIT), 1),
            self::MESSAGES_MAX_LIMIT,
        );

        $replies = $interest->replies()
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $latestId = $replies->isNotEmpty()
            ? (int) $replies->last()->id
            : $after;  // empty page → echo input cursor so next poll re-sends same `?after`

        // Re-query the interest's status so Flutter can react if the
        // thread changed state while the user was idle. Use a column-only
        // query instead of $interest->refresh() — refresh() reloads
        // every relation that's been touched (sender / receiver profile,
        // replies), which would unnecessarily query the profiles table
        // each poll.
        $threadStatus = (string) (Interest::whereKey($interest->id)->value('status')
            ?? $interest->status);

        return ApiResponse::ok([
            'replies' => $replies
                ->map(fn ($r) => $this->renderPollReply($r, $viewer))
                ->values()
                ->all(),
            'latest_message_id' => $latestId,
            'thread_status' => (string) $threadStatus,
            'polled_at' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * Render a single InterestReply for the polling response. Adds the
     * viewer-relative `from` field ("me" vs "them") + maps the
     * custom_message column to the Flutter-friendly `text` key.
     */
    private function renderPollReply($reply, Profile $viewer): array
    {
        return [
            'id' => (int) $reply->id,
            'from' => $reply->replier_profile_id === $viewer->id ? 'me' : 'them',
            'type' => (string) $reply->reply_type,
            'template_id' => $reply->template_id,
            'text' => $reply->custom_message,
            'created_at' => $reply->created_at?->toIso8601String(),
        ];
    }

    /* ==================================================================
     |  Helpers — shape, guards, lookups
     | ================================================================== */

    /**
     * Render a single Interest into the API card shape. Direction is
     * computed relative to the viewer (sent vs received). Star/trash
     * flags resolve to the viewer's column.
     */
    private function toCard(Interest $interest, Profile $viewer): array
    {
        $isSender = $interest->sender_profile_id === $viewer->id;
        $other = $isSender ? $interest->receiverProfile : $interest->senderProfile;

        $isStarred = $isSender
            ? (bool) $interest->is_starred_by_sender
            : (bool) $interest->is_starred_by_receiver;
        $isTrashed = $isSender
            ? (bool) $interest->is_trashed_by_sender
            : (bool) $interest->is_trashed_by_receiver;

        // can_cancel: sender-only, status=pending, within window.
        $canCancel = false;
        if ($isSender && $interest->status === 'pending' && $interest->created_at) {
            $hoursElapsed = $interest->created_at->diffInHours(Carbon::now());
            $canCancel = $hoursElapsed < $this->cancelWindowHours();
        }

        // can_act: receiver-only, status=pending — accept/decline available.
        $canAct = ! $isSender && $interest->status === 'pending';

        return [
            'id' => (int) $interest->id,
            'direction' => $isSender ? 'sent' : 'received',
            'status' => (string) $interest->status,
            'template_id' => $interest->template_id,
            'custom_message' => $interest->custom_message,
            'is_starred' => $isStarred,
            'is_trashed' => $isTrashed,
            'can_cancel' => $canCancel,
            'can_act' => $canAct,
            'is_chat_open' => $interest->status === 'accepted',
            'other_profile' => $other
                ? (new ProfileCardResource($other))->resolve()
                : null,
            'replies' => $this->renderReplies($interest),
            'created_at' => $interest->created_at?->toIso8601String(),
            'updated_at' => $interest->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Render the replies collection. Uses the `replies` relation (which
     * the controller eager-loads). Empty array when no replies.
     */
    private function renderReplies(Interest $interest): array
    {
        $replies = $interest->relationLoaded('replies')
            ? $interest->replies
            : collect();

        return $replies
            ->map(fn ($reply) => [
                'id' => (int) $reply->id,
                'type' => (string) $reply->reply_type,
                'replier_profile_id' => (int) $reply->replier_profile_id,
                'template_id' => $reply->template_id,
                'text' => $reply->custom_message,
                'is_silent_decline' => (bool) ($reply->is_silent_decline ?? false),
                'created_at' => $reply->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /** Is the viewer a party (sender or receiver) to the interest? */
    private function isPartyTo(Interest $interest, Profile $viewer): bool
    {
        return $interest->sender_profile_id === $viewer->id
            || $interest->receiver_profile_id === $viewer->id;
    }

    /** Find target Profile by matri_id — protected seam for tests. */
    protected function findTargetByMatriId(string $matriId): ?Profile
    {
        try {
            return Profile::where('matri_id', $matriId)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Resolve per_page from request; clamp [1, MAX_PER_PAGE]. */
    private function resolvePerPage(Request $request): int
    {
        return min(
            max((int) $request->query('per_page', self::DEFAULT_PER_PAGE), 1),
            self::MAX_PER_PAGE,
        );
    }

    private function profileRequired(): JsonResponse
    {
        return ApiResponse::error(
            'PROFILE_REQUIRED',
            'Complete registration before using interests.',
            null,
            422,
        );
    }

    private function notParty(): JsonResponse
    {
        return ApiResponse::error(
            'UNAUTHORIZED',
            'You do not have permission to act on this interest.',
            null,
            403,
        );
    }

    private function lookupNotFound(): JsonResponse
    {
        return ApiResponse::error(
            'NOT_FOUND',
            'Profile not available.',
            null,
            404,
        );
    }

    /**
     * Map a service-thrown exception (\InvalidArgumentException or
     * \RuntimeException, depending on the rule that fired) to a 422
     * envelope with the message verbatim. Future buffer work can add
     * a typed InterestException with stable error codes.
     */
    private function serviceError(\Throwable $e): JsonResponse
    {
        return ApiResponse::error(
            'INVALID_INTEREST',
            $e->getMessage(),
            null,
            422,
        );
    }
}
