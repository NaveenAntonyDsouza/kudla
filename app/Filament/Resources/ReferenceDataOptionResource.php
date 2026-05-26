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
 * Per-row CRUD for the dropdown lists used across registration, onboarding,
 * profile-edit, partner-preferences, the admin form, and the API site/settings
 * response — religion, denomination, diocese and the other religion lists,
 * complexion, blood group, mother tongue, hobbies, cuisine, etc.
 *
 * Each option is its own row with an Active toggle. Deactivating an option
 * hides it from NEW selections without deleting it — profiles that already
 * have that value keep it. Views that handle existing-value editing add a
 * "preserve current" fallback so a saved value stays visible even after
 * deactivation (see religious-edit / partner-edit for the pattern).
 *
 * Handles both FLAT lists (one independent value per row) and simple GROUPED
 * lists via the optional `group_label` column (e.g. Denomination → Catholic /
 * Non-Catholic) — GatewayConfigProvider rebuilds the grouped shape at boot.
 *
 * Companion of App\Filament\Pages\ReferenceDataEditor, which remains for the
 * few COMPLEX nested lists this per-row model doesn't cover (Education
 * Qualifications, Occupation Categories, Country/State). Both populate the
 * same config('reference_data.X_list') tree at boot via GatewayConfigProvider,
 * so view callsites don't change regardless of which editor was used.
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
        'denomination' => 'Denomination (Christian)',
        'diocese' => 'Diocese (Christian)',
        'muslim_sect' => 'Muslim Sect',
        'jamath' => 'Muslim Community / Jamath',
        'jain_sect' => 'Jain Sect',
        'religious_observance' => 'Religious Observance',
        'gothram' => 'Gotra / Gothram',
        'nakshatra' => 'Nakshatra (Star)',
        'rasi' => 'Rashi (Zodiac)',
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

    /** Categories whose options display grouped under headings (need group_label). */
    public const GROUPED_CATEGORIES = ['denomination'];

    /**
     * Categories organised into sections, so the (now ~34-item) Category
     * picker is easy to scan. Every category in CATEGORY_LABELS must appear
     * in exactly one section here.
     */
    public const CATEGORY_GROUPS = [
        'Religion & Community' => [
            'religion', 'denomination', 'diocese', 'muslim_sect', 'jamath',
            'jain_sect', 'religious_observance', 'gothram', 'nakshatra', 'rasi',
        ],
        'Personal & Physical' => [
            'mother_tongue', 'complexion', 'body_type', 'physical_status',
            'blood_group', 'marital_status',
        ],
        'Family & Background' => [
            'family_status', 'residency_status', 'cultural_background',
            'custodian_relation', 'created_by',
        ],
        'Education & Work' => [
            'education_level', 'employment_category', 'annual_income',
        ],
        'Lifestyle & Interests' => [
            'diet', 'drinking', 'smoking', 'hobbies', 'music', 'books',
            'movies', 'sports', 'cuisine',
        ],
        'Contact' => [
            'preferred_call_time',
        ],
    ];

    /**
     * Grouped <optgroup> options for the Category select, built from the
     * sections above with the friendly label for each category.
     */
    public static function categoryOptions(): array
    {
        $out = [];
        foreach (self::CATEGORY_GROUPS as $section => $categories) {
            $out[$section] = collect($categories)
                ->mapWithKeys(fn (string $c) => [$c => self::CATEGORY_LABELS[$c] ?? $c])
                ->all();
        }

        return $out;
    }

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
                ->options(self::categoryOptions())
                ->required()
                ->searchable()
                ->live()
                ->helperText('Which dropdown does this value belong to?'),

            Forms\Components\TextInput::make('group_label')
                ->label('Group / Heading')
                ->maxLength(80)
                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('category'), self::GROUPED_CATEGORIES, true))
                ->helperText('Only for grouped lists like Denomination — e.g. "Catholic" or "Non-Catholic". Leave blank for ordinary lists.'),

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

                Tables\Columns\TextColumn::make('group_label')
                    ->label('Group')
                    ->placeholder('—')
                    ->toggleable(),

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
