<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;

/**
 * Sends in-app notifications and (since step-07) FCM push to a user's
 * registered devices. Gracefully degrades: if FCM credentials aren't
 * configured (CodeCanyon buyer hasn't uploaded the service-account
 * JSON yet), the in-app row still writes and the push leg is silently
 * skipped — the rest of the app keeps working.
 *
 * Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-07-notification-push-dispatch.md
 */
class NotificationService
{
    /**
     * Notification types that ALWAYS push, bypassing user preferences
     * and quiet hours. Reserved for events the user explicitly opted
     * into (someone accepted their interest) — never broadcast spam.
     *
     * Currently only the types in the notifications.type ENUM are
     * eligible; expand the ENUM (separate migration) before adding more.
     */
    private const HIGH_PRIORITY_TYPES = ['interest_accepted'];

    /**
     * Send an in-app notification to a user, then fire a push to their
     * active devices when preferences and quiet hours allow.
     *
     * Method name kept as `send` for backward compatibility with the
     * eight existing call sites (InterestService, PhotoRequestController,
     * SendMembershipExpiryReminders). The step-07 design doc calls the
     * method `dispatch`; behaviour is identical — only the name differs.
     */
    public function send(User $user, string $type, string $title, string $message, ?int $fromProfileId = null, array $data = []): void
    {
        $notification = Notification::create([
            'user_id' => $user->id,
            'profile_id' => $fromProfileId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
        ]);

        if ($this->shouldPush($user, $type)) {
            // Carry the notification id + type into the FCM data so the
            // Flutter handler can deep-link straight to the matching row.
            $this->sendPush(
                $user,
                $title,
                $message,
                array_merge($data, [
                    'notification_id' => (string) $notification->id,
                    'type' => $type,
                ]),
            );
        }
    }

    /* ==================================================================
     |  Existing in-app reads — unchanged from pre step-07
     | ================================================================== */

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get recent notifications grouped by date (Today, Yesterday, Previous).
     */
    public function getRecent(User $user, int $limit = 20): array
    {
        $notifications = Notification::where('user_id', $user->id)
            ->with('profile.primaryPhoto')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        return [
            'today' => $notifications->filter(fn ($n) => $n->created_at >= $today)->values(),
            'yesterday' => $notifications->filter(fn ($n) => $n->created_at >= $yesterday && $n->created_at < $today)->values(),
            'previous' => $notifications->filter(fn ($n) => $n->created_at < $yesterday)->values(),
        ];
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->update(['is_read' => true]);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(User $user): void
    {
        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Get the URL to navigate to when a notification is clicked.
     */
    public function getNotificationUrl(Notification $notification): string
    {
        $data = $notification->data ?? [];

        return match ($notification->type) {
            'interest_received', 'interest_accepted', 'interest_declined' => isset($data['interest_id']) ? route('interests.show', $data['interest_id']) : route('interests.inbox'),
            'profile_view' => isset($data['viewer_profile_id']) ? route('profile.view', $data['viewer_profile_id']) : route('dashboard'),
            default => route('dashboard'),
        };
    }

    /* ==================================================================
     |  Push dispatch (step-07)
     | ================================================================== */

    /**
     * Decide whether to push for a (user, type) pair.
     *
     * High-priority types ALWAYS push (preferences + quiet hours
     * ignored). Everything else respects:
     *   1. The matching `push_*` notification preference (default true).
     *   2. The user's quiet-hours window (default off).
     */
    private function shouldPush(User $user, string $type): bool
    {
        if (in_array($type, self::HIGH_PRIORITY_TYPES, true)) {
            return true;
        }

        $prefs = $user->notification_preferences ?? [];
        $prefKey = $this->pushPrefKey($type);

        // Explicit opt-out. Default true (the user has not set it yet).
        if (! ($prefs[$prefKey] ?? true)) {
            return false;
        }

        $quietStart = (string) ($prefs['quiet_hours_start'] ?? '');
        $quietEnd = (string) ($prefs['quiet_hours_end'] ?? '');
        if ($quietStart !== '' && $quietEnd !== '') {
            $now = now()->format('H:i');
            if ($this->inQuietHours($now, $quietStart, $quietEnd)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Map a notification type onto the matching push_* preference key.
     * Future types (membership.*, profile.approved, etc.) need an entry
     * here when added to the notifications.type ENUM.
     */
    private function pushPrefKey(string $type): string
    {
        return match ($type) {
            'interest_received', 'interest_accepted', 'interest_declined' => 'push_interest',
            'profile_view' => 'push_views',
            'system' => 'push_promotions',
            default => 'push_interest',
        };
    }

    /**
     * Check whether a HH:MM "now" falls inside a [start, end) window.
     * Handles the overnight case where end < start (e.g. 22:00 → 07:00
     * means "between 22:00 and midnight, OR between midnight and 07:00").
     */
    private function inQuietHours(string $now, string $start, string $end): bool
    {
        if ($start < $end) {
            return $now >= $start && $now < $end;
        }

        // Overnight window — true if now is past start OR before end.
        return $now >= $start || $now < $end;
    }

    /**
     * Fire FCM push to every active device the user owns.
     *
     * Failure modes (all swallowed — push is best-effort, never breaks
     * the in-app notification flow):
     *   - Firebase not configured: graceful no-op (kreait throws on
     *     messaging() when credentials are missing). This is how the
     *     CodeCanyon buyer's first install works — push silently
     *     disabled until they upload a service-account JSON.
     *   - NotFound on a specific device: token is stale (uninstalled
     *     app, FCM rotation never received). Mark the device inactive
     *     so subsequent pushes skip it.
     *   - Other per-device errors: report() and continue to the next
     *     device.
     *
     * Successful sends bump last_seen_at — useful for admin support
     * (which devices are reachable) and per-user device cleanup.
     */
    private function sendPush(User $user, string $title, string $message, array $data): void
    {
        $devices = $user->devices()->where('is_active', true)->get();
        if ($devices->isEmpty()) {
            return;
        }

        try {
            $messaging = Firebase::messaging();
        } catch (\Throwable $e) {
            // Firebase not configured (or kreait failed to bootstrap) —
            // silent no-op so the in-app notification path keeps working.
            // report() surfaces it to the dev log without user impact.
            report($e);

            return;
        }

        // FCM data fields must be strings — coerce booleans / ints.
        $stringData = array_map(static fn ($v) => is_scalar($v) ? (string) $v : (string) json_encode($v), $data);

        foreach ($devices as $device) {
            try {
                $cloudMessage = CloudMessage::new()
                    ->withToken($device->fcm_token)
                    ->withNotification(FcmNotification::create($title, $message))
                    ->withData($stringData);

                $messaging->send($cloudMessage);

                $device->update(['last_seen_at' => now()]);
            } catch (NotFound $e) {
                // Token is no longer valid (app uninstalled, FCM token
                // expired, etc.). Stop targeting it; admin or future
                // re-install will create a new device row.
                $device->update(['is_active' => false]);
            } catch (\Throwable $e) {
                // Unknown per-device failure — log + carry on.
                report($e);
            }
        }
    }
}
