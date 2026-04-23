<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffRoleResource\Pages;
use App\Models\StaffRole;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class StaffRoleResource extends Resource
{
    protected static ?string $model = StaffRole::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Staff Roles';
    protected static ?string $modelLabel = 'Staff Role';
    protected static ?string $pluralModelLabel = 'Staff Roles';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false; // 10 system roles are fixed
    }

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('manage_roles');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('manage_roles');
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
        $permissionConfig = config('permissions.permissions');
        $categoryLabels = config('permissions.categories');

        // Group permissions by category
        $permissionsByCategory = [];
        foreach ($permissionConfig as $key => $def) {
            $permissionsByCategory[$def['category']][$key] = $def;
        }

        // Build category sections
        $sections = [];
        foreach ($categoryLabels as $categoryKey => $categoryLabel) {
            if (!isset($permissionsByCategory[$categoryKey])) {
                continue;
            }

            $fields = [];
            foreach ($permissionsByCategory[$categoryKey] as $permKey => $def) {
                if ($def['type'] === 'scoped') {
                    $fields[] = Forms\Components\Radio::make("perm_{$permKey}")
                        ->label($def['label'])
                        ->options([
                            'all' => 'All Members',
                            'own' => 'Own Members',
                            'none' => 'No',
                        ])
                        ->default('none')
                        ->inline()
                        ->inlineLabel(false)
                        ->columnSpanFull()
                        ->disabled(fn (?StaffRole $record) => $record?->isSuperAdmin() ?? false);
                } else {
                    $fields[] = Forms\Components\Radio::make("perm_{$permKey}")
                        ->label($def['label'])
                        ->options([
                            'yes' => 'Yes',
                            'no' => 'No',
                        ])
                        ->default('no')
                        ->inline()
                        ->inlineLabel(false)
                        ->columnSpanFull()
                        ->disabled(fn (?StaffRole $record) => $record?->isSuperAdmin() ?? false);
                }
            }

            $sections[] = \Filament\Schemas\Components\Section::make($categoryLabel)
                ->schema($fields)
                ->collapsible()
                ->collapsed(fn (?StaffRole $record) => $record?->isSuperAdmin() ?? false);
        }

        return $form->schema(array_merge([
            \Filament\Schemas\Components\Section::make('Role Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Role Name')
                        ->required()
                        ->disabled(fn (?StaffRole $record) => $record?->is_system ?? false),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\Textarea::make('description')
                        ->rows(2)
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->disabled(fn (?StaffRole $record) => $record?->isSuperAdmin() ?? false),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->disabled(fn (?StaffRole $record) => $record?->isSuperAdmin() ?? false),
                ])
                ->columns(2),
        ], $sections));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Role Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->color('gray')
                    ->fontFamily('mono')
                    ->copyable(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_system')
                    ->label('System')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()
                    ->label('Edit Permissions'),
            ])
            ->defaultSort('sort_order', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaffRoles::route('/'),
            'edit' => Pages\EditStaffRole::route('/{record}/edit'),
        ];
    }
}
