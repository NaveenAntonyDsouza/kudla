<?php

namespace App\Filament\Widgets;

use App\Models\CallLog;
use App\Models\Lead;
use App\Models\Subscription;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Branch staff performance table — each staff member in the branch with this month's metrics.
 * Visible to Branch Manager and Branch Staff (any branch-bound user).
 */
class MyBranchStaffPerformance extends TableWidget
{
    protected static ?int $sort = -4;
    protected static bool $isLazy = true;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user || !$user->staff_role_id) {
            return false;
        }
        return $user->branch_id !== null && !$user->isSuperAdmin();
    }

    public function getTableHeading(): ?string
    {
        $branchName = auth()->user()->branch?->name ?? 'My Branch';
        return $branchName . ' — Staff Performance (' . now()->format('F Y') . ')';
    }

    protected function getTableQuery(): Builder
    {
        $branchId = auth()->user()->branch_id;

        return User::query()
            ->whereNotNull('staff_role_id')
            ->where('branch_id', $branchId)
            ->whereHas('staffRole', fn ($q) => $q->whereNotIn('slug', ['super_admin']))
            ->with('staffRole');
    }

    protected function getTableColumns(): array
    {
        $monthStart = now()->startOfMonth();

        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Staff')
                ->searchable()
                ->sortable()
                ->weight('bold'),

            Tables\Columns\TextColumn::make('staffRole.name')
                ->label('Role')
                ->badge()
                ->color('gray'),

            Tables\Columns\TextColumn::make('calls_this_month')
                ->label('Calls (Month)')
                ->getStateUsing(fn (User $record) => CallLog::where('called_by_staff_id', $record->id)
                    ->where('called_at', '>=', $monthStart)
                    ->count())
                ->alignCenter()
                ->badge()
                ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

            Tables\Columns\TextColumn::make('conversions_this_month')
                ->label('Conversions')
                ->getStateUsing(fn (User $record) => Lead::where('converted_by_staff_id', $record->id)
                    ->where('converted_at', '>=', $monthStart)
                    ->count())
                ->alignCenter()
                ->badge()
                ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

            Tables\Columns\TextColumn::make('revenue_this_month')
                ->label('Revenue (Month)')
                ->getStateUsing(function (User $record) use ($monthStart) {
                    $paise = (int) Subscription::where('payment_status', 'paid')
                        ->where('created_at', '>=', $monthStart)
                        ->whereHas('user.profile', function ($q) use ($record) {
                            $q->where('created_by_staff_id', $record->id);
                        })
                        ->sum('amount');
                    return '₹' . number_format($paise / 100);
                })
                ->alignCenter(),

            Tables\Columns\TextColumn::make('open_leads')
                ->label('Open Leads')
                ->getStateUsing(fn (User $record) => Lead::where('assigned_to_staff_id', $record->id)
                    ->whereNotIn('status', ['registered', 'lost', 'not_interested'])
                    ->count())
                ->alignCenter(),
        ];
    }
}
