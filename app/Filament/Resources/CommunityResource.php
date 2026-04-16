<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommunityResource\Pages;
use App\Models\Community;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CommunityResource extends Resource
{
    protected static ?string $model = Community::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Communities';
    protected static ?string $modelLabel = 'Community';
    protected static ?string $pluralModelLabel = 'Communities';
    protected static \UnitEnum|string|null $navigationGroup = 'Content Management';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Community Details')
                ->schema([
                    Forms\Components\Select::make('religion')
                        ->label('Religion')
                        ->options([
                            'Hindu' => 'Hindu',
                            'Christian' => 'Christian',
                            'Muslim' => 'Muslim',
                            'Jain' => 'Jain',
                            'Other' => 'Other',
                        ])
                        ->required()
                        ->searchable(),

                    Forms\Components\TextInput::make('community_name')
                        ->label('Community Name')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TagsInput::make('sub_communities')
                        ->label('Sub-Communities')
                        ->helperText('Press Enter after each sub-community name to add it.')
                        ->placeholder('Type and press Enter')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers appear first'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive communities are hidden from registration and search'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('religion')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Hindu' => 'warning',
                        'Christian' => 'info',
                        'Muslim' => 'success',
                        'Jain' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('community_name')
                    ->label('Community')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sub_communities')
                    ->label('Sub-Communities')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' items' : '0 items')
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('religion')
                    ->options([
                        'Hindu' => 'Hindu',
                        'Christian' => 'Christian',
                        'Muslim' => 'Muslim',
                        'Jain' => 'Jain',
                        'Other' => 'Other',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
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
            ->defaultSort('sort_order', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommunities::route('/'),
            'create' => Pages\CreateCommunity::route('/create'),
            'edit' => Pages\EditCommunity::route('/{record}/edit'),
        ];
    }
}
