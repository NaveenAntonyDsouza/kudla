<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Send an in-app notification to a user.
     */
    public function send(User $user, string $type, string $title, string $message, ?int $fromProfileId = null, array $data = []): void
    {
        Notification::create([
            'user_id' => $user->id,
            'profile_id' => $fromProfileId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
        ]);
    }

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
            'today' => $notifications->filter(fn($n) => $n->created_at >= $today)->values(),
            'yesterday' => $notifications->filter(fn($n) => $n->created_at >= $yesterday && $n->created_at < $today)->values(),
            'previous' => $notifications->filter(fn($n) => $n->created_at < $yesterday)->values(),
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
            'interest_received', 'interest_accepted', 'interest_declined' =>
                isset($data['interest_id']) ? route('interests.show', $data['interest_id']) : route('interests.inbox'),
            'profile_view' =>
                isset($data['viewer_profile_id']) ? route('profile.view', $data['viewer_profile_id']) : route('dashboard'),
            default => route('dashboard'),
        };
    }
}
