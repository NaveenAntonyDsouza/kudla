# Step 7 — Extend `NotificationService` with Push Dispatch

## Goal
Make `NotificationService::dispatch()` send push notifications alongside in-app + email. Add quiet hours support.

## Procedure

### 1. Extend `NotificationService`

Open `app/Services/NotificationService.php` and add:

```php
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;

private const HIGH_PRIORITY_TYPES = [
    'interest.accepted',
    'membership.expiring',
    'membership.expired',
    'profile.approved',
    'id_proof.verified',
];

public function dispatch(User $user, string $type, string $title, string $body, array $data = []): void
{
    $notification = Notification::create([
        'user_id' => $user->id,
        'type' => $type,
        'title' => $title,
        'body' => $body,
        'data' => $data,
    ]);

    if ($this->shouldEmail($user, $type)) {
        $this->sendEmail($user, $type, $title, $body, $data);
    }

    if ($this->shouldPush($user, $type)) {
        $this->sendPush($user, $title, $body, array_merge($data, [
            'notification_id' => (string) $notification->id,
            'type' => $type,
        ]));
    }
}

private function shouldPush(User $user, string $type): bool
{
    $prefs = $user->notification_preferences ?? [];
    $prefKey = $this->pushPrefKey($type);

    // High-priority types bypass preferences
    if (in_array($type, self::HIGH_PRIORITY_TYPES, true)) {
        return true;
    }

    if (! ($prefs[$prefKey] ?? true)) return false;

    // Quiet hours check (in user's timezone)
    $quietStart = $prefs['quiet_hours_start'] ?? null;
    $quietEnd = $prefs['quiet_hours_end'] ?? null;
    if ($quietStart && $quietEnd) {
        $now = now()->format('H:i');
        if ($this->inQuietHours($now, $quietStart, $quietEnd)) {
            return false;
        }
    }

    return true;
}

private function pushPrefKey(string $type): string
{
    return match (true) {
        str_starts_with($type, 'interest.') => 'push_interest',
        str_starts_with($type, 'photo_request.') => 'push_photo_requests',
        str_starts_with($type, 'profile.viewed'), str_starts_with($type, 'profile.shortlisted') => 'push_views',
        str_starts_with($type, 'match.') => 'push_matches',
        str_starts_with($type, 'admin.broadcast') => 'push_promotions',
        default => 'push_interest',
    };
}

private function inQuietHours(string $now, string $start, string $end): bool
{
    if ($start < $end) {
        return $now >= $start && $now < $end;
    }
    // Overnight window (e.g., 22:00 → 07:00)
    return $now >= $start || $now < $end;
}

private function sendPush(User $user, string $title, string $body, array $data): void
{
    $devices = $user->devices()->where('is_active', true)->get();
    if ($devices->isEmpty()) return;

    $messaging = Firebase::messaging();

    foreach ($devices as $device) {
        try {
            $message = CloudMessage::withTarget('token', $device->fcm_token)
                ->withNotification(FcmNotification::create($title, $body))
                ->withData(array_map('strval', $data));
            $messaging->send($message);
            $device->update(['last_seen_at' => now()]);
        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            $device->update(['is_active' => false]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
```

### 2. Test send

```bash
php artisan tinker
>>> $user = \App\Models\User::find(1);
>>> app(\App\Services\NotificationService::class)->dispatch($user, 'admin.broadcast', 'Test push', 'Hello from server');
# Check FCM delivery on device
```

## Verification

- [ ] `dispatch()` writes to `notifications` table
- [ ] Push arrives on registered device
- [ ] Quiet hours skipped for non-critical types
- [ ] High-priority types (interest.accepted, membership.expiring) bypass quiet hours
- [ ] Invalid FCM token marks device `is_active=false`

## Commit

```bash
git commit -am "phase-2a wk-04: step-07 NotificationService with push dispatch + quiet hours"
```

## Next step
→ [step-08-notification-endpoints.md](step-08-notification-endpoints.md)
