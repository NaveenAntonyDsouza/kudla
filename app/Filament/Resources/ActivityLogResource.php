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
    protected static ?string $cluster = \App\Filament\Clusters\AuditTrail::class;
    protected static \UnitEnum|string|null $navigationGroup = null;
    protected static ?int $navigationSort = 2;

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
                    ->placeholder('System') // scheduled jobs and other things the site does by itself
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'banned') || str_contains($state, 'deleted') => 'danger',
                        str_contains($state, 'suspended') || str_contains($state, 'deactivated') => 'warning',
                        str_contains($state, 'approved') || str_contains($state, 'activated') || str_contains($state, 'restored') || str_contains($state, 'checked') => 'success',
                        str_contains($state, 'replied') || str_contains($state, 'saved') || str_contains($state, 'updated') => 'info',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                // the member by name and Matri ID, linked to their page (12 Nov 2026: "Profile #123")
                Tables\Columns\TextColumn::make('target')
                    ->label('About')
                    ->getStateUsing(fn (AdminActivityLog $record): string => $record->target_label ?? '—')
                    ->url(fn (AdminActivityLog $record): ?string => ($p = $record->targetProfile()) && ! $p->trashed()
                        ? UserResource::getUrl('view', ['record' => $p->id])
                        : null)
                    ->color(fn (AdminActivityLog $record): string => $record->targetProfile() ? 'primary' : 'gray'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (AdminActivityLog $record): string => $record->created_at->timezone(config('app.display_timezone', 'Asia/Kolkata'))->format('M j, Y g:i A')),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with(['admin', 'profileTarget']))
            ->filters([
                // a date range (12 Nov 2026: the log could only be scrolled)
                Tables\Filters\Filter::make('when')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', \Carbon\Carbon::parse($d, config('app.display_timezone', 'Asia/Kolkata'))->startOfDay()->setTimezone(config('app.timezone'))))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->where('created_at', '<=', \Carbon\Carbon::parse($d, config('app.display_timezone', 'Asia/Kolkata'))->endOfDay()->setTimezone(config('app.timezone')))))
                    ->indicateUsing(fn (array $data): array => array_values(array_filter([
                        ($data['from'] ?? null) ? 'From '.\Carbon\Carbon::parse($data['from'])->format('j M Y') : null,
                        ($data['until'] ?? null) ? 'Until '.\Carbon\Carbon::parse($data['until'])->format('j M Y') : null,
                    ]))),

                // a member, by Matri ID or name
                Tables\Filters\Filter::make('member')
                    ->schema([\Filament\Forms\Components\TextInput::make('member')->label('Member (Matri ID or name)')])
                    ->query(fn ($query, array $data) => $query->when(trim((string) ($data['member'] ?? '')), fn ($q, $term) => $q
                        ->where('model_type', 'Profile')
                        ->whereIn('model_id', \App\Models\Profile::withTrashed()
                            ->where(fn ($p) => $p->where('matri_id', $term)->orWhere('full_name', 'like', '%'.$term.'%'))
                            ->select('id'))))
                    ->indicateUsing(fn (array $data): ?string => filled($data['member'] ?? null) ? 'Member: '.$data['member'] : null),

                Tables\Filters\SelectFilter::make('action')
                    ->options(fn () => AdminActivityLog::query()
                        ->distinct()
                        ->pluck('action', 'action')
                        ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('admin_user_id')
                    ->label('Admin')
                    ->options(fn () => User::where(fn ($q) => $q->whereNotNull('staff_role_id')->orWhere('role', 'admin'))
                        ->orderBy('name')
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
