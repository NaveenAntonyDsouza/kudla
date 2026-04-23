<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Models\Branch;
use App\Models\User;
use App\Support\Permissions;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Branches';
    protected static ?string $modelLabel = 'Branch';
    protected static ?string $pluralModelLabel = 'Branches';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return Permissions::can('manage_staff');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, Telecallers etc. could navigate to /admin/branches by typing the URL.
     */
    public static function canAccess(): bool
    {
        return Permissions::can('manage_staff');
    }

    public static function canViewAny(): bool
    {
        return Permissions::can('manage_staff');
    }

    public static function canCreate(): bool
    {
        return Permissions::can('manage_staff');
    }

    public static function canEdit($record): bool
    {
        return Permissions::can('manage_staff');
    }

    public static function canDelete($record): bool
    {
        return Permissions::can('manage_staff');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Branch Identity')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Branch Name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g., Mangalore Branch'),

                    Forms\Components\TextInput::make('code')
                        ->label('Branch Code')
                        ->required()
                        ->maxLength(20)
                        ->alphaDash()
                        ->unique(ignoreRecord: true)
                        ->helperText('Short uppercase code used in affiliate URLs (e.g., MNG, BLR). Cannot contain spaces.')
                        ->dehydrateStateUsing(fn ($state) => strtoupper(trim($state)))
                        ->placeholder('MNG'),
                ])
                ->columns(2),

            \Filament\Schemas\Components\Section::make('Location')
                ->schema([
                    Forms\Components\TextInput::make('location')
                        ->label('City / Area')
                        ->maxLength(255)
                        ->placeholder('Mangalore'),

                    Forms\Components\Select::make('state')
                        ->label('State')
                        ->options(collect(config('reference_data.indian_states', []))
                            ->mapWithKeys(fn ($s) => [$s => $s])
                            ->toArray())
                        ->searchable()
                        ->placeholder('Select state'),

                    Forms\Components\Textarea::make('address')
                        ->label('Full Address')
                        ->rows(3)
                        ->columnSpanFull()
                        ->maxLength(500),
                ])
                ->columns(2),

            \Filament\Schemas\Components\Section::make('Contact')
                ->schema([
                    Forms\Components\TextInput::make('phone')
                        ->label('Branch Phone')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\TextInput::make('email')
                        ->label('Branch Email')
                        ->email()
                        ->maxLength(255),
                ])
                ->columns(2),

            \Filament\Schemas\Components\Section::make('Management')
                ->schema([
                    Forms\Components\Select::make('manager_user_id')
                        ->label('Branch Manager')
                        ->options(
                            User::whereHas('staffRole', fn ($q) =>
                                $q->whereIn('slug', ['branch_manager', 'manager', 'admin', 'super_admin'])
                            )
                            ->orderBy('name')
                            ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->helperText('Select a Branch Manager, Manager, or Admin to lead this branch.')
                        ->placeholder('Select manager (optional)'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive branches do not accept new registrations and are hidden from public-facing affiliate links.'),

                    Forms\Components\Toggle::make('is_head_office')
                        ->label('Head Office')
                        ->disabled(fn ($record) => $record && $record->is_head_office)
                        ->helperText('Only one branch can be marked as Head Office. Used as fallback for staff/data without explicit branch.')
                        ->dehydrated(fn ($record) => !($record && $record->is_head_office)),
                ])
                ->columns(3),

            \Filament\Schemas\Components\Section::make('Commission')
                ->description('Branch commission rate applied to subscription revenue when generating monthly payouts.')
                ->schema([
                    Forms\Components\TextInput::make('commission_pct')
                        ->label('Commission Rate (%)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.01)
                        ->suffix('%')
                        ->helperText('Percentage of subscription revenue paid to this branch. Set to 0 if branch is not paid commissions. Future payouts use this rate; existing payouts keep their frozen rate.'),
                ])
                ->columns(1),

            \Filament\Schemas\Components\Section::make('Affiliate Marketing')
                ->description('Share this branch\'s URL or QR code on social media, websites, or printed materials. Visitors who arrive via these are auto-attributed to this branch when they register.')
                ->visible(fn ($record) => $record !== null) // only show on edit, not create
                ->schema([
                    Forms\Components\Placeholder::make('affiliate_url_full')
                        ->label('Public Affiliate URL')
                        ->content(fn ($record) => $record
                            ? rtrim(config('app.url'), '/') . '/?ref=' . $record->code
                            : ''),

                    Forms\Components\Placeholder::make('affiliate_url_short')
                        ->label('Short URL (for QR / print)')
                        ->content(fn ($record) => $record
                            ? rtrim(config('app.url'), '/') . '/r/' . $record->code
                            : ''),

                    Forms\Components\Placeholder::make('qr_code')
                        ->label('QR Code')
                        ->content(function ($record) {
                            if (!$record) return '';
                            $svc = app(\App\Services\QrCodeService::class);
                            $url = $svc->shortAffiliateUrl($record->code);
                            $dataUri = $svc->dataUri($url, 200);
                            return new \Illuminate\Support\HtmlString(
                                '<div style="background:white;padding:12px;border:1px solid #e5e7eb;border-radius:8px;display:inline-block;">'
                                . '<img src="' . e($dataUri) . '" alt="QR for ' . e($record->code) . '" style="display:block;width:180px;height:180px;">'
                                . '<div style="text-align:center;font-family:monospace;font-size:11px;color:#6b7280;margin-top:6px;">' . e($url) . '</div>'
                                . '</div>'
                            );
                        })
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible(),

            \Filament\Schemas\Components\Section::make('Notes')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->rows(3)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ])
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('state')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Branch Manager')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('staff_count')
                    ->label('Staff')
                    ->counts('staff')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('profiles_count')
                    ->label('Members')
                    ->counts('profiles')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('commission_pct')
                    ->label('Commission %')
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state . '%' : '—')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_head_office')
                    ->label('HO')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All branches')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\SelectFilter::make('state')
                    ->options(collect(config('reference_data.indian_states', []))
                        ->mapWithKeys(fn ($s) => [$s => $s])
                        ->toArray())
                    ->searchable(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->before(function (Branch $record, Actions\DeleteAction $action) {
                        if ($record->is_head_office) {
                            Notification::make()
                                ->title('Cannot delete the Head Office branch.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('is_head_office', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
