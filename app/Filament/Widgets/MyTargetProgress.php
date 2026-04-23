<?php

namespace App\Filament\Widgets;

use App\Models\StaffTarget;
use Filament\Widgets\Widget;

class MyTargetProgress extends Widget
{
    protected static ?int $sort = -6;
    protected static bool $isLazy = true;
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.my-target-progress';

    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user || !$user->staff_role_id) {
            return false;
        }

        return $user->hasPermission('view_lead') || $user->isSuperAdmin();
    }

    protected function getViewData(): array
    {
        $target = StaffTarget::findForUser(auth()->id());
        $monthLabel = now()->format('F Y');

        if (!$target) {
            return [
                'hasTarget' => false,
                'monthLabel' => $monthLabel,
            ];
        }

        $actuals = $target->computeActuals();

        $progress = [
            'registrations' => [
                'actual' => $actuals['registrations'],
                'target' => $target->registration_target,
                'percent' => $target->getProgressPercent('registrations'),
            ],
            'revenue' => [
                'actual_paise' => $actuals['revenue_paise'],
                'actual_rupees' => (int) round($actuals['revenue_paise'] / 100),
                'target_paise' => $target->revenue_target,
                'target_rupees' => (int) round($target->revenue_target / 100),
                'percent' => $target->getProgressPercent('revenue'),
            ],
            'calls' => [
                'actual' => $actuals['calls'],
                'target' => $target->call_target,
                'percent' => $target->getProgressPercent('calls'),
            ],
        ];

        return [
            'hasTarget' => true,
            'monthLabel' => $monthLabel,
            'target' => $target,
            'actuals' => $actuals,
            'progress' => $progress,
            'incentive' => [
                'total_rupees' => (int) round($actuals['incentive_earned_paise'] / 100),
                'from_registrations_rupees' => (int) round($actuals['incentive_from_registrations_paise'] / 100),
                'from_revenue_rupees' => (int) round($actuals['incentive_from_revenue_paise'] / 100),
            ],
        ];
    }
}
