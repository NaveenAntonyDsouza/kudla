# 7. Interests & Chat API

The #1 engagement feature in matrimony. Covers: send, accept, decline, cancel, star, trash, list (with tabs), daily limit check, chat replies, polling contract.

**Source:** `App\Http\Controllers\InterestController`, `App\Services\InterestService`, `App\Models\Interest`, `App\Models\InterestReply`, `App\Models\DailyInterestUsage`.

**Chat note:** chat is NOT a separate feature — it's replies within an `accepted` interest thread. No standalone "chat" controller or model exists.

---

## 7.1 Data shape refresh

### Interest
- `sender_profile_id`, `receiver_profile_id`, `status` (pending|accepted|declined|expired), `is_starred_by_sender`, `is_starred_by_receiver`, `is_trashed_by_sender`, `is_trashed_by_receiver`, timestamps

### InterestReply
- `interest_id`, `replier_profile_id` (sender OR receiver), `message_text`, `created_at`

### DailyInterestUsage
- `profile_id`, `date`, `sent_count`

---

## 7.2 Daily limits

| Plan | Daily send cap |
|------|----------------|
| Free | 5 (from `config/matrimony.php daily_interest_limit_free`) |
| Silver | plan's `daily_interest_limit` (typically 10) |
| Gold | typically 20 |
| Diamond | typically 50 |
| Diamond Plus | typically 100 or unlimited |

Enforced by `InterestService::canSendToday(Profile $profile): array`:
```php
['can_send' => bool, 'limit' => int, 'used' => int, 'resets_at' => Carbon]
```

---

## 7.3 `GET /api/v1/interests`

Returns interest inbox with tab filtering.

### Query params
```
tab=received                       (all|received|sent|accepted|declined|expired|starred|trash — default: received)
page=1
per_page=20
```

### Response
```json
{
  "success": true,
  "data": [
    {
      "id": 89,
      "status": "pending",
      "direction": "received",              // sent | received
      "other_party": { /* ProfileCardResource */ },
      "message": "Hi, I'd like to connect.",  // from custom_message or template
      "latest_reply": null,                 // or { text, from, at } if accepted + has replies
      "unread_reply_count": 0,
      "is_starred": false,
      "is_trashed": false,
      "can_cancel": true,                   // within 24h window + direction=sent + status=pending
      "created_at": "2026-04-22T18:00:00Z",
      "expires_at": "2026-05-22T18:00:00Z"
    }
  ],
  "meta": {
    "page": 1, "per_page": 20, "total": 47, "last_page": 3,
    "counts": {
      "received_pending": 3,
      "sent_pending": 2,
      "accepted_both": 8,
      "declined_total": 12
    }
  }
}
```

**Sort:** newest first within each tab.

**`unread_reply_count`:** counts replies in this thread where `created_at > user.last_seen_interest_{id}`. Flutter tracks per-thread read state by calling `GET /interests/{interest}` — see §7.9.

---

## 7.4 `GET /api/v1/interests/{interest}`

Full thread view. Also marks all existing replies as "seen" for this viewer.

### Response
```json
{
  "success": true,
  "data": {
    "interest": {
      "id": 89,
      "status": "accepted",
      "direction": "sent",
      "other_party": { /* ProfileCardResource */ },
      "initial_message": "Hi, I'd like to connect.",
      "is_starred": false,
      "can_reply": true,                    // status=accepted AND auth user is premium (see 7.10)
      "can_cancel": false,
      "can_decline_anymore": false,
      "created_at": "2026-04-22T18:00:00Z",
      "accepted_at": "2026-04-22T19:30:00Z",
      "expires_at": "2026-05-22T18:00:00Z",
      "replies": [
        {
          "id": 421,
          "from": "them",                   // "me" | "them"
          "text": "Thanks for reaching out!",
          "created_at": "2026-04-22T19:32:00Z"
        },
        {
          "id": 422,
          "from": "me",
          "text": "Would love to chat more.",
          "created_at": "2026-04-22T19:45:00Z"
        }
      ]
    }
  }
}
```

**Gender gate** applies — 403 if viewer tries to open a same-gender thread (shouldn't be possible but defensive).

---

## 7.5 `POST /api/v1/profiles/{matriId}/interest` — Send

### Request
```json
{
  "template_id": 3,               // optional; pre-canned template from admin
  "custom_message": null          // optional; premium-only; max 500 chars
}
```

### Server-side checks (exact order)
1. Target `matriId` exists → else 404
2. Target is not self → else 400 `SELF_ACTION`
3. Target is opposite gender → else 403 `GENDER_MISMATCH`
4. Neither party has blocked the other → else 403 `NOT_FOUND` (mask the block)
5. No existing `pending` or `accepted` interest between them → else 409 `ALREADY_EXISTS`
6. If previously declined: wait ≥ `resend_interest_cooldown_days` (30d) → else 409 with code `COOLDOWN`
7. If receiver previously sent to sender: block — encourage to accept that one → 409 `REVERSE_EXISTS`
8. Daily limit check → 429 `DAILY_LIMIT_REACHED` with `{limit, used, resets_at}`
9. If `custom_message` present, require premium → 403 `UNAUTHORIZED` with `{code: "PREMIUM_REQUIRED"}`
10. Create Interest row with status=pending, expires_at=+30d
11. Increment DailyInterestUsage
12. Fire notification: `NotificationService::interestReceived($receiver, $interest)` — in-app + email + push

### Response 201
```json
{
  "success": true,
  "data": {
    "interest": { /* same shape as §7.3 item */ },
    "daily_usage": { "limit": 5, "used": 3, "remaining": 2, "resets_at": "2026-04-24T00:00:00Z" }
  }
}
```

---

## 7.6 `POST /api/v1/interests/{interest}/accept`

- Only receiver can accept (403 `UNAUTHORIZED` if sender tries)
- Only `pending` interests → else 400
- Sets `status=accepted`, `accepted_at=now()`
- Fires notification to sender

**Response:** full thread (same shape as §7.4).

---

## 7.7 `POST /api/v1/interests/{interest}/decline`

- Only receiver can decline
- Only `pending` interests
- Sets `status=declined`, `declined_at=now()`
- Fires notification to sender

**Response:** `{"success": true, "data": {"interest_id": 89, "status": "declined"}}`

---

## 7.8 `POST /api/v1/interests/{interest}/cancel`

- Only sender can cancel
- Only `pending` interests
- Only within `cancel_interest_window_hours` (24h) — else 400 `TOO_LATE`
- Soft-deletes the interest row (or sets status=`cancelled` — decide at implementation)
- Does NOT refund daily usage count

**Response:** `{"success": true, "data": {"cancelled": true}}`

---

## 7.9 Replies — `POST /api/v1/interests/{interest}/messages`

### Request
```json
{ "message": "Thanks! Would love to chat more about your interests." }
```

### Rules
- Only participants (sender or receiver)
- Only `accepted` interests
- Sender AND receiver must both be premium (chat is a premium feature) — 403 `PREMIUM_REQUIRED` if either is free
- `message` length 1–500 chars
- Rate limit: 30 replies/hour/user

### Response 201
```json
{
  "success": true,
  "data": {
    "reply": {
      "id": 423,
      "from": "me",
      "text": "Thanks! Would love to chat...",
      "created_at": "2026-04-23T14:50:00Z"
    }
  }
}
```

Side effect: fires notification to the other party.

---

## 7.10 Chat polling — `GET /api/v1/interests/{interest}/messages/since/{messageId?}`

**The polling contract for chat.** Flutter polls every **10 seconds** when the chat screen is open. When screen is backgrounded, polling stops.

### Query params
```
/interests/89/messages/since/422       → returns messages with id > 422
/interests/89/messages/since             → returns all messages (initial load)
```

### Response
```json
{
  "success": true,
  "data": {
    "replies": [
      { "id": 423, "from": "them", "text": "Hi!", "created_at": "2026-04-23T14:51:00Z" },
      { "id": 424, "from": "them", "text": "How are you?", "created_at": "2026-04-23T14:51:10Z" }
    ],
    "latest_message_id": 424,
    "thread_status": "accepted"           // could change to "expired" if TTL hit
  }
}
```

### Response (empty — common case)
```json
{
  "success": true,
  "data": {
    "replies": [],
    "latest_message_id": 422,             // echoes what client sent
    "thread_status": "accepted"
  }
}
```

**Payload size:** 150 bytes empty, ~350 bytes per message. Negligible at 10s polling even for active users.

**Why 10s and not faster:** chat isn't real-time critical in matrimony (unlike messaging apps). Users expect "pretty fast", not "instant". 10s balance: responsive + server-friendly.

### Reverb migration path (Phase 3)
Same endpoint signature, but Flutter connects to a WebSocket instead when `site.config.realtime_chat_enabled = true`. New messages broadcast on channel `private-interest.{id}` → Flutter listens and appends. Polling becomes fallback only.

---

## 7.11 Star / Trash — `POST /api/v1/interests/{interest}/star`, `/trash`

Toggle personal flags. Endpoints take no body.

**Response:**
```json
{ "success": true, "data": { "interest_id": 89, "is_starred": true } }
```

**Business:** `is_starred_by_{sender|receiver}` and `is_trashed_by_{sender|receiver}` — current user's perspective only. The other party doesn't know.

---

## 7.12 Expiration

Scheduled job `interests:expire-stale` runs daily at midnight:
- Marks pending interests older than 30 days as `expired`
- Fires a low-priority notification ("Your interest to X expired unanswered")

No action needed from API — it just Just Works.

---

## 7.13 Templates

Interest templates are pre-canned short messages admin edits. New endpoint:

### `GET /api/v1/interests/templates`

**Response:**
```json
{
  "success": true,
  "data": [
    { "id": 1, "text": "Hi, I'd like to connect." },
    { "id": 2, "text": "Your profile caught my eye." },
    { "id": 3, "text": "Our backgrounds seem aligned — would love to chat." }
  ]
}
```

Free users must use a template. Premium users can send `custom_message` instead.

Templates read from `site_settings.interest_templates` JSON array (new setting; admin edits in Filament panel).

---

## 7.14 Build Checklist

- [ ] `App\Http\Resources\V1\InterestResource` (thread view)
- [ ] `App\Http\Resources\V1\InterestListItemResource` (inbox)
- [ ] `App\Http\Resources\V1\InterestReplyResource`
- [ ] `App\Http\Controllers\Api\V1\InterestController`:
  - [ ] `index()` with tab filter + counts
  - [ ] `show(Interest $interest)` — marks replies seen
  - [ ] `send(string $matriId)` — all 12 checks from §7.5
  - [ ] `accept() / decline() / cancel() / star() / trash()`
  - [ ] `reply()` — premium gate + rate limit
  - [ ] `since(Interest $interest, ?int $messageId)` — polling endpoint
  - [ ] `templates()` — canned messages
- [ ] `App\Services\InterestService` — existing, add methods as needed
- [ ] New `site_settings.interest_templates` JSON array + Filament admin UI
- [ ] New columns `interests.last_seen_by_sender_at`, `last_seen_by_receiver_at` (for unread counts)
- [ ] Scheduled job `interests:expire-stale` daily
- [ ] Rate limits: `throttle:30,60` on reply endpoint
- [ ] Pest tests: all 12 check branches in §7.5, polling contract, premium gates, daily limit reset at midnight

**Acceptance:**
- Free user sends 5 interests → 6th returns 429 with reset time
- Sending custom message as free user → 403 PREMIUM_REQUIRED
- Accept interest from sender → reply endpoint succeeds for both if both premium
- Polling `/since/{lastId}` returns only new messages, not full history
- Expired interest cannot receive replies
