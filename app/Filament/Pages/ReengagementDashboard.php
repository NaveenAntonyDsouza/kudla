<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\ReengagementService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ReengagementDashboard extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Re-engagement';
    protected static \UnitEnum|string|null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 9;
    protected static ?string $title = 'Re-engagement Emails';
    protected string $view = 'filament.pages.reengagement-dashboard';

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
        $service = app(ReengagementService::class);
        $thresholds = $service->getThresholds();

        // Who would the dry run target?
        $dryRun = $service->run(true);

        return [
            'enabled' => $service->isEnabled(),
            'thresholds' => $thresholds,
            'stats' => [
                'sent_today' => User::whereDate('last_reengagement_sent_at', today())->count(),
                'sent_this_week' => User::where('last_reengagement_sent_at', '>=', now()->startOfWeek())->count(),
                'sent_this_month' => User::where('last_reengagement_sent_at', '>=', now()->startOfMonth())->count(),
                'total_sent_ever' => User::whereNotNull('last_reengagement_sent_at')->count(),
            ],
            'levels' => [
                1 => User::where('reengagement_level', 1)->count(),
                2 => User::where('reengagement_level', 2)->count(),
                3 => User::where('reengagement_level', 3)->count(),
            ],
            'next_run' => $dryRun,
            'inactive_breakdown' => [
                '7+' => User::whereNull('staff_role_id')
                    ->where('is_active', true)
                    ->where('last_login_at', '<=', now()->subDays($thresholds[1]))
                    ->count(),
                '14+' => User::whereNull('staff_role_id')
                    ->where('is_active', true)
                    ->where('last_login_at', '<=', now()->subDays($thresholds[2]))
                    ->count(),
                '30+' => User::whereNull('staff_role_id')
                    ->where('is_active', true)
                    ->where('last_login_at', '<=', now()->subDays($thresholds[3]))
                    ->count(),
            ],
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
                ->modalHeading('Send re-engagement emails now?')
                ->modalDescription('This will send emails to all eligible inactive users immediately. The scheduled 9 AM run will also happen.')
                ->visible(fn () => \App\Support\Permissions::can('send_broadcast'))
                ->action(function () {
                    $service = app(ReengagementService::class);
                    $result = $service->run(false);
                    $total = array_sum($result['sent_by_level']);

                    Notification::make()
                        ->title("Sent $total re-engagement emails")
                        ->body("L1: {$result['sent_by_level'][1]}, L2: {$result['sent_by_level'][2]}, L3: {$result['sent_by_level'][3]}")
                        ->success()
                        ->send();
                }),

            \Filament\Actions\Action::make('dry_run')
                ->label('Preview (Dry Run)')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->action(function () {
                    $service = app(ReengagementService::class);
                    $result = $service->run(true);
                    $total = array_sum($result['sent_by_level']);

                    Notification::make()
                        ->title("Preview: would send $total emails")
                        ->body("L1: {$result['sent_by_level'][1]}, L2: {$result['sent_by_level'][2]}, L3: {$result['sent_by_level'][3]}. No emails were actually sent.")
                        ->info()
                        ->send();
                }),
        ];
    }
}
