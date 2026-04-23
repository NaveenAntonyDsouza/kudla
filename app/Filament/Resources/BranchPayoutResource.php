<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchPayoutResource\Pages;
use App\Models\Branch;
use App\Models\BranchPayout;
use App\Support\Permissions;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BranchPayoutResource extends Resource
{
    protected static ?string $model = BranchPayout::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Branch Payouts';
    protected static ?string $modelLabel = 'Branch Payout';
    protected static ?string $pluralModelLabel = 'Branch Payouts';
    protected static \UnitEnum|string|null $navigationGroup = 'Membership & Payments';
    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return Permissions::can('manage_payouts');
    }

    public static function canAccess(): bool
    {
        return Permissions::can('manage_payouts');
    }

    public static function canViewAny(): bool { return static::canAccess(); }
    public static function canCreate(): bool { return static::canAccess(); }
    public static function canEdit($record): bool { return static::canAccess(); }
    public static function canDelete($record): bool { return static::canAccess(); }

    /**
     * Branch scoping — Branch Manager sees only their branch's payouts.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['branch', 'createdByUser'])
            ->forUserBranch();
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Payout Details')
                ->schema([
                    Forms\Components\Select::make('branch_id')
                        ->label('Branch')
                        ->options(
                            Branch::active()
                                ->where('is_head_office', false)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->required()
                        ->searchable()
                        ->disabled(fn ($record) => $record !== null) // can't change branch on existing payout
                        ->dehydrated(),

                    Forms\Components\DatePicker::make('period_start')
                        ->label('Period Start')
                        ->required()
                        ->displayFormat('M Y')
                        ->default(now()->subMonth()->startOfMonth())
                        ->dehydrateStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->startOfMonth()->toDateString() : null)
                        ->disabled(fn ($record) => $record !== null),

                    Forms\Components\DatePicker::make('period_end')
                        ->label('Period End')
                        ->required()
                        ->default(now()->subMonth()->endOfMonth())
                        ->dehydrateStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->endOfMonth()->toDateString() : null)
                        ->disabled(fn ($record) => $record !== null),
                ])
                ->columns(3),

            \Filament\Schemas\Components\Section::make('Calculation')
                ->schema([
                    Forms\Components\TextInput::make('gross_revenue_paise')
                        ->label('Gross Revenue (₹)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₹')
                        ->dehydrated(false)
                        ->afterStateHydrated(function ($component, $state, $record) {
                            if ($record) {
                                $component->state(round($record->gross_revenue_paise / 100, 2));
                            }
                        })
                        ->helperText('Total subscription revenue from this branch in this period.'),

                    Forms\Components\TextInput::make('commission_pct')
                        ->label('Commission Rate (%)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.01)
                        ->suffix('%'),

                    Forms\Components\TextInput::make('payout_amount_paise')
                        ->label('Payout Amount (₹)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₹')
                        ->dehydrated(false)
                        ->afterStateHydrated(function ($component, $state, $record) {
                            if ($record) {
                                $component->state(round($record->payout_amount_paise / 100, 2));
                            }
                        })
                        ->helperText('Auto-calculated as Gross × Commission %. Override if needed.'),
                ])
                ->columns(3),

            \Filament\Schemas\Components\Section::make('Payment Status')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(BranchPayout::statusOptions())
                        ->required()
                        ->default('pending')
                        ->reactive(),

                    Forms\Components\DatePicker::make('paid_on')
                        ->label('Paid On')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('status') === 'paid')
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('status') === 'paid')
                        ->maxDate(now()),

                    Forms\Components\TextInput::make('transaction_reference')
                        ->label('Transaction Reference')
                        ->maxLength(255)
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('status') === 'paid')
                        ->placeholder('e.g., NEFT-AB123456, UPI ref, Cheque #')
                        ->helperText('Bank reference / UPI ID / cheque number for record-keeping.'),
                ])
                ->columns(3),

            \Filament\Schemas\Components\Section::make('Notes')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->rows(3)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ])
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('branch.code')
                    ->label('Code')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('period_start')
                    ->label('Period')
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('M Y') : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('gross_revenue_paise')
                    ->label('Gross Revenue')
                    ->formatStateUsing(fn ($state) => '₹' . number_format($state / 100, 2))
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('commission_pct')
                    ->label('%')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->alignCenter()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('payout_amount_paise')
                    ->label('Payout')
                    ->formatStateUsing(fn ($state) => '₹' . number_format($state / 100, 2))
                    ->alignRight()
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => BranchPayout::statusColors()[$state] ?? 'gray')
                    ->formatStateUsing(fn ($state) => BranchPayout::statusOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('paid_on')
                    ->label('Paid On')
                    ->date('M j, Y')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('transaction_reference')
                    ->label('Txn Ref')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('createdByUser.name')
                    ->label('Created By')
                    ->placeholder('System')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(BranchPayout::statusOptions()),

                \App\Filament\Tables\BranchTableComponents::filter(),

                Tables\Filters\SelectFilter::make('period')
                    ->label('Period')
                    ->options(function () {
                        $opts = [];
                        for ($i = 0; $i < 12; $i++) {
                            $m = now()->startOfMonth()->subMonths($i);
                            $opts[$m->toDateString()] = $m->format('F Y');
                        }
                        return $opts;
                    })
                    ->query(fn ($query, $data) => isset($data['value']) && $data['value']
                        ? $query->whereDate('period_start', $data['value'])
                        : $query),
            ])
            ->actions([
                Actions\Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (BranchPayout $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\DatePicker::make('paid_on')
                            ->label('Paid On')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),
                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('Transaction Reference')
                            ->maxLength(255)
                            ->placeholder('e.g., NEFT-AB123456'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Additional Notes')
                            ->rows(2)
                            ->maxLength(500),
                    ])
                    ->action(function (BranchPayout $record, array $data) {
                        app(\App\Services\BranchPayoutService::class)->markAsPaid(
                            $record,
                            \Carbon\Carbon::parse($data['paid_on']),
                            $data['transaction_reference'] ?? null,
                            $data['notes'] ?? null
                        );
                        Notification::make()
                            ->title("Payout marked as paid")
                            ->body("₹" . number_format($record->payout_amount_paise / 100, 2) . ' to ' . $record->branch->name)
                            ->success()
                            ->send();
                    }),

                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('exportCsv')
                        ->label('Export CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($records) {
                            $csv = "Branch,Code,Period,Gross Revenue (Rs),Commission %,Payout (Rs),Status,Paid On,Transaction Ref,Notes\n";
                            foreach ($records as $r) {
                                $csv .= '"' . str_replace('"', '""', $r->branch?->name ?? '') . '",';
                                $csv .= '"' . ($r->branch?->code ?? '') . '",';
                                $csv .= '"' . $r->period_start?->format('M Y') . '",';
                                $csv .= number_format($r->gross_revenue_paise / 100, 2) . ',';
                                $csv .= $r->commission_pct . ',';
                                $csv .= number_format($r->payout_amount_paise / 100, 2) . ',';
                                $csv .= $r->status . ',';
                                $csv .= '"' . ($r->paid_on?->format('Y-m-d') ?? '') . '",';
                                $csv .= '"' . str_replace('"', '""', $r->transaction_reference ?? '') . '",';
                                $csv .= '"' . str_replace('"', '""', str_replace("\n", ' | ', $r->notes ?? '')) . '"';
                                $csv .= "\n";
                            }
                            return response()->streamDownload(
                                fn () => print($csv),
                                'branch-payouts-' . now()->format('Y-m-d') . '.csv',
                                ['Content-Type' => 'text/csv']
                            );
                        })
                        ->deselectRecordsAfterCompletion(),

                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('period_start', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranchPayouts::route('/'),
            'create' => Pages\CreateBranchPayout::route('/create'),
            'edit' => Pages\EditBranchPayout::route('/{record}/edit'),
        ];
    }
}
