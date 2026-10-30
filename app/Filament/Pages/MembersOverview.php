<?php

namespace App\Filament\Pages;

use App\Services\CsvExportService;
use App\Support\Permissions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Insights → Members Overview (Oct 2026): who the members are — men and
 * women, age groups, where they live, religion and community, sign-ups over
 * time — and the members worth a call. Replaces Site Overview and User
 * Reports, which showed the same charts twice and counted "active" as
 * merely switched on.
 */
class MembersOverview extends BaseDashboard
{
    protected static string $routePath = 'members-overview';
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static \UnitEnum|string|null $navigationGroup = 'Insights';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Members Overview';
    protected static ?string $title = 'Members Overview';

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return null;
    }

    public static function canAccess(): bool
    {
        return Permissions::can('view_user_reports');
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\MembersStats::class,
            \App\Filament\Widgets\GenderBalanceChart::class,
            \App\Filament\Widgets\AgeGroupsChart::class,
            \App\Filament\Widgets\WhereMembersLiveChart::class,
            \App\Filament\Widgets\ReligionDistribution::class,
            \App\Filament\Widgets\RegistrationChart::class,
            \App\Filament\Widgets\CasteDenominationChart::class,
            \App\Filament\Widgets\InactiveMembersTable::class,
            \App\Filament\Widgets\IncompleteProfilesTable::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 2];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export members (CSV)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                // every member's email and phone: its own permission, not just this page's
                ->visible(fn () => Permissions::can('export_member_data'))
                ->action(fn () => Permissions::can('export_member_data') ? CsvExportService::exportUsers() : abort(403)),
        ];
    }
}
