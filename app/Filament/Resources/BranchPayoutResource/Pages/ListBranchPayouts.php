<?php

namespace App\Filament\Resources\BranchPayoutResource\Pages;

use App\Filament\Resources\BranchPayoutResource;
use App\Services\BranchPayoutService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListBranchPayouts extends ListRecords
{
    protected static string $resource = BranchPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_for_month')
                ->label('Generate for Month')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('warning')
                ->form([
                    Forms\Components\DatePicker::make('month')
                        ->label('Target Month')
                        ->required()
                        ->displayFormat('F Y')
                        ->default(now()->subMonth()->startOfMonth())
                        ->helperText('Select any date — will be normalized to that month\'s 1st.')
                        ->dehydrateStateUsing(fn ($state) => $state ? Carbon::parse($state)->startOfMonth()->toDateString() : null),
                ])
                ->action(function (array $data) {
                    $month = Carbon::parse($data['month'])->startOfMonth();
                    $created = app(BranchPayoutService::class)->generateForMonth($month, auth()->id());

                    if ($created->isEmpty()) {
                        Notification::make()
                            ->title('No new payouts created')
                            ->body('Either all eligible branches already have payouts for ' . $month->format('F Y') . ', or no branches generated revenue.')
                            ->warning()
                            ->send();
                    } else {
                        $total = $created->sum('payout_amount_paise') / 100;
                        Notification::make()
                            ->title("Generated {$created->count()} payouts for " . $month->format('F Y'))
                            ->body("Total: ₹" . number_format($total, 2))
                            ->success()
                            ->send();
                    }
                }),

            Actions\CreateAction::make()
                ->label('New Payout (Manual)'),
        ];
    }
}
