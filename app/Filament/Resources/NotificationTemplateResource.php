<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationTemplateResource\Pages;
use App\Models\NotificationTemplate;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Notification Templates';
    protected static ?string $modelLabel = 'Notification Template';
    protected static ?string $pluralModelLabel = 'Notification Templates';
    protected static \UnitEnum|string|null $navigationGroup = 'Content Management';
    protected static ?int $navigationSort = 5;

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
            \Filament\Schemas\Components\Section::make('Template')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Template Name')
                        ->required()
                        ->maxLength(150),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Internal identifier. Cannot be changed.'),

                    Forms\Components\TextInput::make('title_template')
                        ->label('Notification Title')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Use {{VARIABLE}} placeholders. e.g., "New Interest from {{SENDER_MATRI_ID}}"'),

                    Forms\Components\Textarea::make('body_template')
                        ->label('Notification Body')
                        ->required()
                        ->rows(4)
                        ->helperText('Use {{VARIABLE}} placeholders for dynamic content.'),

                    Forms\Components\TagsInput::make('variables')
                        ->label('Available Variables')
                        ->helperText('Variables that can be used as {{VARIABLE}} in title and body.'),

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
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('title_template')
                    ->label('Title')
                    ->limit(40)
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modified')
                    ->since(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationTemplates::route('/'),
            'edit' => Pages\EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
