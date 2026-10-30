<?php

namespace App\Filament\Widgets;

use App\Models\Profile;
use App\Support\MemberMix;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

/** Insights → Members Overview: the headline counts. */
class MembersStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected static bool $isLazy = true;
    protected static bool $isDiscovered = false;

    public static function canView(): bool
    {
        return \App\Support\Permissions::can('view_user_reports');
    }

    protected function getStats(): array
    {
        $s = Cache::remember('insights_members_stats', 300, function () {
            $balance = MemberMix::genderBalance();

            return [
                'total' => Profile::whereNotNull('full_name')->count(),
                'live' => array_sum($balance['live']),
                'active' => array_sum($balance['active']),
                'men' => $balance['active']['male'],
                'women' => $balance['active']['female'],
                'new_month' => Profile::whereNotNull('full_name')->where('created_at', '>=', now()->startOfMonth())->count(),
                'completion' => (int) Profile::whereNotNull('full_name')->avg('profile_completion_pct'),
            ];
        });

        $ratio = $s['women'] > 0 ? round($s['men'] / $s['women'], 1) : null;

        return [
            Stat::make('Total profiles', number_format($s['total']))->description('Everyone who signed up, not deleted'),
            Stat::make('Live profiles', number_format($s['live']))->description('Switched on, shown to members'),
            Stat::make('Active members', number_format($s['active']))->description('Used the site in the last 30 days'),
            Stat::make('Men per woman', $ratio === null ? '—' : $ratio . ' : 1')->description("Active: {$s['men']} men, {$s['women']} women"),
            Stat::make('New this month', number_format($s['new_month']))->description(now()->format('F Y')),
            Stat::make('Profile completion', $s['completion'] . '%')->description('Average, all profiles'),
        ];
    }
}
