# Step 2 — Chat Polling Endpoint

## Goal
`GET /api/v1/interests/{interest}/messages/since/{messageId?}` — returns messages after the given id. Flutter polls every 10s when chat screen open.

## Procedure

### 1. Add `since` method to `InterestController`

```php
/**
 * @authenticated
 * @group Interests
 */
public function since(Request $request, Interest $interest, ?int $messageId = null): JsonResponse
{
    $viewer = $request->user()->profile;
    abort_unless(in_array($viewer->id, [$interest->sender_profile_id, $interest->receiver_profile_id]), 403);

    $query = $interest->replies()->with('replierProfile');
    if ($messageId !== null) {
        $query->where('id', '>', $messageId);
    }
    $replies = $query->orderBy('id')->get();

    $latestId = $replies->isNotEmpty() ? $replies->last()->id : ($messageId ?? 0);

    return ApiResponse::ok([
        'replies' => $replies->map(fn ($r) => [
            'id' => $r->id,
            'from' => $r->replier_profile_id === $viewer->id ? 'me' : 'them',
            'text' => $r->message_text,
            'created_at' => $r->created_at->toIso8601String(),
        ]),
        'latest_message_id' => $latestId,
        'thread_status' => $interest->fresh()->status,
    ]);
}
```

### 2. Route

```php
Route::get('/interests/{interest}/messages/since/{messageId?}',
    [\App\Http\Controllers\Api\V1\InterestController::class, 'since']
)->where('messageId', '[0-9]+');
```

### 3. Test

```bash
# Get new messages since ID 422
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/interests/89/messages/since/422 | jq
```

## Verification

- [ ] Empty array returned when no new messages
- [ ] New replies returned in chronological order
- [ ] `thread_status` reflects current interest status

## Commit

```bash
git commit -am "phase-2a wk-04: step-02 chat polling endpoint"
```

## Next step
→ [step-03-membership-plans-coupon.md](step-03-membership-plans-coupon.md)
