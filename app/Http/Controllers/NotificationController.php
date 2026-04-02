<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Notifications full page view.
     */
    public function index()
    {
        $user = auth()->user();
        $grouped = $this->notificationService->getRecent($user, 50);

        return view('notifications.index', compact('grouped'));
    }

    /**
     * Mark a single notification as read and redirect to target.
     */
    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $this->notificationService->markAsRead($notification);

        $url = $this->notificationService->getNotificationUrl($notification);

        return redirect($url);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $this->notificationService->markAllAsRead(auth()->user());

        return back()->with('success', 'All notifications marked as read.');
    }
}
