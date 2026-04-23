<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffTargetResource\Pages;
use App\Models\StaffTarget;
use App\Models\User;
use App\Support\Permissions;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class StaffTargetResource extends Resource
{
    protected static ?string $model = StaffTarget::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Staff Targets';
    protected static ?string $modelLabel = 'Staff Target';
    protected static ?string $pluralModelLabel = 'Staff Targets';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return Permissions::can('manage_staff');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('manage_staff');
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return static::canAccess();
    }

    public static function canDelete($record): bool
    {
        return static::canAccess();
    }

    /**
     * Branch scoping: Branch Manager sees only targets for their branch's staff.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->forUserBranch();
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Staff & Month')
                ->schema([
                    Forms\Components\Select::make('staff_user_id')
                        ->label('Staff Member')
                        ->required()
                        ->options(
                            User::whereNotNull('staff_role_id')
                                ->whereHas('staffRole', fn ($q) => $q->whereNotIn('slug', ['super_admin']))
                                ->pluck('name', 'id')
                        )
                        ->searchable(),

                    \App\Filament\Forms\BranchFormField::make(
                        helperText: 'Auto-stamped from the staff member\'s branch when omitted.',
                    ),

                    Forms\Components\DatePicker::make('month')
                        ->label('Target Month')
                        ->required()
                        ->displayFormat('F Y')
                        ->default(now()->startOfMonth())
                        ->helperText('Select any date — will be normalized to the 1st of the month.')
                        ->dehydrateStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->startOfMonth()->toDateString() : null),
                ])
                ->columns(3),

            \Filament\Schemas\Components\Section::make('Targets')
                ->schema([
                    Forms\Components\TextInput::make('registration_target')
                        ->label('Registration Target')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->helperText('Number of new member registrations expected.'),

                    Forms\Components\TextInput::make('revenue_target_rupees')
                        ->label('Revenue Target (₹)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->prefix('₹')
                        ->helperText('Target revenue in rupees for the month.')
                        ->dehydrated(false)
                        ->afterStateHydrated(function ($component, $state, $record) {
                            if ($record) {
                                $component->state((int) round($record->revenue_target / 100));
                            }
                        }),

                    Forms\Components\TextInput::make('call_target')
                        ->label('Call Target')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->helperText('Number of calls expected per month.'),
                ])
                ->columns(3),

            \Filament\Schemas\Components\Section::make('Incentive Configuration')
                ->schema([
                    Forms\Components\TextInput::make('incentive_per_registration_rupees')
                        ->label('Incentive per Registration (₹)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->prefix('₹')
                        ->helperText('Fixed bonus paid for each new registration.')
                        ->dehydrated(false)
                        ->afterStateHydrated(function ($component, $state, $record) {
                            if ($record) {
                                $component->state((int) round($record->incentive_per_registration / 100));
                            }
                        }),

                    Forms\Components\TextInput::make('incentive_per_subscription_pct')
                        ->label('Incentive per Subscription (%)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(0)
                        ->suffix('%')
                        ->step(0.01)
                        ->helperText('Percentage of subscription revenue paid as incentive.'),
                ])
                ->columns(2),

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
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('staff.staffRole.name')
                    ->label('Role')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('month')
                    ->label('Month')
                    ->date('M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('registration_target')
                    ->label('Reg Target')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('revenue_target')
                    ->label('Revenue Target')
                    ->formatStateUsing(fn ($state) => '₹' . number_format($state / 100))
                    ->sortable(),

                Tables\Columns\TextColumn::make('call_target')
                    ->label('Call Target')
                    ->numeric(),

                Tables\Columns\TextColumn::make('incentive_per_registration')
                    ->label('Per Reg')
                    ->formatStateUsing(fn ($state) => '₹' . number_format($state / 100))
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('incentive_per_subscription_pct')
                    ->label('% of Rev')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->color('gray')
                    ->toggleable(),

                \App\Filament\Tables\BranchTableComponents::column(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('staff_user_id')
                    ->label('Staff')
                    ->relationship('staff', 'name')
                    ->searchable(),

                \App\Filament\Tables\BranchTableComponents::filter(),

                Tables\Filters\SelectFilter::make('month')
                    ->label('Month')
                    ->options(function () {
                        $options = [];
                        for ($i = 0; $i < 12; $i++) {
                            $month = now()->startOfMonth()->subMonths($i);
                            $options[$month->toDateString()] = $month->format('F Y');
                        }
                        return $options;
                    })
                    ->query(fn ($query, $data) => isset($data['value']) && $data['value']
                        ? $query->whereDate('month', $data['value'])
                        : $query),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('copyToNextMonth')
                        ->label('Copy to Next Month')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Copy Targets to Next Month')
                        ->modalDescription('This will create new target rows for the next month using the same values. Existing rows for next month will be skipped.')
                        ->action(function ($records) {
                            $created = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                $nextMonth = $record->month->copy()->addMonth()->startOfMonth();

                                $exists = StaffTarget::where('staff_user_id', $record->staff_user_id)
                                    ->whereDate('month', $nextMonth->toDateString())
                                    ->exists();

                                if ($exists) {
                                    $skipped++;
                                    continue;
                                }

                                StaffTarget::create([
                                    'staff_user_id' => $record->staff_user_id,
                                    'month' => $nextMonth->toDateString(),
                                    'registration_target' => $record->registration_target,
                                    'revenue_target' => $record->revenue_target,
                                    'call_target' => $record->call_target,
                                    'incentive_per_registration' => $record->incentive_per_registration,
                                    'incentive_per_subscription_pct' => $record->incentive_per_subscription_pct,
                                    'notes' => $record->notes,
                                ]);
                                $created++;
                            }

                            Notification::make()
                                ->title("Copied {$created} target(s) to next month" . ($skipped > 0 ? " ({$skipped} skipped — already exists)" : ''))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('month', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaffTargets::route('/'),
            'create' => Pages\CreateStaffTarget::route('/create'),
            'edit' => Pages\EditStaffTarget::route('/{record}/edit'),
        ];
    }
}
