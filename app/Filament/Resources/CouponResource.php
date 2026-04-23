<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use App\Models\MembershipPlan;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Discount Coupons';
    protected static ?string $modelLabel = 'Coupon';
    protected static ?string $pluralModelLabel = 'Coupons';
    protected static \UnitEnum|string|null $navigationGroup = 'Membership & Payments';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('manage_coupons');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('manage_coupons');
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

    /**
     * Branch scoping with $includeGlobal=true: a Branch Manager sees their branch's
     * coupons PLUS global coupons (where branch_id IS NULL). Super Admin sees all.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->forUserBranch(null, true);
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Coupon Details')
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('Coupon Code')
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn ($state) => strtoupper(trim($state)))
                        ->helperText('e.g., WELCOME50, DIWALI2026. Auto-converted to uppercase.'),

                    Forms\Components\TextInput::make('description')
                        ->label('Description (Internal)')
                        ->maxLength(200)
                        ->helperText('For admin reference only. Not shown to users.'),

                    \App\Filament\Forms\BranchFormField::make(
                        allowGlobal: true,
                        helperText: 'Leave blank for global coupon (visible to all branches). Pick a branch to restrict.',
                    ),

                    Forms\Components\Select::make('discount_type')
                        ->label('Discount Type')
                        ->options([
                            'percentage' => 'Percentage (%)',
                            'fixed' => 'Fixed Amount (₹)',
                        ])
                        ->required()
                        ->default('percentage')
                        ->live(),

                    Forms\Components\TextInput::make('discount_value')
                        ->label(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('discount_type') === 'fixed' ? 'Discount Amount (in Rupees)' : 'Discount Percentage')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('discount_type') === 'percentage' ? 100 : 999999)
                        ->suffix(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('discount_type') === 'percentage' ? '%' : '₹')
                        ->dehydrateStateUsing(function ($state, \Filament\Schemas\Components\Utilities\Get $get) {
                            // Store fixed amounts in paise
                            if ($get('discount_type') === 'fixed') {
                                return (int) ($state * 100);
                            }
                            return (int) $state;
                        })
                        ->formatStateUsing(function ($state, $record) {
                            if ($record && $record->discount_type === 'fixed' && $state) {
                                return $state / 100;
                            }
                            return $state;
                        })
                        ->helperText(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('discount_type') === 'fixed' ? 'Enter amount in Rupees (e.g., 500 for ₹500 off)' : 'Enter percentage (e.g., 50 for 50% off)'),

                    Forms\Components\TextInput::make('max_discount_cap')
                        ->label('Max Discount Cap (₹)')
                        ->numeric()
                        ->nullable()
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('discount_type') === 'percentage')
                        ->helperText('Maximum discount in Rupees for percentage coupons. Leave empty for no cap.')
                        ->dehydrateStateUsing(fn ($state) => $state ? (int) ($state * 100) : null)
                        ->formatStateUsing(fn ($state) => $state ? $state / 100 : null),

                    Forms\Components\TextInput::make('min_purchase_amount')
                        ->label('Minimum Purchase (₹)')
                        ->numeric()
                        ->nullable()
                        ->helperText('Minimum plan price required to use this coupon. Leave empty for no minimum.')
                        ->dehydrateStateUsing(fn ($state) => $state ? (int) ($state * 100) : null)
                        ->formatStateUsing(fn ($state) => $state ? $state / 100 : null),
                ])
                ->columns(2),

            \Filament\Schemas\Components\Section::make('Applicable Plans & Limits')
                ->schema([
                    Forms\Components\Select::make('applicable_plan_ids')
                        ->label('Applicable Plans')
                        ->multiple()
                        ->options(MembershipPlan::where('is_active', true)->pluck('plan_name', 'id'))
                        ->helperText('Leave empty to apply to ALL plans.'),

                    Forms\Components\TextInput::make('usage_limit_total')
                        ->label('Total Usage Limit')
                        ->numeric()
                        ->nullable()
                        ->minValue(1)
                        ->helperText('How many times this coupon can be used in total. Leave empty for unlimited.'),

                    Forms\Components\TextInput::make('usage_limit_per_user')
                        ->label('Per User Limit')
                        ->numeric()
                        ->required()
                        ->default(1)
                        ->minValue(1)
                        ->helperText('How many times a single user can use this coupon.'),
                ])
                ->columns(3),

            \Filament\Schemas\Components\Section::make('Validity & Status')
                ->schema([
                    Forms\Components\DatePicker::make('valid_from')
                        ->label('Valid From')
                        ->nullable()
                        ->helperText('Leave empty for immediately valid.'),

                    Forms\Components\DatePicker::make('valid_until')
                        ->label('Valid Until')
                        ->nullable()
                        ->helperText('Leave empty for no expiry.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Deactivate to disable this coupon.'),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('discount_display')
                    ->label('Discount')
                    ->getStateUsing(function (Coupon $record): string {
                        if ($record->discount_type === 'percentage') {
                            $text = $record->discount_value . '%';
                            if ($record->max_discount_cap) {
                                $text .= ' (max ₹' . number_format($record->max_discount_cap / 100) . ')';
                            }
                            return $text;
                        }
                        return '₹' . number_format($record->discount_value / 100);
                    })
                    ->badge()
                    ->color(fn (Coupon $record) => $record->discount_type === 'percentage' ? 'info' : 'success'),

                Tables\Columns\TextColumn::make('usage_display')
                    ->label('Usage')
                    ->getStateUsing(function (Coupon $record): string {
                        $used = $record->times_used;
                        $limit = $record->usage_limit_total;
                        return $limit ? "{$used} / {$limit}" : "{$used} / ∞";
                    }),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Expires')
                    ->date('M j, Y')
                    ->placeholder('No expiry')
                    ->color(fn (Coupon $record) => $record->valid_until && $record->valid_until->isPast() ? 'danger' : null),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                \App\Filament\Tables\BranchTableComponents::column(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\Filter::make('not_expired')
                    ->label('Not Expired')
                    ->query(fn ($query) => $query->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', today())))
                    ->default(),

                \App\Filament\Tables\BranchTableComponents::filter(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (Coupon $record) {
                        $new = $record->replicate();
                        $new->code = $record->code . '_COPY';
                        $new->times_used = 0;
                        $new->is_active = false;
                        $new->save();
                    }),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
