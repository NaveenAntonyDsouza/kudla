<?php

namespace App\Providers;

use App\Services\NotificationService;
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
                $service = app(NotificationService::class);
                $unreadNotificationCount = $service->getUnreadCount($user);
                $recentNotifications = $service->getRecent($user, 15);
            }

            $view->with('unreadNotificationCount', $unreadNotificationCount);
            $view->with('recentNotifications', $recentNotifications);
        });
    }

    public function register(): void {}
}
