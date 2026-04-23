<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvertisementResource\Pages;
use App\Models\Advertisement;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AdvertisementResource extends Resource
{
    protected static ?string $model = Advertisement::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Advertisements';
    protected static ?string $modelLabel = 'Advertisement';
    protected static ?string $pluralModelLabel = 'Advertisements';
    protected static \UnitEnum|string|null $navigationGroup = 'Content Management';
    protected static ?int $navigationSort = 8;

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('manage_content');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('manage_content');
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

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Ad Details')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Ad Title (internal)')
                        ->required()
                        ->maxLength(150)
                        ->helperText('For admin reference only. Not shown to users.'),

                    Forms\Components\Select::make('ad_space')
                        ->label('Ad Space')
                        ->options(Advertisement::adSpaces())
                        ->required()
                        ->searchable(),

                    Forms\Components\Select::make('type')
                        ->label('Ad Type')
                        ->options([
                            'image' => 'Image Banner',
                            'html' => 'HTML / AdSense Code',
                        ])
                        ->required()
                        ->default('image')
                        ->live(),

                    Forms\Components\TextInput::make('advertiser_name')
                        ->label('Advertiser Name')
                        ->maxLength(100)
                        ->placeholder('e.g., Wedding Photography Studio'),
                ])
                ->columns(2),

            \Filament\Schemas\Components\Section::make('Ad Content')
                ->schema([
                    Forms\Components\FileUpload::make('image_path')
                        ->label('Banner Image')
                        ->image()
                        ->maxSize(2048)
                        ->directory('advertisements')
                        ->disk('public')
                        ->helperText('Recommended sizes: 728x90 (banner), 300x250 (sidebar), 320x50 (mobile)')
                        ->visible(fn (Forms\Get $get) => $get('type') === 'image')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('click_url')
                        ->label('Click URL')
                        ->url()
                        ->placeholder('https://...')
                        ->helperText('Where the user goes when clicking the ad.')
                        ->visible(fn (Forms\Get $get) => $get('type') === 'image')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('html_code')
                        ->label('HTML / AdSense Code')
                        ->rows(6)
                        ->helperText('Paste your Google AdSense code or custom HTML here.')
                        ->visible(fn (Forms\Get $get) => $get('type') === 'html')
                        ->columnSpanFull(),
                ]),

            \Filament\Schemas\Components\Section::make('Schedule & Priority')
                ->schema([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Start Date')
                        ->helperText('Leave empty for immediately active.'),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('End Date')
                        ->helperText('Leave empty for no expiry.'),

                    Forms\Components\TextInput::make('priority')
                        ->label('Priority')
                        ->numeric()
                        ->default(0)
                        ->helperText('Higher number = shown first when multiple ads exist for the same slot.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('ad_space')
                    ->label('Slot')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => Advertisement::adSpaces()[$state] ?? $state),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'image' ? 'success' : 'warning'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('impressions')
                    ->label('Views')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('clicks')
                    ->label('Clicks')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ctr')
                    ->label('CTR')
                    ->getStateUsing(fn (Advertisement $record): string => $record->ctr),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Expires')
                    ->date('M j, Y')
                    ->placeholder('No expiry')
                    ->color(fn (Advertisement $record) => $record->end_date && $record->end_date->isPast() ? 'danger' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ad_space')
                    ->options(Advertisement::adSpaces()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('resetStats')
                    ->label('Reset Stats')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(fn (Advertisement $record) => $record->update(['impressions' => 0, 'clicks' => 0])),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('priority', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvertisements::route('/'),
            'create' => Pages\CreateAdvertisement::route('/create'),
            'edit' => Pages\EditAdvertisement::route('/{record}/edit'),
        ];
    }
}
