<?php

namespace App\Providers;

use App\Models\Notification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewComposerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('components.layouts.app', function ($view) {
            $unreadNotificationCount = 0;
            $recentNotifications = ['today' => collect(), 'yesterday' => collect(), 'previous' => collect()];

            if ($user = auth()->user()) {
                // Only count — lightweight query
                $unreadNotificationCount = Notification::where('user_id', $user->id)
                    ->where('is_read', false)
                    ->count();

                // Load recent notifications (no eager loading of photos for speed)
                $notifications = Notification::where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();

                $today = now()->startOfDay();
                $yesterday = now()->subDay()->startOfDay();

                $recentNotifications = [
                    'today' => $notifications->filter(fn($n) => $n->created_at >= $today)->values(),
                    'yesterday' => $notifications->filter(fn($n) => $n->created_at >= $yesterday && $n->created_at < $today)->values(),
                    'previous' => $notifications->filter(fn($n) => $n->created_at < $yesterday)->values(),
                ];
            }

            $view->with('unreadNotificationCount', $unreadNotificationCount);
            $view->with('recentNotifications', $recentNotifications);
        });
    }

    public function register(): void {}
}
