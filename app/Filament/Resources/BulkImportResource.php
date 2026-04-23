<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BulkImportResource\Pages;
use App\Models\BulkImport;
use App\Services\BulkImportSchema;
use App\Support\Permissions;
use BackedEnum;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class BulkImportResource extends Resource
{
    protected static ?string $model = BulkImport::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Bulk Import';
    protected static ?string $modelLabel = 'Bulk Import';
    protected static ?string $pluralModelLabel = 'Bulk Imports';
    protected static \UnitEnum|string|null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 8;

    public static function shouldRegisterNavigation(): bool
    {
        return Permissions::can('manage_bulk_import');
    }

    public static function canAccess(): bool
    {
        return Permissions::can('manage_bulk_import');
    }

    public static function canViewAny(): bool { return static::canAccess(); }
    public static function canCreate(): bool { return static::canAccess(); }
    public static function canEdit($record): bool { return false; } // read-only after upload
    public static function canDelete($record): bool { return static::canAccess(); }

    public static function form(Schema $form): Schema
    {
        // Form is defined directly on the Upload page, not here
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('original_filename')
                    ->label('File')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('defaultBranch.name')
                    ->label('Default Branch')
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => BulkImport::statusColors()[$state] ?? 'gray')
                    ->formatStateUsing(fn ($state) => BulkImport::statusOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('total_rows')
                    ->label('Rows')
                    ->alignRight()
                    ->numeric(),

                Tables\Columns\TextColumn::make('valid_rows')
                    ->label('Valid')
                    ->alignRight()
                    ->color('success')
                    ->numeric()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('invalid_rows')
                    ->label('Invalid')
                    ->alignRight()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->numeric()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('imported_count')
                    ->label('Imported')
                    ->alignRight()
                    ->weight('bold')
                    ->color('success')
                    ->numeric(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Started')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->since()
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(BulkImport::statusOptions()),
            ])
            ->actions([
                Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn (BulkImport $record) => in_array($record->status, ['draft', 'validated', 'completed', 'failed']))
                    ->url(fn (BulkImport $record) => static::getUrl('preview', ['record' => $record])),

                Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBulkImports::route('/'),
            'create' => Pages\UploadBulkImport::route('/upload'),
            'preview' => Pages\PreviewBulkImport::route('/{record}/preview'),
        ];
    }
}
