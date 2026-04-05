<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Profile;
use BackedEnum;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = Profile::class;
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Users';
    protected static ?string $modelLabel = 'User';
    protected static ?string $pluralModelLabel = 'Users';
    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('matri_id')
                    ->label('Matri ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->color(fn(string $state): string => $state === 'male' ? 'info' : 'danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->limit(25)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user.phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('religiousInfo.religion')
                    ->label('Religion')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('religiousInfo.denomination')
                    ->label('Denomination')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('mother_tongue')
                    ->label('Mother Tongue')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('profile_completion_pct')
                    ->label('Complete')
                    ->suffix('%')
                    ->sortable()
                    ->alignCenter()
                    ->color(fn(int $state): string => match(true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('id_proof_verified')
                    ->label('ID Verified')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ]),

                Tables\Filters\SelectFilter::make('religion')
                    ->relationship('religiousInfo', 'religion')
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),

                Tables\Filters\TernaryFilter::make('id_proof_verified')
                    ->label('ID Verified'),

                Tables\Filters\TernaryFilter::make('is_hidden')
                    ->label('Hidden'),

                Tables\Filters\Filter::make('has_photo')
                    ->label('Has Photo')
                    ->query(fn(Builder $query) => $query->whereHas('primaryPhoto')),

                Tables\Filters\Filter::make('registered_today')
                    ->label('Registered Today')
                    ->query(fn(Builder $query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('toggleActive')
                    ->label(fn(Profile $record): string => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn(Profile $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn(Profile $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn(Profile $record) => $record->update(['is_active' => !$record->is_active])),
            ])
            ->bulkActions([
                \Filament\Actions\BulkAction::make('activate')
                    ->label('Activate Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn($records) => $records->each->update(['is_active' => true])),

                \Filament\Actions\BulkAction::make('deactivate')
                    ->label('Deactivate Selected')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn($records) => $records->each->update(['is_active' => false])),

                \Filament\Actions\ExportBulkAction::make(),
            ])
            ->searchPlaceholder('Search by name, matri ID, email, phone...');
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Basic Information')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('matri_id')->label('Matri ID')->weight('bold')->color('primary'),
                        Infolists\Components\TextEntry::make('full_name')->label('Full Name'),
                        Infolists\Components\TextEntry::make('gender')->badge()->color(fn(string $state): string => $state === 'male' ? 'info' : 'danger'),
                        Infolists\Components\TextEntry::make('date_of_birth')->label('Date of Birth')->date('d M Y'),
                        Infolists\Components\TextEntry::make('age')->label('Age')->suffix(' years'),
                        Infolists\Components\TextEntry::make('height')->label('Height'),
                        Infolists\Components\TextEntry::make('marital_status')->label('Marital Status'),
                        Infolists\Components\TextEntry::make('mother_tongue')->label('Mother Tongue'),
                        Infolists\Components\TextEntry::make('complexion'),
                    ]),

                Infolists\Components\Section::make('Account Status')
                    ->columns(4)
                    ->schema([
                        Infolists\Components\IconEntry::make('is_active')->label('Active')->boolean(),
                        Infolists\Components\IconEntry::make('is_approved')->label('Approved')->boolean(),
                        Infolists\Components\IconEntry::make('id_proof_verified')->label('ID Verified')->boolean(),
                        Infolists\Components\TextEntry::make('profile_completion_pct')->label('Completion')->suffix('%'),
                    ]),

                Infolists\Components\Section::make('Contact & Auth')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('user.email')->label('Email'),
                        Infolists\Components\TextEntry::make('user.phone')->label('Phone'),
                        Infolists\Components\TextEntry::make('user.email_verified_at')->label('Email Verified')->date('d M Y H:i')->default('Not verified'),
                        Infolists\Components\TextEntry::make('user.phone_verified_at')->label('Phone Verified')->date('d M Y H:i')->default('Not verified'),
                        Infolists\Components\TextEntry::make('user.last_login_at')->label('Last Login')->date('d M Y H:i')->default('Never'),
                    ]),

                Infolists\Components\Section::make('Religious Information')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('religiousInfo.religion')->label('Religion')->default('-'),
                        Infolists\Components\TextEntry::make('religiousInfo.denomination')->label('Denomination')->default('-'),
                        Infolists\Components\TextEntry::make('religiousInfo.caste')->label('Caste')->default('-'),
                        Infolists\Components\TextEntry::make('religiousInfo.diocese_name')->label('Diocese')->default('-'),
                    ]),

                Infolists\Components\Section::make('Education & Profession')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('educationDetail.highest_education')->label('Education')->default('-'),
                        Infolists\Components\TextEntry::make('educationDetail.occupation')->label('Occupation')->default('-'),
                        Infolists\Components\TextEntry::make('educationDetail.employer_name')->label('Employer')->default('-'),
                        Infolists\Components\TextEntry::make('educationDetail.annual_income')->label('Income')->default('-'),
                        Infolists\Components\TextEntry::make('educationDetail.working_country')->label('Working Country')->default('-'),
                    ]),

                Infolists\Components\Section::make('Location')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('locationInfo.residing_country')->label('Residing Country')->default('-'),
                        Infolists\Components\TextEntry::make('locationInfo.native_country')->label('Native Country')->default('-'),
                        Infolists\Components\TextEntry::make('locationInfo.native_state')->label('Native State')->default('-'),
                        Infolists\Components\TextEntry::make('locationInfo.native_district')->label('Native District')->default('-'),
                    ]),

                Infolists\Components\Section::make('Family')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('familyDetail.father_name')->label('Father')->default('-'),
                        Infolists\Components\TextEntry::make('familyDetail.mother_name')->label('Mother')->default('-'),
                        Infolists\Components\TextEntry::make('familyDetail.family_type')->label('Family Type')->default('-'),
                        Infolists\Components\TextEntry::make('familyDetail.family_status')->label('Family Status')->default('-'),
                    ]),

                Infolists\Components\Section::make('About Me')
                    ->schema([
                        Infolists\Components\TextEntry::make('about_me')->default('Not provided'),
                    ]),

                Infolists\Components\Section::make('Timestamps')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')->label('Registered')->dateTime('d M Y, h:i A'),
                        Infolists\Components\TextEntry::make('updated_at')->label('Last Updated')->dateTime('d M Y, h:i A'),
                        Infolists\Components\TextEntry::make('created_by')->label('Created By')->default('Self'),
                    ]),
            ]);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('full_name')
                    ->required()
                    ->maxLength(100),

                Forms\Components\Select::make('gender')
                    ->options(['male' => 'Male', 'female' => 'Female'])
                    ->required(),

                Forms\Components\DatePicker::make('date_of_birth')
                    ->required()
                    ->maxDate(now()->subYears(18)),

                Forms\Components\TextInput::make('matri_id')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\Select::make('marital_status')
                    ->options([
                        'Unmarried' => 'Unmarried',
                        'Divorced' => 'Divorced',
                        'Widow/Widower' => 'Widow/Widower',
                        'Awaiting Divorce' => 'Awaiting Divorce',
                        'Annulled' => 'Annulled',
                    ]),

                Forms\Components\TextInput::make('mother_tongue')
                    ->maxLength(50),

                Forms\Components\TextInput::make('height')
                    ->maxLength(50),

                Forms\Components\Textarea::make('about_me')
                    ->rows(3),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active'),

                Forms\Components\Toggle::make('is_approved')
                    ->label('Approved'),

                Forms\Components\Toggle::make('id_proof_verified')
                    ->label('ID Proof Verified'),

                Forms\Components\Toggle::make('is_hidden')
                    ->label('Hidden'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('full_name')
            ->with(['user', 'religiousInfo', 'educationDetail', 'locationInfo', 'familyDetail', 'primaryPhoto']);
    }
}
