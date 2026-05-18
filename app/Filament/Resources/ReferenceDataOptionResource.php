<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferenceDataOptionResource\Pages;
use App\Models\ReferenceDataOption;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Admin → Content Management → Dropdown Options.
 *
 * Per-row CRUD for the ~25 flat dropdown lists used across registration,
 * onboarding, profile-edit, partner-preferences, admin form, and the
 * API site/settings response — religion, complexion, blood group,
 * mother tongue, hobbies, cuisine, etc.
 *
 * Each option has its own row with an Active toggle. Deactivating an
 * option hides it from NEW selections (registration form, profile
 * edit dropdown for users who haven't picked that value yet) without
 * deleting it — profiles that already have that value keep it. Views
 * that handle existing-value editing add a "preserve current" fallback
 * so the user's saved value remains visible even after deactivation
 * (see religious-edit / partner-edit for the pattern).
 *
 * Companion (not replacement) of App\Filament\Pages\ReferenceDataEditor:
 *   - ReferenceDataEditor: textarea-based, for GROUPED lists where
 *     each category is a nested array — educational qualifications,
 *     occupation categories, country/state, denomination/diocese.
 *   - ReferenceDataOptionResource (this): per-row + Active toggle,
 *     for FLAT lists where each value is independent.
 *
 * Both populate the same config('reference_data.X_list') tree at
 * boot via GatewayConfigProvider, so view callsites don't change
 * regardless of which editor was used.
 */
class ReferenceDataOptionResource extends Resource
{
    protected static ?string $model = ReferenceDataOption::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Dropdown Options';
    protected static ?string $modelLabel = 'Dropdown Option';
    protected static ?string $pluralModelLabel = 'Dropdown Options';
    protected static \UnitEnum|string|null $navigationGroup = 'Content Management';
    protected static ?int $navigationSort = 8;

    /**
     * Stable display labels for the categories this resource manages.
     * Order here is the order they appear in the Category dropdown.
     */
    public const CATEGORY_LABELS = [
        'religion' => 'Religion',
        'mother_tongue' => 'Mother Tongue / Languages',
        'complexion' => 'Complexion',
        'body_type' => 'Body Type',
        'physical_status' => 'Physical Status',
        'blood_group' => 'Blood Group',
        'marital_status' => 'Marital Status',
        'family_status' => 'Family Status',
        'residency_status' => 'Residency Status',
        'education_level' => 'Education Level',
        'employment_category' => 'Employment Category',
        'annual_income' => 'Annual Income',
        'diet' => 'Diet (Eating Habits)',
        'drinking' => 'Drinking Habits',
        'smoking' => 'Smoking Habits',
        'cultural_background' => 'Cultural Background',
        'hobbies' => 'Hobbies',
        'music' => 'Music Preferences',
        'books' => 'Book Preferences',
        'movies' => 'Movie Preferences',
        'sports' => 'Sports / Fitness',
        'cuisine' => 'Cuisine Preferences',
        'preferred_call_time' => 'Preferred Call Time',
        'custodian_relation' => 'Custodian Relation',
        'created_by' => 'Created By (Relation)',
    ];

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('manage_content');
    }

    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('manage_content');
    }

    public static function canViewAny(): bool { return static::canAccess(); }
    public static function canCreate(): bool { return static::canAccess(); }
    public static function canEdit($record): bool { return static::canAccess(); }
    public static function canDelete($record): bool { return static::canAccess(); }

    public static function form(Schema $form): Schema
    {
        return $form->components([
            Forms\Components\Select::make('category')
                ->label('Category')
                ->options(self::CATEGORY_LABELS)
                ->required()
                ->searchable()
                ->helperText('Which dropdown does this value belong to?'),

            Forms\Components\TextInput::make('value')
                ->label('Stored Value')
                ->required()
                ->maxLength(200)
                ->helperText('Exactly what gets saved on the profile (case-sensitive). E.g. "Hindu" — not "hindu".'),

            Forms\Components\TextInput::make('label')
                ->label('Display Label')
                ->maxLength(250)
                ->placeholder('Same as Stored Value')
                ->helperText('Optional. Leave blank to display the Stored Value as-is. Useful when you want to show a friendlier label without renaming the stored value (e.g. preserve user data).'),

            Forms\Components\TextInput::make('sort_order')
                ->label('Sort Order')
                ->numeric()
                ->default(0)
                ->helperText('Lower numbers appear first in the dropdown. Ties broken by id.'),

            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->helperText('Inactive options are hidden from NEW signups + new dropdown selections, but profiles that already have this value KEEP it. Use this to retire an option without losing user data.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state) => self::CATEGORY_LABELS[$state] ?? ucfirst(str_replace('_', ' ', $state)))
                    ->sortable(),

                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('label')
                    ->label('Display Label')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sort')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('category')
            ->groups([
                Tables\Grouping\Group::make('category')
                    ->label('Category')
                    ->getTitleFromRecordUsing(fn ($record) => self::CATEGORY_LABELS[$record->category] ?? $record->category),
            ])
            ->defaultGroup('category')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options(self::CATEGORY_LABELS)
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('toggle')
                    ->label(fn (ReferenceDataOption $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (ReferenceDataOption $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (ReferenceDataOption $record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalDescription(fn (ReferenceDataOption $record) => $record->is_active
                        ? 'This option will disappear from NEW signups. Profiles that already have this value keep it.'
                        : 'This option will reappear in NEW signups\' dropdowns.'
                    )
                    ->action(function (ReferenceDataOption $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        \Filament\Notifications\Notification::make()
                            ->title($record->is_active ? 'Activated' : 'Deactivated')
                            ->body("\"{$record->value}\" is now ".($record->is_active ? 'visible' : 'hidden').' on new signup dropdowns.')
                            ->success()
                            ->send();
                    }),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    \Filament\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->searchPlaceholder('Search by value or label...');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferenceDataOptions::route('/'),
            'create' => Pages\CreateReferenceDataOption::route('/create'),
            'edit' => Pages\EditReferenceDataOption::route('/{record}/edit'),
        ];
    }
}
