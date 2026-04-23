<?php

namespace App\Filament\Pages;

use App\Models\Notification;
use App\Models\User;
use App\Services\ProfileNudgeService;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;

class ProfileNudgeDashboard extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Profile Nudges';
    protected static \UnitEnum|string|null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 11;
    protected static ?string $title = 'Profile Completion Nudges';
    protected string $view = 'filament.pages.profile-nudge-dashboard';

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('view_engagement_reports');
    }

    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('view_engagement_reports');
    }

    public function getViewData(): array
    {
        $service = app(ProfileNudgeService::class);

        // Forecast what next run would do
        $dryRun = $service->run(true);

        // Breakdown of sent nudges by type (from Notification.data)
        $byType = [];
        $recent = Notification::where('type', 'system')
            ->where('created_at', '>=', now()->subDays(30))
            ->get();
        foreach ($recent as $notif) {
            $type = $notif->data['nudge_type'] ?? 'unknown';
            $byType[$type] = ($byType[$type] ?? 0) + 1;
        }
        arsort($byType);

        return [
            'enabled' => $service->isEnabled(),
            'threshold' => $service->getThreshold(),
            'stats' => [
                'sent_today' => User::whereDate('last_nudge_sent_at', today())->count(),
                'sent_this_week' => User::where('last_nudge_sent_at', '>=', now()->startOfWeek())->count(),
                'sent_this_month' => User::where('last_nudge_sent_at', '>=', now()->startOfMonth())->count(),
                'total_sent_ever' => Notification::where('type', 'system')
                    ->whereJsonLength('data->nudge_type', '>', 0)
                    ->count(),
            ],
            'by_lifetime_count' => [
                '0' => User::whereNull('staff_role_id')->where('is_active', true)->where('nudges_sent_count', 0)->count(),
                '1' => User::whereNull('staff_role_id')->where('nudges_sent_count', 1)->count(),
                '2' => User::whereNull('staff_role_id')->where('nudges_sent_count', 2)->count(),
                '3' => User::whereNull('staff_role_id')->where('nudges_sent_count', 3)->count(),
                '4' => User::whereNull('staff_role_id')->where('nudges_sent_count', 4)->count(),
            ],
            'nudges_by_type' => $byType,
            'next_run' => $dryRun,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('run_now')
                ->label('Run Now')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Send profile nudges now?')
                ->modalDescription('This will create in-app notifications for all eligible members with incomplete profiles. The scheduled 19:00 run will also happen.')
                ->visible(fn () => \App\Support\Permissions::can('send_broadcast'))
                ->action(function () {
                    $service = app(ProfileNudgeService::class);
                    $result = $service->run(false);

                    FilamentNotification::make()
                        ->title("Sent {$result['sent']} profile nudges")
                        ->body("Eligible: {$result['eligible']}, Skipped (complete): {$result['skipped_no_missing']}, Skipped (other): {$result['skipped_other']}")
                        ->success()
                        ->send();
                }),

            \Filament\Actions\Action::make('dry_run')
                ->label('Preview (Dry Run)')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->action(function () {
                    $service = app(ProfileNudgeService::class);
                    $result = $service->run(true);

                    FilamentNotification::make()
                        ->title("Would send {$result['sent']} nudges")
                        ->body("Eligible: {$result['eligible']}, Complete: {$result['skipped_no_missing']}, Rate-limited/Capped: {$result['skipped_other']}")
                        ->info()
                        ->send();
                }),
        ];
    }
}
