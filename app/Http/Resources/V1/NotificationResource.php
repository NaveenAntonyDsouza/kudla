<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Notification row → API shape.
 *
 * Carries enough for Flutter to render a list item AND deep-link on
 * tap: id + type + title + message + the per-type `data` blob (e.g.
 * data.interest_id) + an icon hint Flutter maps to its icon set + a
 * `target` telling it which screen to open.
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
 *   - `target` is {screen, id, section} or null. The server owns the
 *     type → screen mapping so new notification types don't need an app
 *     release to open the right place. Screen keys are stable:
 *       interest (id = interest id) · interests · profile (id = profile
 *       id) · photo_requests · my_photos · my_profile (section = which
 *       part to complete) · my_documents · membership
 */
class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        $type = (string) $this->type;
        $data = $this->data ?? [];
        $fromProfileId = $this->profile_id ? (int) $this->profile_id : null;

        return [
            'id' => (int) $this->id,
            'type' => $type,
            'title' => (string) $this->title,
            'message' => (string) $this->message,
            'data' => $data,
            'is_read' => (bool) $this->is_read,
            'created_at' => $this->created_at?->toUtcIso(),
            'icon_type' => self::iconType($type),
            'from_profile_id' => $fromProfileId,
            'target' => self::target($type, is_array($data) ? $data : [], $fromProfileId),
        ];
    }

    /**
     * Map a notification type to a stable Flutter icon-set key.
     * (notifications.type is varchar(50) since May 2026 — any type string
     * may appear; unknown ones fall back to 'bell'.)
     */
    public static function iconType(string $type): string
    {
        return match ($type) {
            'interest_received', 'interest_accepted', 'interest_declined' => 'interest',
            'profile_view', 'profile_changes_requested' => 'profile',
            'photo_request', 'photo_request_approved', 'photo_added',
            'photo_approved', 'photo_rejected' => 'photo',
            'id_proof_approved', 'id_proof_rejected',
            'document_approved', 'document_rejected' => 'verified',
            'membership_expiring', 'membership_expired', 'plan_changed' => 'membership',
            default => 'bell', // system (incl. profile nudges), admin_broadcast
        };
    }

    /**
     * Where tapping the notification should take the member, or null when
     * there's nowhere specific (broadcasts, plain system messages).
     *
     * @param  array<string,mixed>  $data
     * @return array{screen: string, id: int|null, section: string|null}|null
     */
    public static function target(string $type, array $data, ?int $fromProfileId): ?array
    {
        $to = fn (string $screen, ?int $id = null, ?string $section = null) => [
            'screen' => $screen,
            'id' => $id,
            'section' => $section,
        ];
        $int = fn ($v) => is_numeric($v) ? (int) $v : null;

        return match ($type) {
            'interest_received', 'interest_accepted', 'interest_declined' => isset($data['interest_id'])
                ? $to('interest', $int($data['interest_id']))
                : $to('interests'),
            'profile_view' => ($id = $int($data['viewer_profile_id'] ?? $fromProfileId)) ? $to('profile', $id) : null,
            'photo_request' => $to('photo_requests'),
            // The member who approved / added a photo — open their profile to see it.
            'photo_request_approved' => $fromProfileId ? $to('profile', $fromProfileId) : $to('photo_requests'),
            'photo_added' => ($id = $int($data['owner_profile_id'] ?? $fromProfileId)) ? $to('profile', $id) : $to('photo_requests'),
            'photo_approved', 'photo_rejected' => $to('my_photos'),
            'profile_changes_requested' => $to('my_profile'),
            'id_proof_approved', 'id_proof_rejected',
            'document_approved', 'document_rejected' => $to('my_documents'),
            'membership_expiring', 'membership_expired', 'plan_changed' => $to('membership'),
            // Profile-completion nudges are 'system' rows carrying a nudge_type.
            'system' => isset($data['nudge_type']) ? $to('my_profile', null, (string) $data['nudge_type']) : null,
            default => null,
        };
    }
}
