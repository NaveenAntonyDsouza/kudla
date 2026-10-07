<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ProfileCardResource;
use App\Http\Responses\ApiResponse;
use App\Models\Interest;
use App\Support\Chats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Chats (Oct 2026) — the same split as the website: accepted interests are
 * conversations, listed apart from interest requests. The thread itself is
 * still GET /interests/{id} + /interests/{id}/messages (both mark it read).
 */
class ChatController extends Controller
{
    /**
     * Accepted conversations, newest activity first.
     *
     * @authenticated
     *
     * @group Chats
     *
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="success" {"success": true, "data": [{"interest_id": 42, "other_profile": {"matri_id": "KM100512"}, "last_message": "Shall our parents talk on Sunday?", "last_message_from": "them", "last_activity_at": "2026-10-07T11:00:00Z", "unread_count": 1}], "meta": {"page": 1, "per_page": 20, "total": 1, "last_page": 1}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function index(Request $request): JsonResponse
    {
        $me = $request->user()->profile;
        if (! $me) {
            return ApiResponse::error('PROFILE_REQUIRED', 'Complete registration before viewing chats.', null, 422);
        }

        $chats = Chats::forProfile($me);

        $items = collect($chats->items())->map(function (Interest $chat) use ($me) {
            $other = $chat->sender_profile_id === $me->id ? $chat->receiverProfile : $chat->senderProfile;
            $last = $chat->latestReply;

            return [
                'interest_id' => (int) $chat->id,
                'other_profile' => $other ? (new ProfileCardResource($other))->resolve() : null,
                'last_message' => Chats::preview($chat, $me, withYouPrefix: false),
                'last_message_from' => $last ? ($last->replier_profile_id === $me->id ? 'me' : 'them') : null,
                'last_activity_at' => $chat->last_activity_at ? Carbon::parse($chat->last_activity_at)->toUtcIso() : null,
                'unread_count' => (int) $chat->unread_count,
            ];
        })->all();

        return ApiResponse::ok($items, [
            'page' => $chats->currentPage(),
            'per_page' => $chats->perPage(),
            'total' => $chats->total(),
            'last_page' => $chats->lastPage(),
        ]);
    }

    /**
     * Counts for the app's tab badges: interests waiting for a reply, and
     * conversations with something unread.
     *
     * @authenticated
     *
     * @group Chats
     *
     * @response 200 scenario="success" {"success": true, "data": {"interests_pending": 3, "chats_unread": 1}}
     */
    public function badges(Request $request): JsonResponse
    {
        $me = $request->user()->profile;
        if (! $me) {
            return ApiResponse::ok(['interests_pending' => 0, 'chats_unread' => 0]);
        }

        return ApiResponse::ok([
            'interests_pending' => Chats::pendingInterestsCount($me),
            'chats_unread' => Chats::unreadCount($me),
        ]);
    }
}
