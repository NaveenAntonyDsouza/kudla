<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Notification row → API shape.
 *
 * Carries enough for Flutter to render a list item AND deep-link on
 * tap: id + type + title + message + the per-type `data` blob (e.g.
 * data.interest_id) + an icon hint Flutter maps to its icon set.
 *
 * The `data` blob shape is type-specific and stable — see the docs at
 *   docs/mobile-app/design/10-push-notifications.md
 *
 * Field naming notes:
 *   - We expose `message` (matches DB schema), NOT `body` as the
 *     step-08 design doc suggests; the step-07 push code uses the
 *     same name in FCM payloads, so Flutter sees `message` from both
 *     surfaces.
 *   - `from_profile_id` is included when the notification carries a
 *     reference to another profile (interest sender, viewer, etc.)
 *     so Flutter can navigate without an extra round trip.
 */
class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'type' => (string) $this->type,
            'title' => (string) $this->title,
            'message' => (string) $this->message,
            'data' => $this->data ?? [],
            'is_read' => (bool) $this->is_read,
            'created_at' => $this->created_at?->toIso8601String(),
            'icon_type' => self::iconType((string) $this->type),
            'from_profile_id' => $this->profile_id ? (int) $this->profile_id : null,
        ];
    }

    /**
     * Map a notification type to a stable Flutter icon-set key.
     *
     * Driven by the underscore-cased ENUM in the notifications table
     * — the step-08 doc's dotted variants (interest.accepted etc.) are
     * NOT in the schema yet; expand the ENUM (separate migration) and
     * this map together when adding more types.
     */
    public static function iconType(string $type): string
    {
        return match ($type) {
            'interest_received', 'interest_accepted', 'interest_declined' => 'interest',
            'profile_view' => 'profile',
            'system' => 'bell',
            default => 'bell',
        };
    }
}
