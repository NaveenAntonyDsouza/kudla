<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\AdminActivityLog;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogResource extends Resource
{
    protected static ?string $model = AdminActivityLog::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Activity Log';
    protected static ?string $modelLabel = 'Activity Log';
    protected static ?string $pluralModelLabel = 'Activity Logs';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 8;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('view_activity_log');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('view_activity_log');
    }

    public static function canViewAny(): bool
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

    public static function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('admin.name')
                    ->label('Admin')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'banned') || str_contains($state, 'deleted') => 'danger',
                        str_contains($state, 'suspended') || str_contains($state, 'deactivated') => 'warning',
                        str_contains($state, 'approved') || str_contains($state, 'activated') || str_contains($state, 'restored') => 'success',
                        str_contains($state, 'replied') || str_contains($state, 'saved') || str_contains($state, 'updated') => 'info',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('target')
                    ->label('Target')
                    ->getStateUsing(function (AdminActivityLog $record): string {
                        if (!$record->model_type) {
                            return '—';
                        }
                        return $record->model_type . ' #' . $record->model_id;
                    })
                    ->color('gray'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (AdminActivityLog $record): string => $record->created_at->format('M j, Y g:i A')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options(fn () => AdminActivityLog::query()
                        ->distinct()
                        ->pluck('action', 'action')
                        ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('admin_user_id')
                    ->label('Admin')
                    ->options(fn () => User::whereHas('roles')
                        ->pluck('name', 'id')
                        ->toArray()
                    ),
            ])
            ->actions([
                \Filament\Actions\Action::make('details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Activity Details')
                    ->modalContent(fn (AdminActivityLog $record) => view('filament.components.activity-log-detail', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
