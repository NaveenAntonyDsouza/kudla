# 10. Push Notifications (FCM)

End-to-end push notification plan: Firebase project setup, device token registration, backend dispatch integration, trigger catalogue, quiet hours, unsubscribe mapping, iOS later (APNS via FCM).

**Provider:** Firebase Cloud Messaging (FCM), free tier sufficient until ~100K devices.

**SDK:** `firebase_messaging` Flutter plugin (official, maintained by Google).

**Backend:** `kreait/laravel-firebase` Composer package wraps FCM HTTP v1 API with Laravel DI.

---

## 10.1 Firebase Project Setup

### One-time setup

1. Create Firebase project at https://console.firebase.google.com
2. Project name: `matrimony-theme-android` (or reuse existing if any)
3. Add Android app:
   - Package name: `com.books.KudlaMatrimony` (match current webview — preserves install continuity)
   - App nickname: "Kudla Matrimony"
   - SHA-1 certificate: from debug + release keystores
4. Download `google-services.json` → place in `flutter_app/android/app/`
5. Add `google-services` gradle plugin (see §11 Flutter foundations)
6. Enable Cloud Messaging in Firebase console
7. Generate service account key (Project Settings → Service Accounts → Generate new private key)
   - Download JSON
   - Store as `storage/app/firebase-credentials.json` on server (never commit)
   - Add to `.gitignore`
   - `.env`: `FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json`

### iOS (Phase 2b, deferred)

- Same project
- Add iOS app with bundle id
- `GoogleService-Info.plist` in Xcode
- APNS key upload in Firebase Console (needs Apple Developer Program enrollment — $99/year)

---

## 10.2 Backend — Composer Package

```
composer require kreait/laravel-firebase
```

**Config:** publishes `config/firebase.php`. Reads `FIREBASE_CREDENTIALS` env.

**Usage (in `NotificationService`):**
```php
use Kreait\Laravel\Firebase\Facades\Firebase;

$messaging = Firebase::messaging();
$message = CloudMessage::withTarget('token', $deviceToken)
    ->withNotification(Notification::create($title, $body))
    ->withData($data);
$messaging->send($message);
```

---

## 10.3 Device Token Storage

### Migration: `devices` table

```php
Schema::create('devices', function (Blueprint $t) {
    $t->id();
    $t->foreignId('user_id')->constrained()->onDelete('cascade');
    $t->foreignId('personal_access_token_id')->nullable()->constrained('personal_access_tokens')->onDelete('set null');
    $t->string('fcm_token', 255)->unique();
    $t->string('platform', 10);              // android | ios
    $t->string('device_model', 100)->nullable();
    $t->string('app_version', 20)->nullable();
    $t->string('os_version', 20)->nullable();
    $t->string('locale', 10)->default('en');
    $t->timestamp('last_seen_at')->nullable();
    $t->boolean('is_active')->default(true);
    $t->timestamps();

    $t->index('user_id');
    $t->index(['user_id', 'is_active']);
});
```

One user → N devices (phone + tablet + re-installs). Active token can exist on multiple devices simultaneously.

### Token lifecycle

| Event | Action |
|-------|--------|
| Flutter first run after login | `POST /devices` with current fcm_token + metadata |
| Flutter `onTokenRefresh` fires | `POST /devices` with new token (server dedupes on fcm_token unique) |
| User logs out on device | `DELETE /devices/{id}` → sets is_active=false, revokes personal_access_token |
| FCM returns "unregistered" on send | Mark device is_active=false |
| User reinstalls app | New token → new device row. Old token keeps is_active=false after first failed send |

### Endpoints

Already specified in `02-auth-api.md §2.9`. Recap:

- `POST /api/v1/devices` — register/refresh
- `DELETE /api/v1/devices/{device}` — revoke

---

## 10.4 Notification Dispatch Integration

### Existing Notification Service

`App\Services\NotificationService` currently writes to `notifications` table (in-app) + sends email. Add push:

```php
public function dispatch(User $user, string $type, string $title, string $body, array $data = []): void
{
    // 1. Write to notifications table (existing)
    $notification = Notification::create([
        'user_id' => $user->id,
        'type' => $type,
        'title' => $title,
        'body' => $body,
        'data' => $data,
    ]);

    // 2. Send email if user's prefs allow (existing)
    if ($this->shouldEmail($user, $type)) {
        Mail::to($user)->queue(new NotificationMail(...));
    }

    // 3. NEW — send push if user's prefs allow
    if ($this->shouldPush($user, $type)) {
        $this->sendPush($user, $title, $body, array_merge($data, [
            'notification_id' => $notification->id,
            'type' => $type,
        ]));
    }
}

private function sendPush(User $user, string $title, string $body, array $data): void
{
    $devices = $user->devices()->where('is_active', true)->get();
    if ($devices->isEmpty()) return;

    $messaging = Firebase::messaging();

    foreach ($devices as $device) {
        try {
            $message = CloudMessage::withTarget('token', $device->fcm_token)
                ->withNotification(Notification::create($title, $body))
                ->withData(array_map('strval', $data));   // FCM requires string values
            $messaging->send($message);
            $device->update(['last_seen_at' => now()]);
        } catch (NotFound $e) {
            // Token invalid/unregistered
            $device->update(['is_active' => false]);
        } catch (\Throwable $e) {
            report($e);  // non-fatal
        }
    }
}
```

### Quiet hours

User preference: `notification_preferences.quiet_hours_start` (e.g. "22:00") and `quiet_hours_end` ("07:00"), both in user's timezone.

**Enforcement:**
- `shouldPush()` checks quiet hours window. If current user-local time is inside window, skip push (in-app + email still fire normally)
- Exception: **high-priority types** bypass quiet hours (`interest.accepted`, `membership.expiring`). Marketing / engagement types (`admin.broadcast`, `match.new`) respect quiet hours

**Types:**
| Type | Priority | Bypasses quiet hours? |
|------|----------|----------------------|
| `interest.received` | normal | no |
| `interest.accepted` | high | yes |
| `interest.declined` | normal | no |
| `interest.reply` | normal | no |
| `photo_request.*` | normal | no |
| `profile.viewed` | low | no |
| `match.new` | low | no |
| `membership.expiring` | high | yes |
| `membership.expired` | high | yes |
| `admin.broadcast` | normal | no |

---

## 10.5 Push Payload Format

**Notification block** (shown in system tray when app is backgrounded):
```json
{
  "notification": {
    "title": "Priya sent you an interest",
    "body": "Tap to view their profile"
  }
}
```

**Data block** (delivered to `FirebaseMessaging.onMessage` / `onBackgroundMessage` in Flutter):
```json
{
  "data": {
    "type": "interest.received",
    "notification_id": "5421",
    "interest_id": "89",
    "sender_matri_id": "AM100087",
    "deep_link": "/interests/89"
  }
}
```

**Android channel** (for Android 8+ notification channels, set in Flutter):
- `interests` — interest events (high importance, sound)
- `photo_requests` — photo request events (default importance)
- `profile_activity` — views, shortlists (low importance, silent)
- `membership` — membership alerts (high importance)
- `broadcasts` — admin announcements (default importance)
- `matches` — daily/weekly matches (low importance)

---

## 10.6 Handling in Flutter

### App states

| App state | Notification behavior |
|-----------|----------------------|
| Foreground | `FirebaseMessaging.onMessage` stream fires. Show in-app toast/banner, update badge counts. System tray *not* populated unless we explicitly call `flutter_local_notifications` |
| Background (app running) | System tray shows notification. On tap, `onMessageOpenedApp` fires → navigate to `data.deep_link` |
| Terminated | System tray shows notification. On tap, `getInitialMessage()` returns the message at app start → navigate after Riverpod boot completes |

### Permissions

Android 13+ requires `POST_NOTIFICATIONS` runtime permission. Flutter plugin handles the prompt on first FCM subscribe.

### Badge count

FCM on Android doesn't deliver native app icon badges reliably. Use `badge_count` field in data and update via `flutter_app_badger` when unread count changes. Alternatively: rely on in-app badge in bottom nav (what we do in v1).

---

## 10.7 Unsubscribe Mapping

Web has per-type email unsubscribe via signed URLs (`/unsubscribe/{user}/{preference}`). Mobile needs equivalent:

### From notification itself
- User taps notification → deep-links to settings screen with that type's push toggle highlighted

### From settings screen
- `PUT /api/v1/settings/alerts` with the `push_*` keys (see `09-engagement-api.md §9.8`)

### Server maps notification types to preference keys

| Type | Preference key |
|------|---------------|
| `interest.received` | `push_interest` |
| `interest.accepted` | `push_interest` (same flag — we don't let users opt out of "good" interest news separately) |
| `interest.declined` | `push_declined` |
| `interest.reply` | `push_interest` |
| `photo_request.*` | `push_photo_requests` |
| `profile.viewed` | `push_views` |
| `profile.shortlisted` | `push_views` (bundled) |
| `match.new` | `push_matches` |
| `membership.*` | always on (can't opt out of account alerts) |
| `admin.broadcast` | `push_promotions` |
| `saved_search.new_matches` | `push_matches` |

Preference defaults on registration:
```
push_interest: true
push_declined: false           (most users don't want this)
push_photo_requests: true
push_views: false              (can be spammy)
push_matches: true
push_promotions: false         (opt-in for marketing)
```

---

## 10.8 Broadcast / Campaign Push

**Filament admin has "Broadcast Notifications" page** (from admin panel phase). Currently sends in-app + email broadcast. Extend to push:

### Admin UI additions
- Checkbox: "Also send as push notification"
- Filter: target audience (all, premium only, free only, by plan, by branch, by gender, by age range, etc.)
- Preview: "Will send to ~1,234 users"
- Schedule: "Send now" or "Send at {datetime}"

### Backend
- Queue a job `DispatchBroadcastPush($broadcastId)` that iterates target users in chunks of 500
- Per user: look up active devices, send multicast FCM message (up to 500 tokens per HTTP call)
- Record per-user dispatch status in `broadcast_delivery_log` table

---

## 10.9 Scheduled Push — Re-engagement

Existing engagement emails (7/14/30 day re-engagement, weekly matches, daily profile nudges) also get push variants. Add push check in each:

```php
// In ReengagementService (existing)
foreach ($inactiveUsers as $user) {
    Mail::to($user)->queue(...);        // existing
    if ($user->hasAnyDevice()) {
        NotificationService::dispatch(... push included ...);
    }
}
```

---

## 10.10 Observability

### Metrics to track
- `push_send_attempted_total{type, platform}`
- `push_send_succeeded_total{type, platform}`
- `push_send_failed_total{type, platform, reason}`
- `push_token_invalidated_total`
- `push_delivery_latency_seconds` (dispatched → acked)

Store aggregates in `notification_metrics` table, surface in Filament admin dashboard.

### Alerting
- Scheduled job `notifications:health-check` hourly
- If failure rate > 20% in last hour → Slack alert to ops
- If token invalidation rate spikes (>50 in 10 min) → investigate Firebase project config

---

## 10.11 Cost Projection

FCM is free. Cost = server infra + dev time.

At 10,000 DAU × 3 push/day avg = 30K pushes/day = 900K/month → well under any conceivable quota.

Bandwidth negligible (each push ~500 bytes).

---

## 10.12 Build Checklist

### Backend
- [ ] Firebase project + service account JSON
- [ ] `composer require kreait/laravel-firebase`
- [ ] `php artisan vendor:publish --tag=firebase-config`
- [ ] `.env`: `FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json`
- [ ] Migration: `devices` table (see §10.3)
- [ ] Migration: `broadcast_delivery_log` table
- [ ] `User::devices()` hasMany relation
- [ ] `App\Http\Controllers\Api\V1\DeviceController` (register, revoke)
- [ ] Extend `App\Services\NotificationService` with `sendPush()` (see §10.4)
- [ ] Add quiet-hours logic in `shouldPush()`
- [ ] Extend Broadcast admin page with push toggle + audience filter
- [ ] Queue job `DispatchBroadcastPush`
- [ ] Metrics collection in `NotificationService` (counters) + Filament widget
- [ ] Scheduled job `notifications:health-check` hourly

### Flutter (covered in §11-15)
- [ ] `google-services.json` in android/app
- [ ] `firebase_core`, `firebase_messaging` deps
- [ ] `FirebaseMessaging.instance.requestPermission()` on login
- [ ] Register token via `POST /devices` on login and on `onTokenRefresh`
- [ ] Handlers for `onMessage` (in-app banner), `onMessageOpenedApp` (deep link), `getInitialMessage` (cold-start deep link)
- [ ] Notification channels created on first run
- [ ] `flutter_app_badger` for badge count

**Acceptance:**
- Fresh Flutter install → login → push arrives on test notification send from admin panel
- Logging out revokes device → push stops arriving
- Quiet hours respected for non-critical types
- Notification tap routes to correct screen in all 3 app states (foreground, background, terminated)
