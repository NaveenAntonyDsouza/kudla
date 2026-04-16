<?php

namespace App\Filament\Pages;

use App\Models\Profile;
use App\Models\ReligiousInfo;
use App\Services\CsvExportService;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserReports extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'User Reports';
    protected static \UnitEnum|string|null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'User Reports';
    protected string $view = 'filament.pages.user-reports';

    public array $stats = [];
    public array $registrationChart = [];
    public array $genderData = [];
    public array $religionData = [];
    public array $stateData = [];
    public array $inactiveUsers = [];
    public array $incompleteProfiles = [];

    public function mount(): void
    {
        $this->loadStats();
        $this->loadRegistrationChart();
        $this->loadGenderData();
        $this->loadReligionData();
        $this->loadStateData();
        $this->loadInactiveUsers();
        $this->loadIncompleteProfiles();
    }

    private function loadStats(): void
    {
        $inactive30d = 0;
        try {
            $inactive30d = Profile::whereNotNull('full_name')
                ->whereHas('user', fn ($q) => $q->where('last_login_at', '<', now()->subDays(30))->orWhereNull('last_login_at'))
                ->count();
        } catch (\Throwable $e) {
            // last_login_at may not exist in fresh installs
        }

        $this->stats = [
            'total' => Profile::whereNotNull('full_name')->count(),
            'active' => Profile::whereNotNull('full_name')->where('is_active', true)->count(),
            'inactive_30d' => $inactive30d,
            'new_this_month' => Profile::whereNotNull('full_name')
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'avg_completion' => (int) Profile::whereNotNull('full_name')->avg('profile_completion_pct'),
        ];
    }

    private function loadRegistrationChart(): void
    {
        $data = Profile::select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as period"), DB::raw('COUNT(*) as total'))
            ->whereNotNull('full_name')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total', 'period')
            ->toArray();

        $this->registrationChart = [
            'labels' => array_map(fn ($k) => Carbon::createFromFormat('Y-m', $k)->format('M Y'), array_keys($data)),
            'values' => array_values($data),
        ];
    }

    private function loadGenderData(): void
    {
        $this->genderData = Profile::whereNotNull('full_name')
            ->where('is_active', true)
            ->select('gender', DB::raw('COUNT(*) as total'))
            ->groupBy('gender')
            ->pluck('total', 'gender')
            ->toArray();
    }

    private function loadReligionData(): void
    {
        $this->religionData = ReligiousInfo::select('religion', DB::raw('COUNT(*) as total'))
            ->whereNotNull('religion')
            ->groupBy('religion')
            ->orderByDesc('total')
            ->pluck('total', 'religion')
            ->toArray();
    }

    private function loadStateData(): void
    {
        $this->stateData = DB::table('location_info')
            ->select('native_state as state', DB::raw('COUNT(*) as total'))
            ->whereNotNull('native_state')
            ->where('native_state', '!=', '')
            ->groupBy('native_state')
            ->orderByDesc('total')
            ->limit(20)
            ->get()
            ->toArray();
    }

    private function loadInactiveUsers(): void
    {
        try {
            $this->inactiveUsers = Profile::whereNotNull('full_name')
                ->where('is_active', true)
                ->whereHas('user', fn ($q) => $q->where('last_login_at', '<', now()->subDays(30))->orWhereNull('last_login_at'))
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(fn ($p) => [
                    'matri_id' => $p->matri_id,
                    'name' => $p->full_name,
                    'last_login' => $p->user?->last_login_at?->format('d M Y') ?? 'Never',
                    'days' => $p->user?->last_login_at ? $p->user->last_login_at->diffInDays(now()) : 999,
                ])
                ->toArray();
        } catch (\Throwable $e) {
            $this->inactiveUsers = [];
        }
    }

    private function loadIncompleteProfiles(): void
    {
        $this->incompleteProfiles = Profile::whereNotNull('full_name')
            ->where('is_active', true)
            ->where('profile_completion_pct', '<', 50)
            ->orderBy('profile_completion_pct', 'asc')
            ->limit(20)
            ->get()
            ->map(fn ($p) => [
                'matri_id' => $p->matri_id,
                'name' => $p->full_name,
                'completion' => $p->profile_completion_pct,
            ])
            ->toArray();
    }

    public function exportCsv()
    {
        return CsvExportService::exportUsers();
    }
}
