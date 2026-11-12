<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoginAsSessionResource\Pages;
use App\Models\LoginAsSession;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Audit Trail → Login as Member: every time a staff account opened the member
 * site as a member (App\Support\LoginAs) — who, which member, the reason they
 * typed, how long, and the pages opened. Read only.
 */
class LoginAsSessionResource extends Resource
{
    protected static ?string $model = LoginAsSession::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Login as Member';
    protected static ?string $modelLabel = 'Login as Member visit';
    protected static ?string $pluralModelLabel = 'Login as Member';
    protected static ?string $cluster = \App\Filament\Clusters\AuditTrail::class;
    protected static \UnitEnum|string|null $navigationGroup = null;
    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('view_activity_log');
    }

    /** Typing the address must not get around the hidden menu entry. */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('view_activity_log');
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    // an audit record: nobody edits or deletes it
    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff')
                    ->placeholder('(deleted account)')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('matri_id')
                    ->label('Member')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('minutes')
                    ->label('Minutes')
                    ->getStateUsing(fn (LoginAsSession $record): string => $record->isOpen() ? 'open now' : (string) $record->minutes)
                    ->badge()
                    ->color(fn (LoginAsSession $record): string => $record->isOpen() ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('pages_opened')
                    ->label('Pages')
                    ->sortable(),

                Tables\Columns\TextColumn::make('blocked_actions')
                    ->label('Refused')
                    ->tooltip('Buttons pressed that view-only refused')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ended_how')
                    ->label('Ended')
                    ->formatStateUsing(fn (?string $state): string => LoginAsSession::ENDED[$state] ?? (string) $state)
                    ->placeholder('—')
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('staff_user_id')
                    ->label('Staff')
                    ->options(fn () => User::where(fn ($q) => $q->whereNotNull('staff_role_id')->orWhere('role', 'admin'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    ),
            ])
            ->actions([
                \Filament\Actions\Action::make('pages')
                    ->label('Pages opened')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (LoginAsSession $record): string => "Pages opened as {$record->matri_id}")
                    ->modalContent(fn (LoginAsSession $record) => view('filament.components.login-as-pages', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->defaultSort('started_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoginAsSessions::route('/'),
        ];
    }
}
