<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\CsvExportService;
use App\Support\Permissions;
use Filament\Pages\Page;

class StaffPerformanceReport extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Staff Performance';
    protected static \UnitEnum|string|null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Staff Performance Report';
    protected string $view = 'filament.pages.staff-performance-report';

    public string $dateRange = 'this_month';
    public array $staffRows = [];
    public array $totals = [];

    public static function shouldRegisterNavigation(): bool
    {
        return Permissions::can('view_engagement_reports');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('view_engagement_reports');
    }

    public function mount(): void
    {
        $this->loadData();
    }

    public function updatedDateRange(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        [$from, $to] = CsvExportService::resolveDateRange($this->dateRange);

        $staff = User::whereNotNull('staff_role_id')
            ->with('staffRole')
            ->orderBy('name')
            ->get();

        $rows = $staff->map(fn (User $u) => CsvExportService::computeStaffMetrics($u, $from, $to))
            ->sortByDesc('leads_converted')
            ->values()
            ->toArray();

        $this->staffRows = $rows;

        // Compute totals across all staff
        $this->totals = [
            'staff_count' => count($rows),
            'leads_assigned' => array_sum(array_column($rows, 'leads_assigned')),
            'leads_converted' => array_sum(array_column($rows, 'leads_converted')),
            'calls_made' => array_sum(array_column($rows, 'calls_made')),
            'calls_connected' => array_sum(array_column($rows, 'calls_connected')),
        ];

        $this->totals['conversion_rate'] = $this->totals['leads_assigned'] > 0
            ? round(($this->totals['leads_converted'] / $this->totals['leads_assigned']) * 100, 1)
            : 0;
    }

    public function exportCsv()
    {
        return CsvExportService::exportStaffPerformance($this->dateRange);
    }

    public function getDateRangeOptions(): array
    {
        return CsvExportService::dateRangeOptions();
    }
}
