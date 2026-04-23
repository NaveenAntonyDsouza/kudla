<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestimonialResource\Pages;
use App\Models\Testimonial;
use BackedEnum;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Success Stories';
    protected static ?string $modelLabel = 'Success Story';
    protected static ?string $pluralModelLabel = 'Success Stories';
    protected static \UnitEnum|string|null $navigationGroup = 'Content Management';
    protected static ?int $navigationSort = 3;

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
            \Filament\Schemas\Components\Section::make('Story Details')
                ->schema([
                    Forms\Components\TextInput::make('couple_names')
                        ->label('Couple Names')
                        ->required()
                        ->maxLength(200)
                        ->placeholder('e.g., John & Mary')
                        ->helperText('Names displayed on the story card'),

                    Forms\Components\TextInput::make('location')
                        ->label('Location')
                        ->maxLength(100)
                        ->placeholder('e.g., Mangalore, Karnataka'),

                    Forms\Components\DatePicker::make('wedding_date')
                        ->label('Wedding Date')
                        ->maxDate(now()),

                    Forms\Components\Select::make('submitted_by_user_id')
                        ->label('Submitted By')
                        ->relationship('submittedBy', 'name')
                        ->searchable()
                        ->nullable()
                        ->helperText('Link to registered user (optional)'),

                    Forms\Components\Textarea::make('story')
                        ->label('Their Story')
                        ->required()
                        ->rows(6)
                        ->maxLength(5000)
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('photo_url')
                        ->label('Couple Photo')
                        ->image()
                        ->maxSize(5120)
                        ->directory('success-stories')
                        ->disk('public')
                        ->helperText('Wedding or engagement photo. Max 5MB.')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            \Filament\Schemas\Components\Section::make('Display Settings')
                ->schema([
                    Forms\Components\Toggle::make('is_visible')
                        ->label('Approved & Visible')
                        ->default(false)
                        ->helperText('Toggle ON to show this story on the website'),

                    Forms\Components\TextInput::make('display_order')
                        ->label('Display Order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers appear first'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_url')
                    ->label('Photo')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=S&background=8B1D91&color=fff'),

                Tables\Columns\TextColumn::make('couple_names')
                    ->label('Couple')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->searchable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('wedding_date')
                    ->label('Wedding')
                    ->date('M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('submittedBy.name')
                    ->label('Submitted By')
                    ->default('Admin')
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Approved')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Approval Status')
                    ->placeholder('All')
                    ->trueLabel('Approved')
                    ->falseLabel('Pending'),
            ])
            ->actions([
                \Filament\Actions\Action::make('toggleApproval')
                    ->label(fn (Testimonial $record) => $record->is_visible ? 'Hide' : 'Approve')
                    ->icon(fn (Testimonial $record) => $record->is_visible ? 'heroicon-o-eye-slash' : 'heroicon-o-check-circle')
                    ->color(fn (Testimonial $record) => $record->is_visible ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Testimonial $record) {
                        $record->update(['is_visible' => ! $record->is_visible]);
                        Notification::make()
                            ->title($record->is_visible ? 'Story approved and visible' : 'Story hidden')
                            ->success()
                            ->send();
                    }),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(fn (Testimonial $record) => $record->update(['is_visible' => true]));
                            Notification::make()
                                ->title($records->count() . ' stories approved')
                                ->success()
                                ->send();
                        }),
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestimonials::route('/'),
            'create' => Pages\CreateTestimonial::route('/create'),
            'edit' => Pages\EditTestimonial::route('/{record}/edit'),
        ];
    }
}
