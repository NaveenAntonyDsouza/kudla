<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MembershipResource\Pages;
use App\Models\UserMembership;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MembershipResource extends Resource
{
    protected static ?string $model = UserMembership::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Memberships';
    protected static ?string $modelLabel = 'Membership';
    protected static ?string $pluralModelLabel = 'Memberships';
    protected static \UnitEnum|string|null $navigationGroup = 'Membership & Payments';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('view_payment_history');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('view_payment_history');
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->description(fn (UserMembership $record) => $record->user?->profile?->matri_id)
                    ->limit(25),

                Tables\Columns\TextColumn::make('plan.plan_name')
                    ->label('Plan')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'diamond plus' => 'success',
                        'diamond' => 'info',
                        'gold' => 'warning',
                        'silver' => 'primary',
                        'free' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('plan.price_inr')
                    ->label('Amount')
                    ->prefix('₹')
                    ->sortable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Start')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Expires')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn($state) => $state && $state < now() ? 'danger' : 'success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),

                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->relationship('plan', 'plan_name'),

                Tables\Filters\Filter::make('expired')
                    ->label('Expired')
                    ->query(fn(Builder $query) => $query->where('ends_at', '<', now())),
            ])
            ->actions([
                \Filament\Actions\Action::make('toggleActive')
                    ->label(fn(UserMembership $record): string => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn(UserMembership $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn(UserMembership $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn(UserMembership $record) => $record->update(['is_active' => !$record->is_active])),

                \Filament\Actions\Action::make('extend')
                    ->label('Extend')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->form([
                        Forms\Components\DatePicker::make('ends_at')
                            ->label('New Expiry Date')
                            ->required()
                            ->minDate(now()),
                    ])
                    ->action(fn(UserMembership $record, array $data) => $record->update([
                        'ends_at' => $data['ends_at'],
                        'is_active' => true,
                    ])),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('manualActivate')
                    ->label('Manual Activate')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('plan_id')
                            ->label('Plan')
                            ->relationship('plan', 'plan_name')
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('starts_at')
                            ->label('Start Date')
                            ->default(now())
                            ->required(),

                        Forms\Components\DatePicker::make('ends_at')
                            ->label('End Date')
                            ->required(),

                        Forms\Components\Textarea::make('admin_note')
                            ->label('Admin Note (e.g., "Complimentary for beta tester")')
                            ->rows(2),
                    ])
                    ->action(function (array $data) {
                        UserMembership::create([
                            'user_id' => $data['user_id'],
                            'plan_id' => $data['plan_id'],
                            'starts_at' => $data['starts_at'],
                            'ends_at' => $data['ends_at'],
                            'is_active' => true,
                        ]);
                    }),
            ]);
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMemberships::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user.profile', 'plan']);
    }
}
