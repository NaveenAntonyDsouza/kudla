<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\MembershipPlan;
use App\Models\Profile;
use App\Models\ProfileNote;
use App\Traits\LogsAdminActivity;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    use LogsAdminActivity;
    protected static ?string $model = Profile::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'All Members';
    protected static ?string $modelLabel = 'User';
    protected static ?string $pluralModelLabel = 'Users';
    protected static \UnitEnum|string|null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('view_member');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('view_member');
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    // Photo — left side
                    Tables\Columns\ImageColumn::make('primaryPhoto.photo_url')
                        ->disk('public')
                        ->circular()
                        ->size(70)
                        ->defaultImageUrl(url('/images/default-avatar.svg'))
                        ->grow(false),

                    // Main content — right side
                    Tables\Columns\Layout\Stack::make([
                        // Row 1: Name (Matri ID) + Plan badge + Status
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('full_name')
                                ->weight('bold')
                                ->size('lg')
                                ->searchable()
                                ->sortable()
                                ->formatStateUsing(function ($state, Profile $record) {
                                    return $state . ' ( ' . $record->matri_id . ' )';
                                }),

                            Tables\Columns\TextColumn::make('plan_badge')
                                ->label('Plan')
                                ->badge()
                                ->getStateUsing(function (Profile $record): string {
                                    $membership = $record->user?->activeMembership();
                                    return $membership?->plan?->plan_name ?? 'Free';
                                })
                                ->color(fn(string $state): string => match ($state) {
                                    'Diamond Plus' => 'success',
                                    'Diamond' => 'info',
                                    'Gold' => 'warning',
                                    'Silver' => 'primary',
                                    default => 'gray',
                                })
                                ->grow(false),

                            Tables\Columns\TextColumn::make('is_approved')
                                ->label('Status')
                                ->badge()
                                ->getStateUsing(fn (Profile $record): string => $record->is_approved ? 'APPROVED' : 'PENDING')
                                ->icon(fn (string $state): string => $state === 'APPROVED' ? 'heroicon-o-check-badge' : 'heroicon-o-clock')
                                ->color(fn (string $state): string => $state === 'APPROVED' ? 'success' : 'warning')
                                ->grow(false),

                            Tables\Columns\TextColumn::make('vip_featured_badges')
                                ->label('')
                                ->getStateUsing(function (Profile $record): ?string {
                                    if ($record->is_vip) return 'VIP';
                                    if ($record->is_featured) return 'Featured';
                                    return null;
                                })
                                ->badge()
                                ->color(fn (?string $state): string => $state === 'VIP' ? 'warning' : 'info')
                                ->icon(fn (?string $state): ?string => $state === 'VIP' ? 'heroicon-o-trophy' : ($state === 'Featured' ? 'heroicon-o-sparkles' : null))
                                ->grow(false),
                        ]),

                        // Row 2: Contact + Basic details
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('gender')
                                ->label('Gender')
                                ->badge()
                                ->color(fn(string $state): string => $state === 'male' ? 'info' : 'danger')
                                ->grow(false),

                            Tables\Columns\TextColumn::make('date_of_birth')
                                ->label('Age')
                                ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->age . ' yrs' : '-')
                                ->icon('heroicon-o-cake')
                                ->grow(false),

                            Tables\Columns\TextColumn::make('user.phone')
                                ->label('Phone')
                                ->searchable()
                                ->icon('heroicon-o-phone')
                                ->grow(false),

                            Tables\Columns\TextColumn::make('user.email')
                                ->label('Email')
                                ->searchable()
                                ->icon('heroicon-o-envelope')
                                ->limit(25)
                                ->grow(false),

                            Tables\Columns\TextColumn::make('educationDetail.highest_education')
                                ->label('Education')
                                ->icon('heroicon-o-academic-cap')
                                ->placeholder('-')
                                ->limit(20)
                                ->grow(false),

                            Tables\Columns\TextColumn::make('educationDetail.occupation')
                                ->label('Occupation')
                                ->icon('heroicon-o-briefcase')
                                ->placeholder('-')
                                ->limit(20)
                                ->grow(false),
                        ]),

                        // Row 3: Religion, Location, Marital Status, Mother Tongue, Income
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('religiousInfo.religion')
                                ->label('Religion')
                                ->icon('heroicon-o-globe-alt')
                                ->formatStateUsing(function ($state, Profile $record) {
                                    $denom = $record->religiousInfo?->denomination;
                                    $caste = $record->religiousInfo?->caste;
                                    if ($denom) return ($state ?? '-') . ' / ' . $denom;
                                    if ($caste) return ($state ?? '-') . ' / ' . $caste;
                                    return $state ?? '-';
                                })
                                ->grow(false),

                            Tables\Columns\TextColumn::make('locationInfo.native_state')
                                ->label('Location')
                                ->icon('heroicon-o-map-pin')
                                ->formatStateUsing(function ($state, Profile $record) {
                                    $district = $record->locationInfo?->native_district;
                                    if ($district && $state) return $district . ', ' . $state;
                                    return $state ?: $district ?: '-';
                                })
                                ->grow(false),

                            Tables\Columns\TextColumn::make('marital_status')
                                ->icon('heroicon-o-heart')
                                ->placeholder('-')
                                ->grow(false),

                            Tables\Columns\TextColumn::make('mother_tongue')
                                ->icon('heroicon-o-language')
                                ->placeholder('-')
                                ->grow(false),

                            Tables\Columns\TextColumn::make('educationDetail.annual_income')
                                ->label('Income')
                                ->icon('heroicon-o-currency-rupee')
                                ->placeholder('-')
                                ->grow(false),

                            Tables\Columns\TextColumn::make('created_by')
                                ->label('Created By')
                                ->formatStateUsing(fn ($state) => $state ? 'By: ' . ucfirst($state) : null)
                                ->placeholder('')
                                ->color('gray')
                                ->grow(false),
                        ]),

                        // Row 4: Profile Completion, Registered, Last Login, Notes, ID Verified
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('profile_completion_pct')
                                ->label('Completion')
                                ->formatStateUsing(fn ($state) => 'Profile: ' . ($state ?? 0) . '%')
                                ->color(fn($state): string => match(true) {
                                    ($state ?? 0) >= 80 => 'success',
                                    ($state ?? 0) >= 50 => 'warning',
                                    default => 'danger',
                                })
                                ->sortable()
                                ->grow(false),

                            Tables\Columns\TextColumn::make('created_at')
                                ->label('Registered')
                                ->getStateUsing(function (Profile $record): string {
                                    $date = $record->created_at;
                                    if (!$date) return 'Registered: -';
                                    return 'Registered: ' . $date->format('d M Y, h:i A') . ' (' . $date->diffForHumans() . ')';
                                })
                                ->sortable()
                                ->color('gray')
                                ->grow(false),

                            Tables\Columns\TextColumn::make('last_login_display')
                                ->label('Last Login')
                                ->getStateUsing(function (Profile $record): string {
                                    $lastLogin = $record->user?->last_login_at;
                                    if (!$lastLogin) return 'Last Login: Never';
                                    $lastLogin = Carbon::parse($lastLogin);
                                    return 'Last Login: ' . $lastLogin->format('d M Y, h:i A') . ' (' . $lastLogin->diffForHumans() . ')';
                                })
                                ->color('gray')
                                ->grow(false),

                            Tables\Columns\TextColumn::make('profile_notes_count')
                                ->label('Notes')
                                ->formatStateUsing(fn ($state) => ($state ?? 0) . ' notes')
                                ->badge()
                                ->color(fn ($state) => ($state ?? 0) > 0 ? 'warning' : 'gray')
                                ->grow(false),

                            Tables\Columns\TextColumn::make('id_verified_display')
                                ->label('ID Verified')
                                ->getStateUsing(fn (Profile $record): string => $record->id_proof_verified ? 'ID Verified' : 'ID Not Verified')
                                ->badge()
                                ->icon(fn (string $state): string => $state === 'ID Verified' ? 'heroicon-o-shield-check' : 'heroicon-o-shield-exclamation')
                                ->color(fn (string $state): string => $state === 'ID Verified' ? 'success' : 'gray')
                                ->grow(false),
                        ]),
                    ])->space(2),
                ])->from('md'),
            ])
            ->contentGrid([
                'default' => 1,
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Branch (visible only to Super Admin / HO Manager)
                \App\Filament\Tables\BranchTableComponents::filter(),

                // Gender
                Tables\Filters\SelectFilter::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ]),

                // Religion
                Tables\Filters\SelectFilter::make('religion')
                    ->options(fn () => \App\Models\ReligiousInfo::whereNotNull('religion')
                        ->distinct()
                        ->orderBy('religion')
                        ->pluck('religion', 'religion')
                        ->toArray()
                    )
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) return;
                        $query->whereHas('religiousInfo', fn ($q) => $q->where('religion', $data['value']));
                    }),

                // Membership Plan
                Tables\Filters\SelectFilter::make('membership_plan')
                    ->label('Membership Plan')
                    ->options(function () {
                        $plans = MembershipPlan::where('is_active', true)
                            ->orderBy('sort_order')
                            ->pluck('plan_name', 'id')
                            ->toArray();
                        return ['free' => 'Free (No Plan)'] + $plans;
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) return;
                        if ($data['value'] === 'free') {
                            $query->whereDoesntHave('user.userMemberships', function ($q) {
                                $q->where('is_active', true)
                                    ->where(fn ($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>', now()));
                            });
                        } else {
                            $query->whereHas('user.userMemberships', function ($q) use ($data) {
                                $q->where('plan_id', $data['value'])
                                    ->where('is_active', true)
                                    ->where(fn ($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>', now()));
                            });
                        }
                    }),

                // Profile Completion Range
                Tables\Filters\SelectFilter::make('completion_range')
                    ->label('Profile Completion')
                    ->options([
                        '0-25' => '0% - 25%',
                        '25-50' => '25% - 50%',
                        '50-75' => '50% - 75%',
                        '75-100' => '75% - 100%',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) return;
                        [$min, $max] = explode('-', $data['value']);
                        $query->whereBetween('profile_completion_pct', [(int) $min, (int) $max]);
                    }),

                // Marital Status
                Tables\Filters\SelectFilter::make('marital_status')
                    ->options([
                        'Unmarried' => 'Unmarried',
                        'Divorced' => 'Divorced',
                        'Widow/Widower' => 'Widow/Widower',
                        'Awaiting Divorce' => 'Awaiting Divorce',
                        'Annulled' => 'Annulled',
                    ]),

                // Active/Inactive
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),

                // Approved
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approved')
                    ->trueLabel('Approved')
                    ->falseLabel('Pending Approval'),

                // ID Verified
                Tables\Filters\TernaryFilter::make('id_proof_verified')
                    ->label('ID Verified'),

                // Has Photo
                Tables\Filters\Filter::make('has_photo')
                    ->label('Has Photo')
                    ->query(fn (Builder $query) => $query->whereHas('primaryPhoto'))
                    ->toggle(),

                // Hidden
                Tables\Filters\TernaryFilter::make('is_hidden')
                    ->label('Hidden'),

                // Created By
                Tables\Filters\SelectFilter::make('created_by')
                    ->label('Created By')
                    ->options([
                        'self' => 'Self',
                        'parent' => 'Parent',
                        'sibling' => 'Sibling',
                        'relative' => 'Relative',
                        'friend' => 'Friend',
                    ]),

                // Registration Date Range
                Tables\Filters\Filter::make('registered_between')
                    ->form([
                        Forms\Components\DatePicker::make('registered_from')->label('Registered From'),
                        Forms\Components\DatePicker::make('registered_until')->label('Registered Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['registered_from'], fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['registered_until'], fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['registered_from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('From ' . Carbon::parse($data['registered_from'])->format('d M Y'));
                        }
                        if ($data['registered_until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Until ' . Carbon::parse($data['registered_until'])->format('d M Y'));
                        }
                        return $indicators;
                    }),

                // Last Login Date Range
                Tables\Filters\Filter::make('last_login_between')
                    ->form([
                        Forms\Components\DatePicker::make('login_from')->label('Last Login From'),
                        Forms\Components\DatePicker::make('login_until')->label('Last Login Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['login_from'], fn (Builder $q, $date) => $q->whereHas('user', fn ($u) => $u->whereDate('last_login_at', '>=', $date)))
                            ->when($data['login_until'], fn (Builder $q, $date) => $q->whereHas('user', fn ($u) => $u->whereDate('last_login_at', '<=', $date)));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['login_from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Login from ' . Carbon::parse($data['login_from'])->format('d M Y'));
                        }
                        if ($data['login_until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Login until ' . Carbon::parse($data['login_until'])->format('d M Y'));
                        }
                        return $indicators;
                    }),

                // Location — Native State
                Tables\Filters\SelectFilter::make('native_state')
                    ->label('Native State')
                    ->options(fn () => \App\Models\LocationInfo::whereNotNull('native_state')
                        ->distinct()
                        ->orderBy('native_state')
                        ->pluck('native_state', 'native_state')
                        ->toArray()
                    )
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) return;
                        $query->whereHas('locationInfo', fn ($q) => $q->where('native_state', $data['value']));
                    })
                    ->searchable(),
            ])
            ->filtersFormColumns(2)
            ->actions([
                \Filament\Actions\ViewAction::make()
                    ->label('View')
                    ->button()
                    ->color('info')
                    ->size('sm'),

                \Filament\Actions\EditAction::make()
                    ->label('Edit')
                    ->button()
                    ->size('sm'),

                // WhatsApp link
                \Filament\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->button()
                    ->size('sm')
                    ->url(function (Profile $record): ?string {
                        $phone = $record->user?->phone;
                        if (!$phone) return null;
                        $phone = preg_replace('/[^0-9]/', '', $phone);
                        if (strlen($phone) === 10) $phone = '91' . $phone;
                        return 'https://wa.me/' . $phone;
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (Profile $record): bool => (bool) $record->user?->phone),

                // Quick Approve
                \Filament\Actions\Action::make('quickApprove')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->button()
                    ->size('sm')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Profile')
                    ->modalDescription(fn (Profile $record) => "Approve {$record->full_name} ({$record->matri_id})?")
                    ->action(function (Profile $record) {
                        $record->update(['is_approved' => true]);
                        self::logActivity('profile_approved', $record);
                    })
                    ->visible(fn (Profile $record): bool => !$record->is_approved && \App\Support\Permissions::can('approve_member'))
                    ->successNotificationTitle('Profile approved'),

                // Add Note
                \Filament\Actions\Action::make('addNote')
                    ->label('Add Note')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->button()
                    ->size('sm')
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Note')
                            ->required()
                            ->rows(3)
                            ->placeholder('Enter note about this profile...'),
                        Forms\Components\DatePicker::make('follow_up_date')
                            ->label('Follow-up Date')
                            ->placeholder('Optional — set a reminder date')
                            ->minDate(today()),
                    ])
                    ->action(function (Profile $record, array $data): void {
                        ProfileNote::create([
                            'profile_id' => $record->id,
                            'admin_user_id' => auth()->id(),
                            'note' => $data['note'],
                            'follow_up_date' => $data['follow_up_date'] ?? null,
                        ]);
                    })
                    ->successNotificationTitle('Note added'),

                // Toggle Active
                \Filament\Actions\Action::make('toggleActive')
                    ->label(fn(Profile $record): string => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn(Profile $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn(Profile $record): string => $record->is_active ? 'danger' : 'success')
                    ->button()
                    ->size('sm')
                    ->visible(fn (): bool => \App\Support\Permissions::can('toggle_active'))
                    ->requiresConfirmation()
                    ->action(function (Profile $record) {
                        $wasActive = $record->is_active;
                        $record->update(['is_active' => !$wasActive]);
                        self::logActivity($wasActive ? 'profile_deactivated' : 'profile_activated', $record);
                    }),

                // Suspend
                \Filament\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->button()
                    ->size('sm')
                    ->visible(fn (Profile $record): bool => ! $record->trashed() && ($record->suspension_status ?? 'active') === 'active' && \App\Support\Permissions::can('suspend_member'))
                    ->form([
                        Forms\Components\Textarea::make('suspension_reason')
                            ->label('Reason for Suspension')
                            ->required()
                            ->rows(2),
                        Forms\Components\DatePicker::make('suspension_ends_at')
                            ->label('Suspend Until (leave empty for indefinite)')
                            ->minDate(today()),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Suspend User')
                    ->action(function (Profile $record, array $data): void {
                        $record->update([
                            'suspension_status' => 'suspended',
                            'suspension_reason' => $data['suspension_reason'],
                            'suspended_at' => now(),
                            'suspension_ends_at' => $data['suspension_ends_at'] ?? null,
                            'suspended_by' => auth()->id(),
                            'is_active' => false,
                        ]);
                        self::logActivity('profile_suspended', $record, ['reason' => $data['suspension_reason']]);
                    })
                    ->successNotificationTitle('User suspended'),

                // Ban
                \Filament\Actions\Action::make('ban')
                    ->label('Ban')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->button()
                    ->size('sm')
                    ->visible(fn (Profile $record): bool => ! $record->trashed() && ($record->suspension_status ?? 'active') !== 'banned' && \App\Support\Permissions::can('ban_member'))
                    ->form([
                        Forms\Components\Textarea::make('suspension_reason')
                            ->label('Reason for Ban')
                            ->required()
                            ->rows(2),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Permanently Ban User')
                    ->modalDescription('This will permanently ban the user. They will not be able to log in.')
                    ->action(function (Profile $record, array $data): void {
                        $record->update([
                            'suspension_status' => 'banned',
                            'suspension_reason' => $data['suspension_reason'],
                            'suspended_at' => now(),
                            'suspension_ends_at' => null,
                            'suspended_by' => auth()->id(),
                            'is_active' => false,
                        ]);
                        self::logActivity('profile_banned', $record, ['reason' => $data['suspension_reason']]);
                    })
                    ->successNotificationTitle('User banned permanently'),

                // Unsuspend / Unban
                \Filament\Actions\Action::make('unsuspend')
                    ->label(fn (Profile $record): string => ($record->suspension_status ?? 'active') === 'banned' ? 'Unban' : 'Unsuspend')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->button()
                    ->size('sm')
                    ->visible(fn (Profile $record): bool => in_array($record->suspension_status ?? 'active', ['suspended', 'banned']) && \App\Support\Permissions::can('suspend_member'))
                    ->requiresConfirmation()
                    ->action(function (Profile $record): void {
                        $previousStatus = $record->suspension_status;
                        $record->update([
                            'suspension_status' => 'active',
                            'suspension_reason' => null,
                            'suspended_at' => null,
                            'suspension_ends_at' => null,
                            'suspended_by' => null,
                            'is_active' => true,
                        ]);
                        self::logActivity($previousStatus === 'banned' ? 'profile_unbanned' : 'profile_unsuspended', $record);
                    })
                    ->successNotificationTitle('User restored to active'),

                // Toggle VIP
                \Filament\Actions\Action::make('toggleVip')
                    ->label(fn(Profile $record): string => $record->is_vip ? 'Remove VIP' : 'Mark VIP')
                    ->icon('heroicon-o-star')
                    ->color(fn(Profile $record): string => $record->is_vip ? 'gray' : 'warning')
                    ->button()
                    ->size('sm')
                    ->visible(fn (): bool => \App\Support\Permissions::can('mark_vip'))
                    ->requiresConfirmation()
                    ->modalHeading(fn(Profile $record): string => $record->is_vip ? 'Remove VIP Status' : 'Mark as VIP')
                    ->modalDescription(fn(Profile $record): string => $record->is_vip
                        ? "Remove VIP status from {$record->full_name}?"
                        : "Mark {$record->full_name} as VIP? They will appear first in search results with a gold badge.")
                    ->action(function (Profile $record) {
                        $wasVip = $record->is_vip;
                        $record->update(['is_vip' => !$wasVip]);
                        self::logActivity($wasVip ? 'profile_vip_removed' : 'profile_marked_vip', $record);
                    })
                    ->successNotificationTitle(fn(Profile $record): string => $record->is_vip ? 'Marked as VIP' : 'VIP status removed'),

                // Toggle Featured
                \Filament\Actions\Action::make('toggleFeatured')
                    ->label(fn(Profile $record): string => $record->is_featured ? 'Unfeature' : 'Feature')
                    ->icon('heroicon-o-sparkles')
                    ->color(fn(Profile $record): string => $record->is_featured ? 'gray' : 'info')
                    ->button()
                    ->size('sm')
                    ->visible(fn (): bool => \App\Support\Permissions::can('feature_profile'))
                    ->requiresConfirmation()
                    ->modalHeading(fn(Profile $record): string => $record->is_featured ? 'Unfeature Profile' : 'Feature Profile')
                    ->modalDescription(fn(Profile $record): string => $record->is_featured
                        ? "Remove featured status from {$record->full_name}?"
                        : "Feature {$record->full_name}? They will appear on the homepage and boosted in search results.")
                    ->action(function (Profile $record) {
                        $wasFeatured = $record->is_featured;
                        $record->update(['is_featured' => !$wasFeatured]);
                        self::logActivity($wasFeatured ? 'profile_unfeatured' : 'profile_featured', $record);
                    })
                    ->successNotificationTitle(fn(Profile $record): string => $record->is_featured ? 'Profile featured' : 'Featured status removed'),

                // Restore (for soft-deleted records)
                \Filament\Actions\Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->button()
                    ->size('sm')
                    ->requiresConfirmation()
                    ->modalHeading('Restore Profile')
                    ->modalDescription(fn (Profile $record) => "Restore {$record->full_name} ({$record->matri_id})? The profile will be reactivated.")
                    ->action(function (Profile $record): void {
                        $record->restore();
                        $record->update(['is_active' => true]);
                    })
                    ->visible(fn (Profile $record): bool => $record->trashed())
                    ->successNotificationTitle('Profile restored'),

                // Permanent delete (for soft-deleted records)
                \Filament\Actions\Action::make('forceDelete')
                    ->label('Delete Forever')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->button()
                    ->size('sm')
                    ->requiresConfirmation()
                    ->modalHeading('Permanently Delete Profile')
                    ->modalDescription(fn (Profile $record) => "Permanently delete {$record->full_name} ({$record->matri_id})? This will delete all their data, photos, interests, and cannot be undone.")
                    ->action(function (Profile $record): void {
                        // Delete photos from disk
                        foreach ($record->profilePhotos as $photo) {
                            if ($photo->photo_url && \Illuminate\Support\Facades\Storage::disk('public')->exists($photo->photo_url)) {
                                \Illuminate\Support\Facades\Storage::disk('public')->delete($photo->photo_url);
                            }
                        }
                        $record->forceDelete();
                    })
                    ->visible(fn (Profile $record): bool => $record->trashed())
                    ->successNotificationTitle('Profile permanently deleted'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkAction::make('approveSelected')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['is_approved' => true]))
                    ->deselectRecordsAfterCompletion(),

                \Filament\Actions\BulkAction::make('activate')
                    ->label('Activate Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn($records) => $records->each->update(['is_active' => true]))
                    ->deselectRecordsAfterCompletion(),

                \Filament\Actions\BulkAction::make('deactivate')
                    ->label('Deactivate Selected')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn($records) => $records->each->update(['is_active' => false]))
                    ->deselectRecordsAfterCompletion(),

                \Filament\Actions\ExportBulkAction::make(),
            ])
            ->searchPlaceholder('Search by name, matri ID, email, phone...')
            ->poll('60s');
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                // ── Header: Key Info ──
                Section::make('')
                    ->columns(4)
                    ->schema([
                        Infolists\Components\ImageEntry::make('primaryPhoto.photo_url')
                            ->label('')
                            ->disk('public')
                            ->circular()
                            ->size(80)
                            ->defaultImageUrl(url('/images/default-avatar.svg')),
                        Infolists\Components\TextEntry::make('matri_id')->label('Matri ID')->weight('bold')->color('primary')->copyable(),
                        Infolists\Components\TextEntry::make('full_name')->label('Full Name')->weight('bold'),
                        Infolists\Components\TextEntry::make('gender')->badge()->color(fn(string $state): string => $state === 'male' ? 'info' : 'danger'),
                        Infolists\Components\TextEntry::make('profile_completion_pct')->label('Profile')->suffix('%')
                            ->color(fn ($state): string => match (true) {
                                ($state ?? 0) >= 80 => 'success', ($state ?? 0) >= 50 => 'warning', default => 'danger',
                            }),
                        Infolists\Components\TextEntry::make('is_approved')->label('Approved')->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Approved' : 'Pending')
                            ->color(fn ($state) => $state ? 'success' : 'warning'),
                        Infolists\Components\TextEntry::make('is_active')->label('Active')->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                            ->color(fn ($state) => $state ? 'success' : 'danger'),
                        Infolists\Components\TextEntry::make('id_proof_verified')->label('ID')->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Verified' : 'Not Verified')
                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                        Infolists\Components\TextEntry::make('plan_display')->label('Plan')
                            ->getStateUsing(function (Profile $record): string {
                                $m = $record->user?->activeMembership();
                                if (!$m) return 'Free';
                                return ($m->plan?->plan_name ?? 'Unknown') . ($m->ends_at ? ' (exp ' . $m->ends_at->format('d M Y') . ')' : '');
                            })->badge()
                            ->color(fn (string $state): string => str_contains($state, 'Diamond Plus') ? 'success' : (str_contains($state, 'Diamond') ? 'info' : (str_contains($state, 'Gold') ? 'warning' : (str_contains($state, 'Silver') ? 'primary' : 'gray')))),
                    ]),

                // ── Tabs ──
                \Filament\Schemas\Components\Tabs::make('Profile Details')
                    ->columnSpanFull()
                    ->tabs([
                        \Filament\Schemas\Components\Tabs\Tab::make('Personal')
                            ->icon('heroicon-o-user')
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(3)->schema([
                                    Infolists\Components\TextEntry::make('date_of_birth')->label('Date of Birth')->date('d M Y'),
                                    Infolists\Components\TextEntry::make('age')->label('Age')->suffix(' years'),
                                    Infolists\Components\TextEntry::make('marital_status')->label('Marital Status')->default('-'),
                                    Infolists\Components\TextEntry::make('mother_tongue')->label('Mother Tongue')->default('-'),
                                    Infolists\Components\TextEntry::make('height')->label('Height')->default('-'),
                                    Infolists\Components\TextEntry::make('weight_kg')->label('Weight')->default('-'),
                                    Infolists\Components\TextEntry::make('complexion')->default('-'),
                                    Infolists\Components\TextEntry::make('body_type')->label('Body Type')->default('-'),
                                    Infolists\Components\TextEntry::make('blood_group')->label('Blood Group')->default('-'),
                                    Infolists\Components\TextEntry::make('physical_status')->label('Physical Status')->default('-'),
                                    Infolists\Components\TextEntry::make('created_by')->label('Created By')->formatStateUsing(fn ($state) => $state ? ucfirst($state) : '-'),
                                    Infolists\Components\TextEntry::make('how_did_you_hear_about_us')->label('How Did They Hear')->default('-'),
                                ]),
                                Infolists\Components\TextEntry::make('about_me')->label('About Me')->default('Not provided')->columnSpanFull(),
                            ]),

                        \Filament\Schemas\Components\Tabs\Tab::make('Account & Contact')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Section::make('Account')->columns(3)->schema([
                                    Infolists\Components\TextEntry::make('user.email')->label('Email'),
                                    Infolists\Components\TextEntry::make('user.phone')->label('Phone'),
                                    Infolists\Components\TextEntry::make('user.email_verified_at')->label('Email Verified')
                                        ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d M Y, h:i A') : 'Not verified')
                                        ->color(fn ($state) => $state ? 'success' : 'danger'),
                                    Infolists\Components\TextEntry::make('user.phone_verified_at')->label('Phone Verified')
                                        ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d M Y, h:i A') : 'Not verified')
                                        ->color(fn ($state) => $state ? 'success' : 'danger'),
                                    Infolists\Components\TextEntry::make('user.last_login_at')->label('Last Login')
                                        ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d M Y, h:i A') . ' (' . Carbon::parse($state)->diffForHumans() . ')' : 'Never'),
                                    Infolists\Components\TextEntry::make('created_at')->label('Registered')
                                        ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d M Y, h:i A') . ' (' . Carbon::parse($state)->diffForHumans() . ')' : '-'),
                                ]),
                                Section::make('Contact Details')->columns(3)->schema([
                                    Infolists\Components\TextEntry::make('contactInfo.whatsapp_number')->label('WhatsApp')->default('-'),
                                    Infolists\Components\TextEntry::make('contactInfo.contact_person')->label('Custodian Name')->default('-'),
                                    Infolists\Components\TextEntry::make('contactInfo.contact_relationship')->label('Custodian Relation')->default('-'),
                                    Infolists\Components\TextEntry::make('contactInfo.preferred_call_time')->label('Preferred Call Time')->default('-'),
                                    Infolists\Components\TextEntry::make('contactInfo.reference_name')->label('Reference Name')->default('-'),
                                    Infolists\Components\TextEntry::make('contactInfo.reference_mobile')->label('Reference Mobile')->default('-'),
                                    Infolists\Components\TextEntry::make('contactInfo.communication_address')->label('Communication Address')->default('-')->columnSpanFull(),
                                ]),
                            ]),

                        \Filament\Schemas\Components\Tabs\Tab::make('Religious')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(3)->schema([
                                    Infolists\Components\TextEntry::make('religiousInfo.religion')->label('Religion')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.denomination')->label('Denomination')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.diocese')->label('Diocese')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.parish_name_place')->label('Parish / Place')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.caste')->label('Caste / Community')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.sub_caste')->label('Sub-Caste')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.gotra')->label('Gotra / Gothram')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.nakshatra')->label('Nakshatra (Star)')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.rashi')->label('Rashi (Zodiac)')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.dosh')->label('Manglik / Dosh')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.muslim_sect')->label('Muslim Sect')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.muslim_community')->label('Muslim Community')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.jain_sect')->label('Jain Sect')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.time_of_birth')->label('Time of Birth')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.place_of_birth')->label('Place of Birth')->default('-'),
                                ]),
                            ]),

                        \Filament\Schemas\Components\Tabs\Tab::make('Education & Career')
                            ->icon('heroicon-o-academic-cap')
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(3)->schema([
                                    Infolists\Components\TextEntry::make('educationDetail.highest_education')->label('Highest Education')->default('-'),
                                    Infolists\Components\TextEntry::make('educationDetail.education_level')->label('Education Level')->default('-'),
                                    Infolists\Components\TextEntry::make('educationDetail.education_detail')->label('Education Details')->default('-'),
                                    Infolists\Components\TextEntry::make('educationDetail.college_name')->label('College / University')->default('-'),
                                    Infolists\Components\TextEntry::make('educationDetail.occupation')->label('Occupation')->default('-'),
                                    Infolists\Components\TextEntry::make('educationDetail.occupation_detail')->label('Occupation Details')->default('-'),
                                    Infolists\Components\TextEntry::make('educationDetail.employment_category')->label('Employment Category')->default('-'),
                                    Infolists\Components\TextEntry::make('educationDetail.employer_name')->label('Employer Name')->default('-'),
                                    Infolists\Components\TextEntry::make('educationDetail.annual_income')->label('Annual Income')->default('-'),
                                    Infolists\Components\TextEntry::make('educationDetail.working_country')->label('Working Country')->default('-'),
                                    Infolists\Components\TextEntry::make('educationDetail.working_state')->label('Working State')->default('-'),
                                    Infolists\Components\TextEntry::make('educationDetail.working_district')->label('Working District')->default('-'),
                                ]),
                            ]),

                        \Filament\Schemas\Components\Tabs\Tab::make('Family')
                            ->icon('heroicon-o-home')
                            ->schema([
                                Section::make('Parents')->columns(4)->schema([
                                    Infolists\Components\TextEntry::make('familyDetail.father_name')->label('Father Name')->default('-'),
                                    Infolists\Components\TextEntry::make('familyDetail.father_occupation')->label('Father Occupation')->default('-'),
                                    Infolists\Components\TextEntry::make('familyDetail.father_house_name')->label('Father House Name')->default('-'),
                                    Infolists\Components\TextEntry::make('familyDetail.father_native_place')->label('Father Native Place')->default('-'),
                                    Infolists\Components\TextEntry::make('familyDetail.mother_name')->label('Mother Name')->default('-'),
                                    Infolists\Components\TextEntry::make('familyDetail.mother_occupation')->label('Mother Occupation')->default('-'),
                                    Infolists\Components\TextEntry::make('familyDetail.mother_house_name')->label('Mother House Name')->default('-'),
                                    Infolists\Components\TextEntry::make('familyDetail.mother_native_place')->label('Mother Native Place')->default('-'),
                                ]),
                                Section::make('Siblings & Family')->columns(3)->schema([
                                    Infolists\Components\TextEntry::make('familyDetail.family_status')->label('Family Status')->default('-'),
                                    Infolists\Components\TextEntry::make('familyDetail.brothers_married')->label('Brothers (Married)')->default('0'),
                                    Infolists\Components\TextEntry::make('familyDetail.brothers_unmarried')->label('Brothers (Unmarried)')->default('0'),
                                    Infolists\Components\TextEntry::make('familyDetail.brothers_priest')->label('Brothers (Priest)')->default('0'),
                                    Infolists\Components\TextEntry::make('familyDetail.sisters_married')->label('Sisters (Married)')->default('0'),
                                    Infolists\Components\TextEntry::make('familyDetail.sisters_unmarried')->label('Sisters (Unmarried)')->default('0'),
                                    Infolists\Components\TextEntry::make('familyDetail.sisters_nun')->label('Sisters (Nun)')->default('0'),
                                    Infolists\Components\TextEntry::make('familyDetail.candidate_asset_details')->label('Asset Details')->default('-')->columnSpanFull(),
                                    Infolists\Components\TextEntry::make('familyDetail.about_candidate_family')->label('About Family')->default('-')->columnSpanFull(),
                                ]),
                            ]),

                        \Filament\Schemas\Components\Tabs\Tab::make('Location')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(3)->schema([
                                    Infolists\Components\TextEntry::make('locationInfo.native_country')->label('Native Country')->default('-'),
                                    Infolists\Components\TextEntry::make('locationInfo.native_state')->label('Native State')->default('-'),
                                    Infolists\Components\TextEntry::make('locationInfo.native_district')->label('Native District')->default('-'),
                                    Infolists\Components\TextEntry::make('locationInfo.residing_country')->label('Residing Country')->default('-'),
                                    Infolists\Components\TextEntry::make('locationInfo.residency_status')->label('Residency Status')->default('-'),
                                    Infolists\Components\TextEntry::make('locationInfo.pin_zip_code')->label('PIN/ZIP Code')->default('-'),
                                ]),
                            ]),

                        \Filament\Schemas\Components\Tabs\Tab::make('Lifestyle & Social')
                            ->icon('heroicon-o-heart')
                            ->schema([
                                Section::make('Lifestyle')->columns(3)->schema([
                                    Infolists\Components\TextEntry::make('lifestyleInfo.diet')->label('Diet')->default('-'),
                                    Infolists\Components\TextEntry::make('lifestyleInfo.smoking')->label('Smoking')->default('-'),
                                    Infolists\Components\TextEntry::make('lifestyleInfo.drinking')->label('Drinking')->default('-'),
                                    Infolists\Components\TextEntry::make('lifestyleInfo.cultural_background')->label('Cultural Background')->default('-'),
                                ]),
                                Section::make('Social Media')->columns(3)->schema([
                                    Infolists\Components\TextEntry::make('socialMediaLink.instagram_url')->label('Instagram')->default('-'),
                                    Infolists\Components\TextEntry::make('socialMediaLink.facebook_url')->label('Facebook')->default('-'),
                                    Infolists\Components\TextEntry::make('socialMediaLink.linkedin_url')->label('LinkedIn')->default('-'),
                                ]),
                            ]),

                        // Tab 8: Subscription & Membership
                        \Filament\Schemas\Components\Tabs\Tab::make('Subscription')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Section::make('Current Membership')->columns(4)->schema([
                                    Infolists\Components\TextEntry::make('current_plan')
                                        ->label('Plan')
                                        ->getStateUsing(function (Profile $record): string {
                                            $m = $record->user?->activeMembership();
                                            return $m?->plan?->plan_name ?? 'Free';
                                        })
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'Diamond Plus' => 'success', 'Diamond' => 'info', 'Gold' => 'warning', 'Silver' => 'primary', default => 'gray',
                                        }),
                                    Infolists\Components\TextEntry::make('membership_start')
                                        ->label('Started')
                                        ->getStateUsing(fn (Profile $record) => $record->user?->activeMembership()?->starts_at?->format('d M Y') ?? '-'),
                                    Infolists\Components\TextEntry::make('membership_end')
                                        ->label('Expires')
                                        ->getStateUsing(function (Profile $record): string {
                                            $m = $record->user?->activeMembership();
                                            if (!$m?->ends_at) return '-';
                                            $days = now()->diffInDays($m->ends_at, false);
                                            $date = $m->ends_at->format('d M Y');
                                            return $days < 0 ? $date . ' (EXPIRED)' : $date . ' (' . $days . ' days left)';
                                        })
                                        ->color(function (Profile $record): string {
                                            $m = $record->user?->activeMembership();
                                            if (!$m?->ends_at) return 'gray';
                                            $days = now()->diffInDays($m->ends_at, false);
                                            if ($days < 0) return 'danger';
                                            if ($days <= 7) return 'warning';
                                            return 'success';
                                        }),
                                    Infolists\Components\TextEntry::make('membership_status')
                                        ->label('Status')
                                        ->getStateUsing(fn (Profile $record) => $record->user?->activeMembership()?->is_active ? 'Active' : 'Inactive')
                                        ->badge()
                                        ->color(fn (string $state) => $state === 'Active' ? 'success' : 'danger'),
                                ]),
                                Section::make('Membership History')->schema([
                                    Infolists\Components\RepeatableEntry::make('user.userMemberships')
                                        ->label('')
                                        ->schema([
                                            \Filament\Schemas\Components\Grid::make(5)->schema([
                                                Infolists\Components\TextEntry::make('plan.plan_name')->label('Plan')->badge(),
                                                Infolists\Components\TextEntry::make('starts_at')->label('Start')->date('d M Y'),
                                                Infolists\Components\TextEntry::make('ends_at')->label('End')->date('d M Y'),
                                                Infolists\Components\TextEntry::make('is_active')->label('Active')
                                                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Expired')
                                                    ->badge()
                                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                                                Infolists\Components\TextEntry::make('created_at')->label('Purchased')->since(),
                                            ]),
                                        ])
                                        ->contained(false)
                                        ->placeholder('No membership history.'),
                                ]),
                                Section::make('Payment History (Razorpay)')->schema([
                                    Infolists\Components\RepeatableEntry::make('user.subscriptions')
                                        ->label('')
                                        ->schema([
                                            \Filament\Schemas\Components\Grid::make(5)->schema([
                                                Infolists\Components\TextEntry::make('plan_name')->label('Plan'),
                                                Infolists\Components\TextEntry::make('amount')->label('Amount')
                                                    ->formatStateUsing(fn ($state) => '₹' . number_format(($state ?? 0) / 100, 0)),
                                                Infolists\Components\TextEntry::make('razorpay_payment_id')->label('Payment ID')->copyable(),
                                                Infolists\Components\TextEntry::make('payment_status')->label('Status')
                                                    ->badge()
                                                    ->color(fn ($state) => $state === 'captured' || $state === 'paid' ? 'success' : ($state === 'failed' ? 'danger' : 'warning')),
                                                Infolists\Components\TextEntry::make('created_at')->label('Date')->date('d M Y, h:i A'),
                                            ]),
                                        ])
                                        ->contained(false)
                                        ->placeholder('No payment history.'),
                                ]),
                            ]),

                        // Tab 9: Activity
                        \Filament\Schemas\Components\Tabs\Tab::make('Activity')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make('Activity Summary')->columns(4)->schema([
                                    Infolists\Components\TextEntry::make('interests_sent_count')
                                        ->label('Interests Sent')
                                        ->getStateUsing(fn (Profile $record) => $record->sentInterests()->count()),
                                    Infolists\Components\TextEntry::make('interests_received_count')
                                        ->label('Interests Received')
                                        ->getStateUsing(fn (Profile $record) => $record->receivedInterests()->count()),
                                    Infolists\Components\TextEntry::make('profile_views_count')
                                        ->label('Profile Views')
                                        ->getStateUsing(fn (Profile $record) => $record->viewedByOthers()->count()),
                                    Infolists\Components\TextEntry::make('shortlisted_count')
                                        ->label('Shortlisted By')
                                        ->getStateUsing(fn (Profile $record) => \App\Models\Shortlist::where('shortlisted_profile_id', $record->id)->count()),
                                ]),
                                Section::make('Recent Interests Sent')->schema([
                                    Infolists\Components\RepeatableEntry::make('sentInterests')
                                        ->label('')
                                        ->schema([
                                            \Filament\Schemas\Components\Grid::make(4)->schema([
                                                Infolists\Components\TextEntry::make('receiverProfile.matri_id')->label('To'),
                                                Infolists\Components\TextEntry::make('receiverProfile.full_name')->label('Name'),
                                                Infolists\Components\TextEntry::make('status')->label('Status')
                                                    ->badge()
                                                    ->color(fn ($state) => match ($state) {
                                                        'accepted' => 'success', 'declined' => 'danger', 'pending' => 'warning', default => 'gray',
                                                    }),
                                                Infolists\Components\TextEntry::make('created_at')->label('Date')->since(),
                                            ]),
                                        ])
                                        ->contained(false)
                                        ->placeholder('No interests sent.'),
                                ]),
                                Section::make('Recent Interests Received')->schema([
                                    Infolists\Components\RepeatableEntry::make('receivedInterests')
                                        ->label('')
                                        ->schema([
                                            \Filament\Schemas\Components\Grid::make(4)->schema([
                                                Infolists\Components\TextEntry::make('senderProfile.matri_id')->label('From'),
                                                Infolists\Components\TextEntry::make('senderProfile.full_name')->label('Name'),
                                                Infolists\Components\TextEntry::make('status')->label('Status')
                                                    ->badge()
                                                    ->color(fn ($state) => match ($state) {
                                                        'accepted' => 'success', 'declined' => 'danger', 'pending' => 'warning', default => 'gray',
                                                    }),
                                                Infolists\Components\TextEntry::make('created_at')->label('Date')->since(),
                                            ]),
                                        ])
                                        ->contained(false)
                                        ->placeholder('No interests received.'),
                                ]),
                                Section::make('Recent Profile Views')->schema([
                                    Infolists\Components\RepeatableEntry::make('viewedByOthers')
                                        ->label('')
                                        ->schema([
                                            \Filament\Schemas\Components\Grid::make(3)->schema([
                                                Infolists\Components\TextEntry::make('viewerProfile.matri_id')->label('Viewed By'),
                                                Infolists\Components\TextEntry::make('viewerProfile.full_name')->label('Name'),
                                                Infolists\Components\TextEntry::make('created_at')->label('Viewed At')->since(),
                                            ]),
                                        ])
                                        ->contained(false)
                                        ->placeholder('No profile views yet.'),
                                ]),
                            ]),

                        // Tab 10: Admin Notes
                        \Filament\Schemas\Components\Tabs\Tab::make('Admin Notes')
                            ->icon('heroicon-o-pencil-square')
                            ->badge(fn (Profile $record): ?string => $record->profileNotes->count() > 0 ? (string) $record->profileNotes->count() : null)
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('profileNotes')
                                    ->label('')
                                    ->schema([
                                        \Filament\Schemas\Components\Grid::make(4)->schema([
                                            Infolists\Components\TextEntry::make('note')->label('Note')->columnSpan(2),
                                            Infolists\Components\TextEntry::make('adminUser.name')->label('Added By'),
                                            Infolists\Components\TextEntry::make('follow_up_date')->label('Follow-up')
                                                ->date('d M Y')
                                                ->color(fn ($state) => $state && Carbon::parse($state)->isPast() ? 'danger' : 'warning')
                                                ->default('-'),
                                        ]),
                                    ])
                                    ->contained(false)
                                    ->placeholder('No admin notes yet. Use "Add Note" action from the list page.'),
                            ]),

                        // Tab 11: Login History
                        \Filament\Schemas\Components\Tabs\Tab::make('Login History')
                            ->icon('heroicon-o-clock')
                            ->badge(fn (Profile $record): ?string => $record->user?->loginHistory->count() > 0 ? (string) $record->user->loginHistory->count() : null)
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('user.loginHistory')
                                    ->label('')
                                    ->schema([
                                        \Filament\Schemas\Components\Grid::make(5)->schema([
                                            Infolists\Components\TextEntry::make('logged_in_at')
                                                ->label('When')
                                                ->since()
                                                ->tooltip(fn ($record) => $record->logged_in_at?->format('M j, Y g:i:s A')),
                                            Infolists\Components\TextEntry::make('login_method')
                                                ->label('Method')
                                                ->badge()
                                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                                    'password' => 'Password',
                                                    'mobile_otp' => 'Mobile OTP',
                                                    'email_otp' => 'Email OTP',
                                                    default => $state,
                                                })
                                                ->color(fn (string $state): string => match ($state) {
                                                    'password' => 'info',
                                                    'mobile_otp' => 'success',
                                                    'email_otp' => 'warning',
                                                    default => 'gray',
                                                }),
                                            Infolists\Components\TextEntry::make('ip_address')
                                                ->label('IP')
                                                ->copyable()
                                                ->color('gray'),
                                            Infolists\Components\TextEntry::make('device_type')
                                                ->label('Device')
                                                ->badge()
                                                ->color(fn (string $state): string => match ($state) {
                                                    'Mobile' => 'success',
                                                    'Tablet' => 'warning',
                                                    'Desktop' => 'info',
                                                    default => 'gray',
                                                }),
                                            Infolists\Components\TextEntry::make('device_label')
                                                ->label('Browser / OS')
                                                ->color('gray'),
                                        ]),
                                    ])
                                    ->contained(false)
                                    ->placeholder('No login history yet. Will populate after the user logs in.'),
                            ]),
                    ]),

            ]);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                // ── Section 1: Personal Information ──
                Section::make('Personal Information')
                    ->icon('heroicon-o-user')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('matri_id')->label('Matri ID')->disabled()->dehydrated(false)
                            ->hidden(fn (string $operation): bool => $operation === 'create'),
                        Forms\Components\TextInput::make('full_name')->label('Full Name')->required()->maxLength(100),
                        Forms\Components\Select::make('gender')->options(['male' => 'Male', 'female' => 'Female'])->required(),
                        Forms\Components\DatePicker::make('date_of_birth')->label('Date of Birth')->required()->maxDate(now()->subYears(18)),
                        Forms\Components\Select::make('marital_status')->label('Marital Status')->options([
                            'Unmarried' => 'Unmarried', 'Divorced' => 'Divorced',
                            'Widow/Widower' => 'Widow/Widower', 'Awaiting Divorce' => 'Awaiting Divorce', 'Annulled' => 'Annulled',
                        ]),
                        Forms\Components\TextInput::make('mother_tongue')->label('Mother Tongue')->maxLength(50),
                        Forms\Components\TextInput::make('height')->maxLength(50),
                        Forms\Components\TextInput::make('weight_kg')->label('Weight (kg)')->maxLength(20),
                        Forms\Components\TextInput::make('complexion')->maxLength(30),
                        Forms\Components\TextInput::make('body_type')->label('Body Type')->maxLength(30),
                        Forms\Components\TextInput::make('blood_group')->label('Blood Group')->maxLength(10),
                        Forms\Components\TextInput::make('physical_status')->label('Physical Status')->maxLength(50),
                        Forms\Components\Textarea::make('about_me')->label('About Me')->rows(3)->columnSpanFull(),
                    ]),

                // ── Section 2: Account & Contact ──
                Section::make('Account & Contact')
                    ->icon('heroicon-o-phone')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('user_email')->label('Email')->email()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->unique(table: 'users', column: 'email', ignoreRecord: true),
                        Forms\Components\TextInput::make('user_phone')->label('Phone')->tel()
                            ->unique(table: 'users', column: 'phone', ignoreRecord: true),
                        Forms\Components\TextInput::make('cont_whatsapp')->label('WhatsApp')->maxLength(15),
                        Forms\Components\TextInput::make('cont_custodian_name')->label('Custodian Name')->maxLength(100),
                        Forms\Components\TextInput::make('cont_custodian_relation')->label('Custodian Relation')->maxLength(100),
                        Forms\Components\TextInput::make('cont_preferred_call_time')->label('Preferred Call Time')->maxLength(50),
                        Forms\Components\Textarea::make('cont_communication_address')->label('Communication Address')->rows(2)->maxLength(200)->columnSpanFull(),
                        Forms\Components\TextInput::make('cont_pin_zip_code')->label('PIN/ZIP Code')->maxLength(10),
                        Forms\Components\TextInput::make('cont_reference_name')->label('Reference Name')->maxLength(100),
                        Forms\Components\TextInput::make('cont_reference_mobile')->label('Reference Mobile')->maxLength(15),
                    ]),

                // ── Section 3: Religious Information ──
                Section::make('Religious Information')
                    ->icon('heroicon-o-globe-alt')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('rel_religion')->label('Religion')->maxLength(50),
                        Forms\Components\TextInput::make('rel_denomination')->label('Denomination')->maxLength(100),
                        Forms\Components\TextInput::make('rel_diocese')->label('Diocese')->maxLength(100),
                        Forms\Components\TextInput::make('rel_diocese_name')->label('Diocese Name (Other)')->maxLength(100),
                        Forms\Components\TextInput::make('rel_parish')->label('Parish Name / Place')->maxLength(200),
                        Forms\Components\TextInput::make('rel_caste')->label('Caste / Community')->maxLength(100),
                        Forms\Components\TextInput::make('rel_sub_caste')->label('Sub-Caste')->maxLength(100),
                        Forms\Components\TextInput::make('rel_gotra')->label('Gotra / Gothram')->maxLength(100),
                        Forms\Components\TextInput::make('rel_nakshatra')->label('Nakshatra (Star)')->maxLength(50),
                        Forms\Components\TextInput::make('rel_rashi')->label('Rashi (Zodiac)')->maxLength(50),
                        Forms\Components\TextInput::make('rel_manglik')->label('Manglik / Dosh')->maxLength(50),
                        Forms\Components\TextInput::make('rel_muslim_sect')->label('Muslim Sect')->maxLength(50),
                        Forms\Components\TextInput::make('rel_muslim_community')->label('Muslim Community')->maxLength(100),
                        Forms\Components\TextInput::make('rel_jain_sect')->label('Jain Sect')->maxLength(50),
                        Forms\Components\TextInput::make('rel_time_of_birth')->label('Time of Birth')->maxLength(20),
                        Forms\Components\TextInput::make('rel_place_of_birth')->label('Place of Birth')->maxLength(100),
                    ]),

                // ── Section 4: Education & Career ──
                Section::make('Education & Career')
                    ->icon('heroicon-o-academic-cap')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('edu_highest_education')->label('Highest Education')->maxLength(100),
                        Forms\Components\TextInput::make('edu_education_level')->label('Education Level')->maxLength(50),
                        Forms\Components\TextInput::make('edu_education_detail')->label('Education Details')->maxLength(200),
                        Forms\Components\TextInput::make('edu_college_name')->label('College / University')->maxLength(200),
                        Forms\Components\TextInput::make('edu_occupation')->label('Occupation')->maxLength(100),
                        Forms\Components\TextInput::make('edu_occupation_detail')->label('Occupation Details')->maxLength(200),
                        Forms\Components\TextInput::make('edu_employment_category')->label('Employment Category')->maxLength(100),
                        Forms\Components\TextInput::make('edu_employer_name')->label('Employer Name')->maxLength(200),
                        Forms\Components\TextInput::make('edu_annual_income')->label('Annual Income')->maxLength(50),
                        Forms\Components\TextInput::make('edu_working_country')->label('Working Country')->maxLength(100),
                        Forms\Components\TextInput::make('edu_working_state')->label('Working State')->maxLength(100),
                        Forms\Components\TextInput::make('edu_working_district')->label('Working District')->maxLength(100),
                    ]),

                // ── Section 5: Family Details ──
                Section::make('Family Details')
                    ->icon('heroicon-o-home')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('fam_father_name')->label('Father Name')->maxLength(100),
                        Forms\Components\TextInput::make('fam_father_occupation')->label('Father Occupation')->maxLength(100),
                        Forms\Components\TextInput::make('fam_father_house_name')->label('Father House Name')->maxLength(100),
                        Forms\Components\TextInput::make('fam_father_native_place')->label('Father Native Place')->maxLength(100),
                        Forms\Components\TextInput::make('fam_mother_name')->label('Mother Name')->maxLength(100),
                        Forms\Components\TextInput::make('fam_mother_occupation')->label('Mother Occupation')->maxLength(100),
                        Forms\Components\TextInput::make('fam_mother_house_name')->label('Mother House Name')->maxLength(100),
                        Forms\Components\TextInput::make('fam_mother_native_place')->label('Mother Native Place')->maxLength(100),
                        Forms\Components\TextInput::make('fam_family_status')->label('Family Status')->maxLength(50),
                        Forms\Components\TextInput::make('fam_brothers_married')->label('Brothers (Married)')->numeric()->minValue(0),
                        Forms\Components\TextInput::make('fam_brothers_unmarried')->label('Brothers (Unmarried)')->numeric()->minValue(0),
                        Forms\Components\TextInput::make('fam_brothers_priest')->label('Brothers (Priest)')->numeric()->minValue(0),
                        Forms\Components\TextInput::make('fam_sisters_married')->label('Sisters (Married)')->numeric()->minValue(0),
                        Forms\Components\TextInput::make('fam_sisters_unmarried')->label('Sisters (Unmarried)')->numeric()->minValue(0),
                        Forms\Components\TextInput::make('fam_sisters_nun')->label('Sisters (Nun)')->numeric()->minValue(0),
                        Forms\Components\Textarea::make('fam_candidate_asset_details')->label('Candidate Asset Details')->rows(2)->maxLength(500)->columnSpanFull(),
                        Forms\Components\Textarea::make('fam_about_family')->label('About Family')->rows(2)->maxLength(5000)->columnSpanFull(),
                    ]),

                // ── Section 6: Location ──
                Section::make('Location')
                    ->icon('heroicon-o-map-pin')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('loc_native_country')->label('Native Country')->maxLength(100),
                        Forms\Components\TextInput::make('loc_native_state')->label('Native State')->maxLength(100),
                        Forms\Components\TextInput::make('loc_native_district')->label('Native District')->maxLength(100),
                        Forms\Components\TextInput::make('loc_residing_country')->label('Residing Country')->maxLength(100),
                        Forms\Components\TextInput::make('loc_residency_status')->label('Residency Status')->maxLength(50),
                        Forms\Components\TextInput::make('loc_pin_zip_code')->label('PIN/ZIP Code')->maxLength(10),
                    ]),

                // ── Section 7: Lifestyle ──
                Section::make('Lifestyle')
                    ->icon('heroicon-o-heart')
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('life_diet')->label('Diet')->maxLength(50),
                        Forms\Components\TextInput::make('life_smoking')->label('Smoking')->maxLength(50),
                        Forms\Components\TextInput::make('life_drinking')->label('Drinking')->maxLength(50),
                        Forms\Components\TextInput::make('life_cultural_background')->label('Cultural Background')->maxLength(100),
                    ]),

                // ── Section 8: Social Media ──
                Section::make('Social Media')
                    ->icon('heroicon-o-link')
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('social_instagram')->label('Instagram URL')->url()->maxLength(300),
                        Forms\Components\TextInput::make('social_facebook')->label('Facebook URL')->url()->maxLength(300),
                        Forms\Components\TextInput::make('social_linkedin')->label('LinkedIn URL')->url()->maxLength(300),
                    ]),

                // ── Section 9: Status & Admin Controls ──
                Section::make('Status & Admin Controls')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Toggle::make('is_active')->label('Active'),
                        Forms\Components\Toggle::make('is_approved')->label('Approved'),
                        Forms\Components\Toggle::make('id_proof_verified')->label('ID Proof Verified'),
                        Forms\Components\Toggle::make('is_hidden')->label('Hidden'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UserResource\RelationManagers\PhotosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Branch scoping: Branch Manager / Branch Staff see only profiles in their branch.
        // Note: this resource manages Profile (member-facing), not the User model directly.
        return parent::getEloquentQuery()
            ->whereNotNull('full_name')
            ->whereHas('user', fn ($q) => $q->whereNull('staff_role_id'))
            ->with(['user', 'religiousInfo', 'educationDetail', 'locationInfo', 'primaryPhoto'])
            ->withCount('profileNotes')
            ->forUserBranch();
    }
}
