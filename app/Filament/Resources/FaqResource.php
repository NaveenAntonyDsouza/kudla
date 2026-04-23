<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'FAQs';
    protected static ?string $modelLabel = 'FAQ';
    protected static ?string $pluralModelLabel = 'FAQs';
    protected static \UnitEnum|string|null $navigationGroup = 'Content Management';
    protected static ?int $navigationSort = 2;

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
            \Filament\Schemas\Components\Section::make('FAQ Details')
                ->schema([
                    Forms\Components\Select::make('category')
                        ->label('Category')
                        ->options([
                            'Registration' => 'Registration',
                            'Profile' => 'Profile',
                            'Search' => 'Search & Matching',
                            'Membership' => 'Membership & Payments',
                            'Privacy' => 'Privacy & Safety',
                            'Contact' => 'Contact & Support',
                            'Legal' => 'Legal',
                        ])
                        ->required()
                        ->searchable(),

                    Forms\Components\TextInput::make('display_order')
                        ->label('Display Order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers appear first within the category'),

                    Forms\Components\TextInput::make('question')
                        ->label('Question')
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('answer')
                        ->label('Answer')
                        ->required()
                        ->rows(5)
                        ->maxLength(5000)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_visible')
                        ->label('Visible')
                        ->default(true)
                        ->helperText('Hidden FAQs are not shown on the FAQ page'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Registration' => 'info',
                        'Profile' => 'success',
                        'Search' => 'warning',
                        'Membership' => 'danger',
                        'Privacy' => 'gray',
                        'Contact' => 'info',
                        'Legal' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('question')
                    ->label('Question')
                    ->searchable()
                    ->limit(60)
                    ->wrap(),

                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'Registration' => 'Registration',
                        'Profile' => 'Profile',
                        'Search' => 'Search & Matching',
                        'Membership' => 'Membership & Payments',
                        'Privacy' => 'Privacy & Safety',
                        'Contact' => 'Contact & Support',
                        'Legal' => 'Legal',
                    ]),
                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Visibility'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('category')
            ->defaultSort('display_order', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
