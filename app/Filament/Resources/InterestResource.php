<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InterestResource\Pages;
use App\Models\Interest;
use BackedEnum;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InterestResource extends Resource
{
    protected static ?string $model = Interest::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'All Interests';
    protected static ?string $modelLabel = 'Interest';
    protected static ?string $pluralModelLabel = 'Interests';
    protected static \UnitEnum|string|null $navigationGroup = 'Interests & Reports';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('view_member');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('view_member');
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
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('senderProfile.matri_id')
                    ->label('Sender')
                    ->searchable()
                    ->description(fn (Interest $record) => $record->senderProfile?->full_name)
                    ->url(fn (Interest $record) => $record->sender_profile_id
                        ? UserResource::getUrl('view', ['record' => $record->sender_profile_id])
                        : null),

                Tables\Columns\TextColumn::make('receiverProfile.matri_id')
                    ->label('Receiver')
                    ->searchable()
                    ->description(fn (Interest $record) => $record->receiverProfile?->full_name)
                    ->url(fn (Interest $record) => $record->receiver_profile_id
                        ? UserResource::getUrl('view', ['record' => $record->receiver_profile_id])
                        : null),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'accepted' => 'success',
                        'declined' => 'danger',
                        'cancelled' => 'gray',
                        'expired' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('custom_message')
                    ->label('Message')
                    ->limit(40)
                    ->placeholder('(template)')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('replies_count')
                    ->label('Replies')
                    ->counts('replies')
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'declined' => 'Declined',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                    ]),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['senderProfile', 'receiverProfile']));
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist->schema([
            \Filament\Schemas\Components\Section::make('Interest Details')
                ->schema([
                    Infolists\Components\TextEntry::make('senderProfile.matri_id')->label('Sender Matri ID'),
                    Infolists\Components\TextEntry::make('senderProfile.full_name')->label('Sender Name'),
                    Infolists\Components\TextEntry::make('receiverProfile.matri_id')->label('Receiver Matri ID'),
                    Infolists\Components\TextEntry::make('receiverProfile.full_name')->label('Receiver Name'),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'pending' => 'warning',
                            'accepted' => 'success',
                            'declined' => 'danger',
                            default => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('created_at')->label('Sent At')->dateTime('d M Y, h:i A'),
                    Infolists\Components\TextEntry::make('custom_message')->label('Message')->default('(Standard template used)')
                        ->columnSpanFull(),
                ])
                ->columns(3),

            \Filament\Schemas\Components\Section::make('Conversation')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('replies')
                        ->label('')
                        ->schema([
                            \Filament\Schemas\Components\Grid::make(4)->schema([
                                Infolists\Components\TextEntry::make('replierProfile.matri_id')->label('From'),
                                Infolists\Components\TextEntry::make('reply_type')->label('Type')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'accept' => 'success',
                                        'decline' => 'danger',
                                        'message' => 'info',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('custom_message')->label('Message')->default('-'),
                                Infolists\Components\TextEntry::make('created_at')->label('Date')->since(),
                            ]),
                        ])
                        ->contained(false)
                        ->placeholder('No replies yet.'),
                ])
                ->collapsed(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInterests::route('/'),
            'view' => Pages\ViewInterest::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
