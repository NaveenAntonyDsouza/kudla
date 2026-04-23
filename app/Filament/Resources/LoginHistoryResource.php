<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoginHistoryResource\Pages;
use App\Models\LoginHistory;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class LoginHistoryResource extends Resource
{
    protected static ?string $model = LoginHistory::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Login History';
    protected static ?string $modelLabel = 'Login';
    protected static ?string $pluralModelLabel = 'Login History';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 9;

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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->description(fn (LoginHistory $record): string => $record->user?->email ?? ''),

                Tables\Columns\TextColumn::make('login_method')
                    ->label('Method')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'password' => 'Password',
                        'mobile_otp' => 'Mobile OTP',
                        'email_otp' => 'Email OTP',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'password' => 'info',
                        'mobile_otp' => 'success',
                        'email_otp' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->copyable()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('device_type')
                    ->label('Device')
                    ->getStateUsing(fn (LoginHistory $record): string => $record->device_type)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Mobile' => 'success',
                        'Tablet' => 'warning',
                        'Desktop' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('device_label')
                    ->label('Browser / OS')
                    ->getStateUsing(fn (LoginHistory $record): string => $record->device_label)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('logged_in_at')
                    ->label('When')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (LoginHistory $record): string => $record->logged_in_at->format('M j, Y g:i:s A')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('login_method')
                    ->options([
                        'password' => 'Password',
                        'mobile_otp' => 'Mobile OTP',
                        'email_otp' => 'Email OTP',
                    ]),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => User::where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->limit(20)
                        ->pluck('name', 'id')
                        ->toArray())
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name),
            ])
            ->defaultSort('logged_in_at', 'desc')
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoginHistory::route('/'),
        ];
    }
}
