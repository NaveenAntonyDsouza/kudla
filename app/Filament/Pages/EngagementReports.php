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
    protected static ?string $navigationLabel = 'Engagement';
    protected static \UnitEnum|string|null $navigationGroup = 'Insights';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Engagement';
    protected string $view = 'filament.pages.engagement-reports';

    public array $stats = [];
    public array $interestChart = [];
    public array $statusData = [];
    public array $mostViewed = [];
    public array $mostShortlisted = [];
    public array $mostActiveSenders = [];
    public array $chatStats = [];
    public array $byGender = [];

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('view_engagement_reports');
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
        $this->loadStats();
        $this->loadInterestChart();
        $this->loadStatusData();
        $this->loadMostViewed();
        $this->loadMostShortlisted();
        $this->loadMostActiveSenders();
        $this->loadChatStats();
        try {
            // men and women: who sends, who answers (Oct 2026)
            $this->byGender = \App\Support\MemberMix::interestsByGender(90);
        } catch (\Throwable) {
            $this->byGender = [];
        }
    }

    /**
     * Last 30 days: chats (accepted interests with messages) and where
     * interests were sent from — profile page, card, phone bar, app
     * (interests.source, recorded from Oct 2026).
     */
    private function loadChatStats(): void
    {
        $since = Carbon::now()->subDays(30);

        try {
            $messages = fn () => DB::table('interest_replies')->where('reply_type', 'message')->where('created_at', '>=', $since);
            $bySource = Interest::where('created_at', '>=', $since)
                ->select('source', DB::raw('COUNT(*) as total'))
                ->groupBy('source')
                ->pluck('total', 'source');

            $sources = [];
            foreach (Interest::SOURCES as $key => $label) {
                $sources[$label] = (int) ($bySource[$key] ?? 0);
            }
            $untracked = (int) $bySource->filter(fn ($n, $key) => ! array_key_exists((string) $key, Interest::SOURCES))->sum();
            if ($untracked > 0) {
                $sources['Before tracking'] = $untracked;
            }

            $this->chatStats = [
                'new_chats' => DB::table('interest_replies')->where('reply_type', 'accept')->where('created_at', '>=', $since)->count(),
                'active_chats' => $messages()->distinct()->count('interest_id'),
                'messages' => $messages()->count(),
                'members_chatting' => $messages()->distinct()->count('replier_profile_id'),
                'by_source' => $sources,
                'sent_total' => array_sum($sources),
            ];
        } catch (\Throwable) {
            $this->chatStats = [];
        }
    }

    private function loadStats(): void
    {
        $totalInterests = Interest::count();
        $accepted = Interest::where('status', 'accepted')->count();
        $declined = Interest::where('status', 'declined')->count();
        $responded = $accepted + $declined;

        // Answer rate (Oct 2026: the owner read "Acceptance Rate" as how many
        // get answered): answered, of interests not withdrawn by the sender.
        $answerable = $totalInterests - Interest::where('status', 'cancelled')->count();
        $since = now()->subDays(30);
        $recent = Interest::where('created_at', '>=', $since)->where('status', '!=', 'cancelled');
        $recentTotal = (clone $recent)->count();
        $recentAccepted = (clone $recent)->where('status', 'accepted')->count();
        $recentAnswered = (clone $recent)->whereIn('status', ['accepted', 'declined'])->count();

        $this->stats = [
            'total_interests' => $totalInterests,
            'answer_rate' => $answerable > 0 ? round(($responded / $answerable) * 100, 1) : 0,
            'answer_rate_30d' => $recentTotal > 0 ? round(($recentAnswered / $recentTotal) * 100) : 0,
            'acceptance_rate' => $responded > 0 ? round(($accepted / $responded) * 100, 1) : 0,
            'acceptance_rate_30d' => $recentAnswered > 0 ? round(($recentAccepted / $recentAnswered) * 100) : 0,
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
        // members' names and interest messages: its own permission (Oct 2026)
        abort_unless(\App\Support\Permissions::can('export_member_data'), 403);

        return CsvExportService::exportInterests();
    }
}
