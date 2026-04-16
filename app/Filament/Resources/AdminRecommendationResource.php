<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminRecommendationResource\Pages;
use App\Models\AdminRecommendation;
use App\Models\Profile;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdminRecommendationResource extends Resource
{
    protected static ?string $model = AdminRecommendation::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Recommend Matches';
    protected static ?string $modelLabel = 'Recommendation';
    protected static ?string $pluralModelLabel = 'Recommendations';
    protected static \UnitEnum|string|null $navigationGroup = 'Interests & Reports';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Recommendation Details')
                ->schema([
                    Forms\Components\Select::make('for_profile_id')
                        ->label('For User (receives recommendation)')
                        ->options(fn () => Profile::whereNotNull('full_name')
                            ->where('is_active', true)
                            ->pluck('full_name', 'id')
                            ->map(fn ($name, $id) => Profile::find($id)?->matri_id . ' - ' . $name)
                        )
                        ->searchable()
                        ->required(),

                    Forms\Components\Select::make('recommended_profile_id')
                        ->label('Recommended Profile')
                        ->options(fn () => Profile::whereNotNull('full_name')
                            ->where('is_active', true)
                            ->pluck('full_name', 'id')
                            ->map(fn ($name, $id) => Profile::find($id)?->matri_id . ' - ' . $name)
                        )
                        ->searchable()
                        ->required()
                        ->different('for_profile_id'),

                    Forms\Components\Select::make('priority')
                        ->label('Priority')
                        ->options([
                            'normal' => 'Normal',
                            'high' => 'High (Top Pick)',
                        ])
                        ->default('normal')
                        ->required(),

                    Forms\Components\Textarea::make('admin_note')
                        ->label('Admin Note')
                        ->rows(3)
                        ->placeholder('We think this profile is a great match because...')
                        ->helperText('This note is shown to the user with the recommendation.')
                        ->columnSpanFull(),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('forProfile.matri_id')
                    ->label('For User')
                    ->searchable()
                    ->description(fn (AdminRecommendation $r) => $r->forProfile?->full_name),

                Tables\Columns\TextColumn::make('recommendedProfile.matri_id')
                    ->label('Recommended')
                    ->searchable()
                    ->description(fn (AdminRecommendation $r) => $r->recommendedProfile?->full_name),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state) => $state === 'high' ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('admin_note')
                    ->label('Note')
                    ->limit(30)
                    ->placeholder('-')
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_viewed')
                    ->label('Viewed')
                    ->boolean(),

                Tables\Columns\IconColumn::make('interest_sent')
                    ->label('Interest Sent')
                    ->boolean(),

                Tables\Columns\TextColumn::make('adminUser.name')
                    ->label('By')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('priority')
                    ->options(['normal' => 'Normal', 'high' => 'High']),
                Tables\Filters\TernaryFilter::make('is_viewed')
                    ->label('Viewed'),
                Tables\Filters\TernaryFilter::make('interest_sent')
                    ->label('Interest Sent'),
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
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['forProfile', 'recommendedProfile', 'adminUser']));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminRecommendations::route('/'),
            'create' => Pages\CreateAdminRecommendation::route('/create'),
            'edit' => Pages\EditAdminRecommendation::route('/{record}/edit'),
        ];
    }
}
