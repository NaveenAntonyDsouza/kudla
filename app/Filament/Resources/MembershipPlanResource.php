<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MembershipPlanResource\Pages;
use App\Models\MembershipPlan;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class MembershipPlanResource extends Resource
{
    protected static ?string $model = MembershipPlan::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Subscription Plans';
    protected static ?string $modelLabel = 'Plan';
    protected static ?string $pluralModelLabel = 'Subscription Plans';
    protected static \UnitEnum|string|null $navigationGroup = 'Membership & Payments';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('edit_plan');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('edit_plan');
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
            Forms\Components\TextInput::make('plan_name')
                ->label('Plan Name')
                ->required()
                ->maxLength(50)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Forms\Set $set, ?string $state, string $operation) =>
                    $operation === 'create' ? $set('slug', Str::slug($state)) : null
                ),

            Forms\Components\TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(50)
                ->unique(ignoreRecord: true)
                ->disabled(fn (string $operation) => $operation === 'edit')
                ->dehydrated()
                ->helperText('Auto-generated from plan name. Cannot be changed after creation.'),

            Forms\Components\TextInput::make('duration_months')
                ->label('Duration (months)')
                ->required()
                ->numeric()
                ->minValue(0)
                ->default(1)
                ->helperText('0 = lifetime/free plan'),

            Forms\Components\TextInput::make('price_inr')
                ->label('Sale Price')
                ->required()
                ->numeric()
                ->minValue(0)
                ->prefix('₹')
                ->helperText('Displayed price. 0 for free plan.'),

            Forms\Components\TextInput::make('strike_price_inr')
                ->label('Original Price')
                ->numeric()
                ->minValue(0)
                ->prefix('₹')
                ->helperText('Strikethrough price for discount display. Leave empty if no discount.'),

            Forms\Components\TextInput::make('daily_interest_limit')
                ->label('Daily Interest Limit')
                ->numeric()
                ->minValue(0)
                ->default(5)
                ->helperText('Max interests a user can send per day'),

            Forms\Components\TextInput::make('view_contacts_limit')
                ->label('Total Contact Views')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->helperText('Total contacts viewable during plan. 0 = unlimited.'),

            Forms\Components\TextInput::make('daily_contact_views')
                ->label('Daily Contact Views')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->helperText('Max contacts viewable per day. 0 = unlimited.'),

            Forms\Components\Toggle::make('can_view_contact')
                ->label('Can View Contact Details')
                ->helperText('Allow viewing phone/email of other profiles'),

            Forms\Components\Toggle::make('personalized_messages')
                ->label('Personalized Messages')
                ->helperText('Allow sending personalized messages'),

            Forms\Components\Toggle::make('allows_free_member_chat')
                ->label('Free Member Chat')
                ->helperText('High-end-tier flag — when ON, members on this plan can chat with free members in both directions (matches Bharat-Platinum / Shaadi-Plus convention).'),

            Forms\Components\Toggle::make('featured_profile')
                ->label('Featured Profile')
                ->helperText('Profile appears in featured/highlighted section'),

            Forms\Components\Toggle::make('priority_support')
                ->label('Priority Support')
                ->helperText('Access to priority customer support'),

            Forms\Components\Toggle::make('is_popular')
                ->label('Show "POPULAR" Badge')
                ->helperText('Displays POPULAR badge on pricing page'),

            Forms\Components\Toggle::make('is_highlighted')
                ->label('Highlighted in Search')
                ->helperText('Profile appears at top of search results'),

            Forms\Components\TextInput::make('sort_order')
                ->label('Display Order')
                ->numeric()
                ->default(0)
                ->helperText('Lower number = appears first on pricing page'),

            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->helperText('Inactive plans are hidden from users'),

            Forms\Components\Textarea::make('features')
                ->label('Feature Labels (JSON)')
                ->rows(4)
                ->helperText('JSON array of feature labels for pricing page display. e.g., ["Send 20 interests/day", "View 500 contacts"]')
                ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $state)
                ->dehydrateStateUsing(fn ($state) => $state ? json_decode($state, true) : []),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plan_name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('duration_months')
                    ->label('Duration')
                    ->formatStateUsing(fn (int $state) => $state === 0 ? 'Lifetime' : $state . ' mo')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_inr')
                    ->label('Price')
                    ->prefix('₹')
                    ->sortable(),

                Tables\Columns\TextColumn::make('strike_price_inr')
                    ->label('Original')
                    ->prefix('₹')
                    ->placeholder('—')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('daily_interest_limit')
                    ->label('Interests/Day')
                    ->sortable(),

                Tables\Columns\TextColumn::make('view_contacts_limit')
                    ->label('Contact Views')
                    ->formatStateUsing(fn (int $state) => $state === 0 ? '∞' : $state),

                Tables\Columns\IconColumn::make('can_view_contact')
                    ->label('Contacts')
                    ->boolean(),

                Tables\Columns\IconColumn::make('personalized_messages')
                    ->label('Messages')
                    ->boolean(),

                Tables\Columns\IconColumn::make('allows_free_member_chat')
                    ->label('Free Chat')
                    ->boolean()
                    ->tooltip('When ON, members on this plan can chat with free members in both directions (Bharat-Platinum / Shaadi-Plus convention).'),

                Tables\Columns\IconColumn::make('featured_profile')
                    ->label('Featured')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_popular')
                    ->label('Popular')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user_memberships_count')
                    ->label('Subscribers')
                    ->counts('userMemberships')
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make()
                    ->before(function (MembershipPlan $record) {
                        $activeCount = $record->userMemberships()->active()->count();
                        if ($activeCount > 0) {
                            throw new \Exception("Cannot delete plan with {$activeCount} active subscribers.");
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembershipPlans::route('/'),
            'create' => Pages\CreateMembershipPlan::route('/create'),
            'edit' => Pages\EditMembershipPlan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('userMemberships');
    }
}
