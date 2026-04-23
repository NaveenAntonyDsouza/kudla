<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\WeeklyMatchSuggestionsService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class WeeklyMatchesDashboard extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Weekly Matches';
    protected static \UnitEnum|string|null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 10;
    protected static ?string $title = 'Weekly Match Suggestion Emails';
    protected string $view = 'filament.pages.weekly-matches-dashboard';

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
        $service = app(WeeklyMatchSuggestionsService::class);

        // Dry run to see who WOULD get emails next Sunday
        $dryRun = $service->run(true);

        return [
            'enabled' => $service->isEnabled(),
            'match_count' => $service->getMatchCount(),
            'min_score' => $service->getMinScore(),
            'stats' => [
                'sent_today' => User::whereDate('last_weekly_match_sent_at', today())->count(),
                'sent_this_week' => User::where('last_weekly_match_sent_at', '>=', now()->startOfWeek())->count(),
                'sent_this_month' => User::where('last_weekly_match_sent_at', '>=', now()->startOfMonth())->count(),
                'total_sent_ever' => User::whereNotNull('last_weekly_match_sent_at')->count(),
            ],
            'eligibility' => [
                'members_total' => User::whereNull('staff_role_id')->where('is_active', true)->count(),
                'with_preferences' => User::whereNull('staff_role_id')
                    ->where('is_active', true)
                    ->whereHas('profile.partnerPreference')
                    ->count(),
                'active_last_60' => User::whereNull('staff_role_id')
                    ->where('is_active', true)
                    ->where('last_login_at', '>=', now()->subDays(60))
                    ->count(),
            ],
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
                ->modalHeading('Send weekly match emails now?')
                ->modalDescription('This will immediately send match digest emails to all eligible members. The scheduled Sunday 10 AM run will also happen.')
                ->visible(fn () => \App\Support\Permissions::can('send_broadcast'))
                ->action(function () {
                    $service = app(WeeklyMatchSuggestionsService::class);
                    $result = $service->run(false);

                    Notification::make()
                        ->title("Sent {$result['sent']} weekly match emails")
                        ->body("Skipped: {$result['skipped_no_matches']} with no matches, {$result['skipped_other']} others")
                        ->success()
                        ->send();
                }),

            \Filament\Actions\Action::make('dry_run')
                ->label('Preview (Dry Run)')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->action(function () {
                    $service = app(WeeklyMatchSuggestionsService::class);
                    $result = $service->run(true);

                    Notification::make()
                        ->title("Would send {$result['sent']} emails")
                        ->body("Eligible: {$result['eligible']}, Skipped (no matches): {$result['skipped_no_matches']}, Skipped (other): {$result['skipped_other']}")
                        ->info()
                        ->send();
                }),
        ];
    }
}
