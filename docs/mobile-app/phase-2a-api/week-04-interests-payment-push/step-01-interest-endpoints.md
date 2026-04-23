# Step 1 — Interest Endpoints (Inbox + Lifecycle)

## Goal
All 10 interest endpoints: list (with tabs), show one, send, accept, decline, cancel, star, trash, reply, templates.

**Design ref:** [`design/07-interests-chat-api.md`](../../design/07-interests-chat-api.md)

## Procedure

### 1. `InterestController`

`app/Http/Controllers/Api/V1/InterestController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Http\Resources\V1\ProfileCardResource;
use App\Models\Interest;
use App\Models\Profile;
use App\Services\InterestService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterestController extends BaseApiController
{
    public function __construct(
        private InterestService $interests,
        private NotificationService $notifications,
    ) {}

    /**
     * @authenticated
     * @group Interests
     */
    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;
        $tab = $request->query('tab', 'received');

        $query = Interest::query()
            ->with(['senderProfile.primaryPhoto', 'receiverProfile.primaryPhoto', 'latestReply']);

        match ($tab) {
            'received' => $query->where('receiver_profile_id', $profile->id)->where('status', 'pending')->where('is_trashed_by_receiver', false),
            'sent' => $query->where('sender_profile_id', $profile->id)->where('status', 'pending')->where('is_trashed_by_sender', false),
            'accepted' => $query->where('status', 'accepted')
                ->where(function ($q) use ($profile) {
                    $q->where('sender_profile_id', $profile->id)->orWhere('receiver_profile_id', $profile->id);
                }),
            'declined' => $query->where('status', 'declined')
                ->where(function ($q) use ($profile) {
                    $q->where('sender_profile_id', $profile->id)->orWhere('receiver_profile_id', $profile->id);
                }),
            'starred' => $query->where(function ($q) use ($profile) {
                $q->where('sender_profile_id', $profile->id)->where('is_starred_by_sender', true)
                  ->orWhere(function ($q2) use ($profile) {
                      $q2->where('receiver_profile_id', $profile->id)->where('is_starred_by_receiver', true);
                  });
            }),
            'trash' => $query->where(function ($q) use ($profile) {
                $q->where('sender_profile_id', $profile->id)->where('is_trashed_by_sender', true)
                  ->orWhere(function ($q2) use ($profile) {
                      $q2->where('receiver_profile_id', $profile->id)->where('is_trashed_by_receiver', true);
                  });
            }),
            default => $query->where(function ($q) use ($profile) {
                $q->where('sender_profile_id', $profile->id)->orWhere('receiver_profile_id', $profile->id);
            }),
        };

        $paginator = $query->latest()->paginate(20);

        // Transform each into the API shape
        $items = $paginator->getCollection()->map(fn ($i) => $this->toCard($i, $profile));

        return ApiResponse::ok($items->values(), [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'counts' => $this->interests->countsFor($profile),
        ]);
    }

    private function toCard(Interest $interest, Profile $viewer): array
    {
        $isSender = $interest->sender_profile_id === $viewer->id;
        $otherParty = $isSender ? $interest->receiverProfile : $interest->senderProfile;

        return [
            'id' => $interest->id,
            'status' => $interest->status,
            'direction' => $isSender ? 'sent' : 'received',
            'other_party' => (new ProfileCardResource($otherParty))->resolve(),
            'message' => $interest->custom_message ?? $interest->template?->text,
            'latest_reply' => $interest->latestReply ? [
                'text' => $interest->latestReply->message_text,
                'from' => $interest->latestReply->replier_profile_id === $viewer->id ? 'me' : 'them',
                'at' => $interest->latestReply->created_at->toIso8601String(),
            ] : null,
            'unread_reply_count' => $this->interests->unreadReplyCountFor($interest, $viewer),
            'is_starred' => $isSender ? $interest->is_starred_by_sender : $interest->is_starred_by_receiver,
            'can_cancel' => $isSender && $interest->status === 'pending' && $interest->created_at->diffInHours() < config('matrimony.cancel_interest_window_hours', 24),
            'created_at' => $interest->created_at->toIso8601String(),
            'expires_at' => $interest->expires_at?->toIso8601String(),
        ];
    }

    /**
     * @authenticated
     * @group Interests
     */
    public function show(Request $request, Interest $interest): JsonResponse
    {
        $viewer = $request->user()->profile;
        abort_unless(in_array($viewer->id, [$interest->sender_profile_id, $interest->receiver_profile_id]), 403);

        // Mark seen
        if ($viewer->id === $interest->sender_profile_id) {
            $interest->update(['last_seen_by_sender_at' => now()]);
        } else {
            $interest->update(['last_seen_by_receiver_at' => now()]);
        }

        $interest->load(['senderProfile.primaryPhoto', 'receiverProfile.primaryPhoto', 'replies.replierProfile']);

        return ApiResponse::ok([
            'interest' => array_merge(
                $this->toCard($interest, $viewer),
                [
                    'replies' => $interest->replies->map(fn ($r) => [
                        'id' => $r->id,
                        'from' => $r->replier_profile_id === $viewer->id ? 'me' : 'them',
                        'text' => $r->message_text,
                        'created_at' => $r->created_at->toIso8601String(),
                    ]),
                    'can_reply' => $interest->status === 'accepted' && $viewer->user?->activeMembership,
                    'accepted_at' => $interest->accepted_at?->toIso8601String(),
                ],
            ),
        ]);
    }

    /**
     * @authenticated
     * @group Interests
     */
    public function send(Request $request, string $matriId): JsonResponse
    {
        $data = $request->validate([
            'template_id' => 'nullable|integer',
            'custom_message' => 'nullable|string|max:500',
        ]);

        $target = Profile::where('matri_id', $matriId)->firstOrFail();
        $sender = $request->user()->profile;

        try {
            $interest = $this->interests->send($sender, $target, $data);
        } catch (\App\Exceptions\InterestException $e) {
            return ApiResponse::error($e->code, $e->getMessage(), status: $e->status);
        }

        return ApiResponse::created([
            'interest' => $this->toCard($interest, $sender),
            'daily_usage' => $this->interests->dailyUsage($sender),
        ]);
    }

    /**
     * @authenticated
     * @group Interests
     */
    public function accept(Request $request, Interest $interest): JsonResponse
    {
        abort_if($interest->receiver_profile_id !== $request->user()->profile->id, 403);
        abort_if($interest->status !== 'pending', 422);

        $this->interests->accept($interest);
        return ApiResponse::ok(['interest_id' => $interest->id, 'status' => 'accepted']);
    }

    /**
     * @authenticated
     * @group Interests
     */
    public function decline(Request $request, Interest $interest): JsonResponse
    {
        abort_if($interest->receiver_profile_id !== $request->user()->profile->id, 403);
        abort_if($interest->status !== 'pending', 422);

        $this->interests->decline($interest);
        return ApiResponse::ok(['interest_id' => $interest->id, 'status' => 'declined']);
    }

    /**
     * @authenticated
     * @group Interests
     */
    public function cancel(Request $request, Interest $interest): JsonResponse
    {
        abort_if($interest->sender_profile_id !== $request->user()->profile->id, 403);
        abort_if($interest->status !== 'pending', 422);

        $windowHours = config('matrimony.cancel_interest_window_hours', 24);
        abort_if($interest->created_at->diffInHours() >= $windowHours, 422, "Cancel window passed");

        $interest->delete();
        return ApiResponse::ok(['cancelled' => true]);
    }

    public function star(Request $request, Interest $interest): JsonResponse
    {
        $viewer = $request->user()->profile;
        abort_unless(in_array($viewer->id, [$interest->sender_profile_id, $interest->receiver_profile_id]), 403);

        $col = $viewer->id === $interest->sender_profile_id ? 'is_starred_by_sender' : 'is_starred_by_receiver';
        $interest->update([$col => ! $interest->$col]);

        return ApiResponse::ok(['interest_id' => $interest->id, 'is_starred' => $interest->$col]);
    }

    public function trash(Request $request, Interest $interest): JsonResponse
    {
        $viewer = $request->user()->profile;
        abort_unless(in_array($viewer->id, [$interest->sender_profile_id, $interest->receiver_profile_id]), 403);

        $col = $viewer->id === $interest->sender_profile_id ? 'is_trashed_by_sender' : 'is_trashed_by_receiver';
        $interest->update([$col => ! $interest->$col]);

        return ApiResponse::ok(['interest_id' => $interest->id, 'is_trashed' => $interest->$col]);
    }

    public function reply(Request $request, Interest $interest): JsonResponse
    {
        $data = $request->validate(['message' => 'required|string|max:500']);
        $viewer = $request->user()->profile;

        abort_unless(in_array($viewer->id, [$interest->sender_profile_id, $interest->receiver_profile_id]), 403);
        abort_if($interest->status !== 'accepted', 422);
        abort_if(! $viewer->user?->activeMembership, 403, 'Premium required for chat');

        $otherProfile = $viewer->id === $interest->sender_profile_id ? $interest->receiverProfile : $interest->senderProfile;
        abort_if(! $otherProfile->user?->activeMembership, 403, 'Both parties must be premium');

        $reply = \App\Models\InterestReply::create([
            'interest_id' => $interest->id,
            'replier_profile_id' => $viewer->id,
            'message_text' => $data['message'],
        ]);

        $this->notifications->dispatch(
            $otherProfile->user,
            'interest.reply',
            'New message',
            ($viewer->full_name ?? 'Someone') . ': ' . \Illuminate\Support\Str::limit($data['message'], 50),
            ['interest_id' => $interest->id, 'deep_link' => "/interests/{$interest->id}"],
        );

        return ApiResponse::created([
            'reply' => [
                'id' => $reply->id,
                'from' => 'me',
                'text' => $reply->message_text,
                'created_at' => $reply->created_at->toIso8601String(),
            ],
        ]);
    }
}
```

> **Note:** this step assumes `InterestService` has `send`, `accept`, `decline`, `countsFor`, `dailyUsage`, `unreadReplyCountFor` methods. If missing, extend it first.

### 2. Routes

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/interests', [\App\Http\Controllers\Api\V1\InterestController::class, 'index']);
    Route::get('/interests/{interest}', [\App\Http\Controllers\Api\V1\InterestController::class, 'show']);
    Route::post('/profiles/{matriId}/interest', [\App\Http\Controllers\Api\V1\InterestController::class, 'send']);
    Route::post('/interests/{interest}/accept', [\App\Http\Controllers\Api\V1\InterestController::class, 'accept']);
    Route::post('/interests/{interest}/decline', [\App\Http\Controllers\Api\V1\InterestController::class, 'decline']);
    Route::post('/interests/{interest}/cancel', [\App\Http\Controllers\Api\V1\InterestController::class, 'cancel']);
    Route::post('/interests/{interest}/star', [\App\Http\Controllers\Api\V1\InterestController::class, 'star']);
    Route::post('/interests/{interest}/trash', [\App\Http\Controllers\Api\V1\InterestController::class, 'trash']);
    Route::post('/interests/{interest}/messages', [\App\Http\Controllers\Api\V1\InterestController::class, 'reply'])->middleware('throttle:30,60');
});
```

## Verification

- [ ] All 10 endpoints work via curl
- [ ] Daily limit enforced on send (free user 5/day, premium tiered)
- [ ] Self-send blocked (400)
- [ ] Same-gender blocked (403)
- [ ] Accept only by receiver
- [ ] Cancel only within 24h window
- [ ] Reply requires both parties premium

## Commit

```bash
git add app/Http/Controllers/Api/V1/InterestController.php routes/api.php
git commit -m "phase-2a wk-04: step-01 interest lifecycle endpoints"
```

## Next step
→ [step-02-chat-polling.md](step-02-chat-polling.md)
