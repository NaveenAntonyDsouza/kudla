<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Models\CallLog;
use App\Support\Permissions;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CallLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'callLogs';
    protected static ?string $title = 'Call Logs';
    protected static ?string $recordTitleAttribute = 'outcome';

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('call_type')
                ->label('Call Type')
                ->required()
                ->options(CallLog::callTypes())
                ->default('outgoing'),

            Forms\Components\Select::make('outcome')
                ->label('Outcome')
                ->required()
                ->options(CallLog::outcomeOptions())
                ->default('connected'),

            Forms\Components\TextInput::make('duration_minutes')
                ->label('Duration (minutes)')
                ->numeric()
                ->default(0)
                ->minValue(0),

            Forms\Components\DateTimePicker::make('called_at')
                ->label('Called At')
                ->required()
                ->default(now()),

            Forms\Components\Textarea::make('notes')
                ->label('Notes')
                ->rows(3)
                ->maxLength(2000)
                ->columnSpanFull(),

            Forms\Components\Toggle::make('follow_up_required')
                ->label('Follow-up Required')
                ->live()
                ->default(false),

            Forms\Components\DatePicker::make('follow_up_date')
                ->label('Follow-up Date')
                ->minDate(today())
                ->visible(fn (Forms\Get $get): bool => (bool) $get('follow_up_required')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('called_at')
                    ->label('When')
                    ->since()
                    ->tooltip(fn ($record) => $record->called_at?->format('M j, Y g:i A')),

                Tables\Columns\TextColumn::make('call_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => CallLog::callTypes()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => $state === 'outgoing' ? 'info' : 'success'),

                Tables\Columns\TextColumn::make('outcome')
                    ->label('Outcome')
                    ->formatStateUsing(fn (string $state): string => CallLog::outcomes()[$state]['label'] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => CallLog::outcomes()[$state]['color'] ?? 'gray'),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state): string => $state ? "{$state} min" : '—')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('calledByStaff.name')
                    ->label('Staff')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(40)
                    ->wrap()
                    ->color('gray')
                    ->placeholder('—'),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('New Call Log')
                    ->icon('heroicon-o-phone')
                    ->visible(fn () => Permissions::can('manage_call_log'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['called_by_staff_id'] = auth()->id();
                        return $data;
                    })
                    ->after(function (CallLog $record) {
                        // If follow-up required, update the lead's follow_up_date
                        if ($record->follow_up_required && $record->follow_up_date) {
                            $record->lead()->update(['follow_up_date' => $record->follow_up_date]);
                        }
                    }),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->visible(fn () => Permissions::can('manage_call_log')),
                Actions\DeleteAction::make()
                    ->visible(fn () => Permissions::can('manage_call_log')),
            ])
            ->defaultSort('called_at', 'desc');
    }
}
