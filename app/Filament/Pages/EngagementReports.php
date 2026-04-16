<?php

namespace App\Filament\Pages;

use App\Models\Interest;
use App\Models\ProfileView;
use App\Models\Shortlist;
use App\Services\CsvExportService;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EngagementReports extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Engagement Reports';
    protected static \UnitEnum|string|null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Engagement Reports';
    protected string $view = 'filament.pages.engagement-reports';

    public array $stats = [];
    public array $interestChart = [];
    public array $statusData = [];
    public array $mostViewed = [];
    public array $mostShortlisted = [];
    public array $mostActiveSenders = [];

    public function mount(): void
    {
        $this->loadStats();
        $this->loadInterestChart();
        $this->loadStatusData();
        $this->loadMostViewed();
        $this->loadMostShortlisted();
        $this->loadMostActiveSenders();
    }

    private function loadStats(): void
    {
        $totalInterests = Interest::count();
        $accepted = Interest::where('status', 'accepted')->count();
        $declined = Interest::where('status', 'declined')->count();
        $responded = $accepted + $declined;

        $this->stats = [
            'total_interests' => $totalInterests,
            'acceptance_rate' => $responded > 0 ? round(($accepted / $responded) * 100, 1) : 0,
            'total_views' => ProfileView::count(),
            'total_shortlists' => Shortlist::count(),
            'pending_interests' => Interest::where('status', 'pending')->count(),
        ];
    }

    private function loadInterestChart(): void
    {
        $data = Interest::select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as period"), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total', 'period')
            ->toArray();

        $this->interestChart = [
            'labels' => array_map(fn ($k) => Carbon::createFromFormat('Y-m', $k)->format('M Y'), array_keys($data)),
            'values' => array_values($data),
        ];
    }

    private function loadStatusData(): void
    {
        $this->statusData = Interest::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    private function loadMostViewed(): void
    {
        $this->mostViewed = ProfileView::select('viewed_profile_id', DB::raw('COUNT(*) as view_count'))
            ->groupBy('viewed_profile_id')
            ->orderByDesc('view_count')
            ->limit(20)
            ->with('viewedProfile')
            ->get()
            ->map(fn ($pv) => [
                'matri_id' => $pv->viewedProfile?->matri_id,
                'name' => $pv->viewedProfile?->full_name,
                'gender' => $pv->viewedProfile?->gender,
                'count' => $pv->view_count,
            ])
            ->filter(fn ($item) => $item['matri_id'] !== null)
            ->values()
            ->toArray();
    }

    private function loadMostShortlisted(): void
    {
        $this->mostShortlisted = Shortlist::select('shortlisted_profile_id', DB::raw('COUNT(*) as shortlist_count'))
            ->groupBy('shortlisted_profile_id')
            ->orderByDesc('shortlist_count')
            ->limit(20)
            ->with('shortlistedProfile')
            ->get()
            ->map(fn ($s) => [
                'matri_id' => $s->shortlistedProfile?->matri_id,
                'name' => $s->shortlistedProfile?->full_name,
                'gender' => $s->shortlistedProfile?->gender,
                'count' => $s->shortlist_count,
            ])
            ->filter(fn ($item) => $item['matri_id'] !== null)
            ->values()
            ->toArray();
    }

    private function loadMostActiveSenders(): void
    {
        $this->mostActiveSenders = Interest::select('sender_profile_id', DB::raw('COUNT(*) as sent_count'))
            ->groupBy('sender_profile_id')
            ->orderByDesc('sent_count')
            ->limit(20)
            ->with('senderProfile')
            ->get()
            ->map(fn ($i) => [
                'matri_id' => $i->senderProfile?->matri_id,
                'name' => $i->senderProfile?->full_name,
                'count' => $i->sent_count,
            ])
            ->filter(fn ($item) => $item['matri_id'] !== null)
            ->values()
            ->toArray();
    }

    public function exportCsv()
    {
        return CsvExportService::exportInterests();
    }
}
