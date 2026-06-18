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
use Filament\Schemas\Components\Utilities\Get;
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
                                ->searchable(['full_name', 'matri_id'])
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
                                ->label('Date of Birth')
                                ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d M Y') . ' · ' . Carbon::parse($state)->age . ' yrs' : '-')
                                ->icon('heroicon-o-cake')
                                ->grow(false),

                            Tables\Columns\TextColumn::make('user.phone')
                                ->label('Phone')
                                ->searchable()
                                ->icon('heroicon-o-phone')
                                ->copyable()
                                ->copyMessage('Phone number copied')
                                ->grow(false),

                            Tables\Columns\TextColumn::make('user.email')
                                ->label('Email')
                                ->searchable()
                                ->icon('heroicon-o-envelope')
                                ->limit(25)
                                ->copyable()
                                ->copyMessage('Email copied')
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
                                    // display_denomination / display_caste resolve "Other" to the
                                    // typed specify value, so the list row shows e.g.
                                    // "Christian / Coptic Catholic" instead of "Christian / Other".
                                    $denom = $record->religiousInfo?->display_denomination;
                                    $caste = $record->religiousInfo?->display_caste;
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

                            // Which device/channel the member registered from.
                            // Only set on sign-ups after this feature shipped —
                            // older profiles show "—".
                            Tables\Columns\TextColumn::make('registration_source')
                                ->label('Source')
                                ->badge()
                                ->icon(fn (?string $state): ?string => match ($state) {
                                    'Desktop' => 'heroicon-o-computer-desktop',
                                    'Mobile' => 'heroicon-o-device-phone-mobile',
                                    'Tablet' => 'heroicon-o-device-tablet',
                                    'App' => 'heroicon-o-device-phone-mobile',
                                    'Admin' => 'heroicon-o-user',
                                    default => null,
                                })
                                ->color(fn (?string $state): string => match ($state) {
                                    'Desktop' => 'info',
                                    'Mobile' => 'success',
                                    'Tablet' => 'warning',
                                    'App' => 'primary',
                                    'Admin' => 'gray',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn (?string $state): string => match ($state) {
                                    'Desktop' => 'Desktop/Laptop',
                                    'Mobile' => 'Mobile Web',
                                    'Tablet' => 'Tablet',
                                    'App' => 'Mobile App',
                                    'Admin' => 'Admin',
                                    default => '—',
                                })
                                ->placeholder('—')
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
                                    // Convert UTC -> display timezone (IST). copy() so we
                                    // don't mutate the model attribute. diffForHumans is
                                    // an absolute diff so it's correct either way.
                                    $local = $date->copy()->timezone(config('app.display_timezone', 'Asia/Kolkata'));
                                    return 'Registered: ' . $local->format('d M Y, h:i A') . ' (' . $local->diffForHumans() . ')';
                                })
                                ->sortable()
                                ->color('gray')
                                ->grow(false),

                            Tables\Columns\TextColumn::make('last_login_display')
                                ->label('Last Login')
                                ->getStateUsing(function (Profile $record): string {
                                    $lastLogin = $record->user?->last_login_at;
                                    if (!$lastLogin) return 'Last Login: Never';
                                    $lastLogin = Carbon::parse($lastLogin)->timezone(config('app.display_timezone', 'Asia/Kolkata'));
                                    return 'Last Login: ' . $lastLogin->format('d M Y, h:i A') . ' (' . $lastLogin->diffForHumans() . ')';
                                })
                                ->color('gray')
                                ->grow(false),

                            // Device of the member's most recent login, derived
                            // from the latest LoginHistory row (user.latestLogin,
                            // eager-loaded). Distinct from registration_source:
                            // a member can sign up on desktop but later log in on
                            // mobile. Shows nothing when they've never logged in.
                            Tables\Columns\TextColumn::make('last_login_device')
                                ->label('Last Device')
                                ->badge()
                                ->getStateUsing(fn (Profile $record): ?string => $record->user?->latestLogin?->device_type)
                                ->icon(fn (?string $state): ?string => match ($state) {
                                    'Desktop' => 'heroicon-o-computer-desktop',
                                    'Mobile', 'App' => 'heroicon-o-device-phone-mobile',
                                    'Tablet' => 'heroicon-o-device-tablet',
                                    default => null,
                                })
                                ->color(fn (?string $state): string => match ($state) {
                                    'Desktop' => 'info',
                                    'Mobile' => 'success',
                                    'Tablet' => 'warning',
                                    'App' => 'primary',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn (?string $state): string => match ($state) {
                                    'Desktop' => 'Desktop/Laptop',
                                    'Mobile' => 'Mobile Web',
                                    'Tablet' => 'Tablet',
                                    'App' => 'Mobile App',
                                    default => '—',
                                })
                                ->placeholder('—')
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

                // Members who typed an "Other" caste / religion / denomination —
                // the queue of values to add to the managed dropdowns. The Religion
                // column already shows the typed value (display_caste / _denomination).
                Tables\Filters\Filter::make('unlisted_caste_religion')
                    ->label('Unlisted caste/religion ("Other")')
                    ->query(fn (Builder $query) => $query->whereHas('religiousInfo', function ($q) {
                        $q->where(function ($w) {
                            foreach (['other_caste_name', 'other_religion_name', 'other_denomination_name'] as $col) {
                                $w->orWhere(fn ($o) => $o->whereNotNull($col)->where($col, '!=', ''));
                            }
                        });
                    }))
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

                // Registration source / device
                Tables\Filters\SelectFilter::make('registration_source')
                    ->label('Registered Via')
                    ->options([
                        'Desktop' => 'Desktop/Laptop',
                        'Mobile' => 'Mobile Web',
                        'Tablet' => 'Tablet',
                        'App' => 'Mobile App',
                        'Admin' => 'Admin',
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

                // Assign / Change membership plan — deep-links to the Change Plan
                // page pre-loaded with this member (one-step assignment).
                \Filament\Actions\Action::make('assignPlan')
                    ->label('Assign Plan')
                    ->icon('heroicon-o-credit-card')
                    ->color('warning')
                    ->button()
                    ->size('sm')
                    ->url(fn (Profile $record): string => \App\Filament\Pages\ChangeMembershipPlan::getUrl(['matri_id' => $record->matri_id]))
                    ->visible(fn (Profile $record): bool => ! $record->trashed() && \App\Support\Permissions::can('assign_member_plan')),

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

                // Notes — interaction log. Opens the full history (who logged
                // it, when, and the interaction type) plus a form to add a new
                // note. Used after every call / WhatsApp. Append-only. Defined
                // once in ProfileNotesAction and reused on the View page header
                // so the two popups stay identical.
                \App\Filament\Actions\ProfileNotesAction::make('notes'),

                // Secondary / less-frequent actions grouped into a "More" dropdown
                // so the row stays compact. The common actions (View, Edit, Assign
                // Plan, WhatsApp) remain as direct buttons above.
                \Filament\Actions\ActionGroup::make([

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

                // Reset Password — set a new login password for the member.
                // Senior-gated (ban_member) since it enables account takeover;
                // written via query update to bypass the model's 'hashed' cast
                // (no double-hashing), and logged to the admin activity trail.
                \Filament\Actions\Action::make('resetPassword')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('danger')
                    ->button()
                    ->size('sm')
                    ->visible(fn (Profile $record): bool => ! $record->trashed() && $record->user && \App\Support\Permissions::can('ban_member'))
                    ->requiresConfirmation()
                    ->modalHeading(fn (Profile $record): string => 'Reset password — ' . $record->full_name)
                    ->modalDescription(fn (Profile $record): string => 'Set a new login password for ' . ($record->user?->email ?? $record->user?->phone ?? 'this member') . '. They can sign in with it immediately. Share it with them securely.')
                    ->form([
                        Forms\Components\TextInput::make('new_password')
                            ->label('New password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->maxLength(72)
                            ->helperText('At least 8 characters.'),
                    ])
                    ->modalSubmitActionLabel('Set password')
                    ->action(function (Profile $record, array $data): void {
                        if (! $record->user) {
                            return;
                        }
                        \App\Models\User::where('id', $record->user_id)
                            ->update(['password' => \Illuminate\Support\Facades\Hash::make($data['new_password'])]);
                        self::logActivity('member_password_reset', $record);
                    })
                    ->successNotificationTitle('Password reset'),

                // Soft delete — removes the profile from listings and moves it to
                // "Deleted Users (Can be restored)", where Restore / Delete Forever
                // take over. Reversible (not a permanent purge).
                \Filament\Actions\Action::make('softDelete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->button()
                    ->size('sm')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Profile')
                    ->modalDescription(fn (Profile $record): string => "Delete {$record->full_name} ({$record->matri_id})? They'll be removed from listings and moved to \"Deleted Users\", where you can restore them or delete permanently.")
                    ->modalSubmitActionLabel('Delete')
                    ->action(fn (Profile $record) => $record->delete())
                    ->visible(fn (Profile $record): bool => ! $record->trashed() && \App\Support\Permissions::can('toggle_active'))
                    ->successNotificationTitle('Profile deleted — restorable from "Deleted Users"'),

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
                    ->label('More')
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->button()
                    ->size('sm'),
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

                // Bulk Restore / Delete-Forever — gated to the "Deleted" tab, whose
                // query is withTrashed()->whereNotNull('deleted_at'). So these can
                // ONLY ever act on already-soft-deleted profiles, never live members
                // (fails closed: hidden on every other tab).
                \Filament\Actions\BulkAction::make('restoreSelected')
                    ->label('Restore selected')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($livewire): bool => ($livewire->activeTab ?? null) === 'deleted')
                    ->action(fn ($records) => $records->each(function (Profile $record): void {
                        $record->restore();
                        $record->update(['is_active' => true]);
                    }))
                    ->deselectRecordsAfterCompletion()
                    ->successNotificationTitle('Profiles restored'),

                \Filament\Actions\BulkAction::make('forceDeleteSelected')
                    ->label('Delete forever')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Permanently delete selected profiles')
                    ->modalDescription('This permanently deletes the selected profiles and all their data, photos and interests. This cannot be undone.')
                    ->modalSubmitActionLabel('Delete forever')
                    ->visible(fn ($livewire): bool => ($livewire->activeTab ?? null) === 'deleted')
                    ->action(fn ($records) => $records->each(function (Profile $record): void {
                        foreach ($record->profilePhotos as $photo) {
                            if ($photo->photo_url && \Illuminate\Support\Facades\Storage::disk('public')->exists($photo->photo_url)) {
                                \Illuminate\Support\Facades\Storage::disk('public')->delete($photo->photo_url);
                            }
                        }
                        $record->forceDelete();
                    }))
                    ->deselectRecordsAfterCompletion()
                    ->successNotificationTitle('Profiles permanently deleted'),

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
                                        ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->timezone(config('app.display_timezone', 'Asia/Kolkata'))->format('d M Y, h:i A') : 'Not verified')
                                        ->color(fn ($state) => $state ? 'success' : 'danger'),
                                    Infolists\Components\TextEntry::make('user.phone_verified_at')->label('Phone Verified')
                                        ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->timezone(config('app.display_timezone', 'Asia/Kolkata'))->format('d M Y, h:i A') : 'Not verified')
                                        ->color(fn ($state) => $state ? 'success' : 'danger'),
                                    Infolists\Components\TextEntry::make('user.last_login_at')->label('Last Login')
                                        ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->timezone(config('app.display_timezone', 'Asia/Kolkata'))->format('d M Y, h:i A') . ' (' . Carbon::parse($state)->diffForHumans() . ')' : 'Never'),
                                    Infolists\Components\TextEntry::make('created_at')->label('Registered')
                                        ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->timezone(config('app.display_timezone', 'Asia/Kolkata'))->format('d M Y, h:i A') . ' (' . Carbon::parse($state)->diffForHumans() . ')' : '-'),
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
                                    Infolists\Components\TextEntry::make('religiousInfo.other_denomination_name')->label('Denomination (specified)')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.diocese')->label('Diocese')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.parish_name_place')->label('Parish / Place')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.caste')->label('Caste / Community')->default('-'),
                                    Infolists\Components\TextEntry::make('religiousInfo.other_caste_name')->label('Caste (specified)')->default('-'),
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
                                    Infolists\Components\TextEntry::make('educationDetail.working_city')->label('Working City')->default('-'),
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
                                    Infolists\Components\TextEntry::make('locationInfo.native_place')->label('Native Place')->default('-'),
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

                        // Tab: Partner Preferences (read-only summary)
                        \Filament\Schemas\Components\Tabs\Tab::make('Partner Preferences')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(2)->schema([
                                    Infolists\Components\TextEntry::make('pp_age')->label('Preferred Age')
                                        ->getStateUsing(function (Profile $record) {
                                            $pp = $record->partnerPreference;
                                            if (!$pp || (!$pp->age_from && !$pp->age_to)) return 'Any';
                                            return ($pp->age_from ?: '?') . ' – ' . ($pp->age_to ?: '?') . ' yrs';
                                        }),
                                    Infolists\Components\TextEntry::make('pp_height')->label('Preferred Height')
                                        ->getStateUsing(function (Profile $record) {
                                            $pp = $record->partnerPreference;
                                            if (!$pp || (!$pp->height_from_cm && !$pp->height_to_cm)) return 'Any';
                                            return ($pp->height_from_cm ?: '?') . ' – ' . ($pp->height_to_cm ?: '?');
                                        }),
                                    Infolists\Components\TextEntry::make('pp_marital_status')->label('Marital Status')
                                        ->getStateUsing(fn (Profile $record) => self::ppList($record->partnerPreference?->marital_status)),
                                    Infolists\Components\TextEntry::make('pp_complexion')->label('Complexion')
                                        ->getStateUsing(fn (Profile $record) => self::ppList($record->partnerPreference?->complexion)),
                                    Infolists\Components\TextEntry::make('pp_body_type')->label('Body Type')
                                        ->getStateUsing(fn (Profile $record) => self::ppList($record->partnerPreference?->body_type)),
                                    Infolists\Components\TextEntry::make('pp_physical_status')->label('Physical Status')
                                        ->getStateUsing(fn (Profile $record) => self::ppList($record->partnerPreference?->physical_status)),
                                    Infolists\Components\TextEntry::make('pp_family_status')->label('Family Status')
                                        ->getStateUsing(fn (Profile $record) => self::ppList($record->partnerPreference?->family_status)),
                                    Infolists\Components\TextEntry::make('pp_religions')->label('Religion')
                                        ->getStateUsing(fn (Profile $record) => self::ppList($record->partnerPreference?->religions)),
                                    Infolists\Components\TextEntry::make('pp_mother_tongues')->label('Mother Tongue')
                                        ->getStateUsing(fn (Profile $record) => self::ppList($record->partnerPreference?->mother_tongues)),
                                    Infolists\Components\TextEntry::make('pp_education_levels')->label('Education Level')
                                        ->getStateUsing(fn (Profile $record) => self::ppList($record->partnerPreference?->education_levels)),
                                    Infolists\Components\TextEntry::make('pp_occupations')->label('Occupation')
                                        ->getStateUsing(fn (Profile $record) => self::ppList($record->partnerPreference?->occupations)),
                                    Infolists\Components\TextEntry::make('pp_working_countries')->label('Preferred Working Country(ies)')
                                        ->getStateUsing(fn (Profile $record) => self::ppList($record->partnerPreference?->working_countries)),
                                    Infolists\Components\TextEntry::make('pp_working_states')->label('Preferred Working State(s)')
                                        ->getStateUsing(fn (Profile $record) => self::ppList($record->partnerPreference?->working_states)),
                                    Infolists\Components\TextEntry::make('pp_working_districts')->label('Preferred Working District(s)')
                                        ->getStateUsing(fn (Profile $record) => self::ppList($record->partnerPreference?->working_districts)),
                                    Infolists\Components\TextEntry::make('pp_native_districts')->label('Preferred Native District(s)')
                                        ->getStateUsing(fn (Profile $record) => self::ppList($record->partnerPreference?->native_districts)),
                                ]),
                                Infolists\Components\TextEntry::make('partnerPreference.about_partner')
                                    ->label('About Partner Expectations')->default('-')->columnSpanFull(),
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
                                        \Filament\Schemas\Components\Grid::make(5)->schema([
                                            Infolists\Components\TextEntry::make('note_type')
                                                ->label('Type')
                                                ->badge()
                                                ->formatStateUsing(fn (?string $state): string => \App\Models\ProfileNote::NOTE_TYPES[$state] ?? \Illuminate\Support\Str::headline($state ?? 'General'))
                                                ->color(fn (?string $state): string => \App\Models\ProfileNote::NOTE_TYPE_COLORS[$state] ?? 'gray'),
                                            Infolists\Components\TextEntry::make('note')->label('Note')->columnSpan(2),
                                            Infolists\Components\TextEntry::make('adminUser.name')->label('Added By'),
                                            Infolists\Components\TextEntry::make('follow_up_date')->label('Follow-up')
                                                ->date('d M Y')
                                                ->color(fn ($state) => filled($state) && Carbon::parse($state)->isPast() ? 'danger' : 'warning')
                                                ->placeholder('—'),
                                        ]),
                                    ])
                                    ->contained(false)
                                    ->placeholder('No notes yet. Use the "Notes" action from the members list to log a call/WhatsApp.'),
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
                                                ->tooltip(fn ($record) => $record->logged_in_at?->timezone(config('app.display_timezone', 'Asia/Kolkata'))->format('M j, Y g:i:s A')),
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
                        // Selects use the same canonical lists as the
                        // member-facing registration wizard. The lists
                        // live in config/reference_data.php so the admin
                        // and member surfaces stay in sync. ->searchable()
                        // is on the longer ones (height, weight, language)
                        // so admins can type to filter instead of scrolling
                        // through 80+ options.
                        Forms\Components\Select::make('mother_tongue')
                            ->label('Mother Tongue')
                            ->options(fn () => self::listToOptions(config('reference_data.language_list', [])))
                            ->searchable(),
                        Forms\Components\Select::make('height')
                            ->options(fn () => self::listToOptions(config('reference_data.height_list', [])))
                            ->searchable(),
                        Forms\Components\Select::make('weight_kg')
                            ->label('Weight (kg)')
                            ->options(fn () => self::listToOptions(config('reference_data.weight_list', [])))
                            ->searchable(),
                        Forms\Components\Select::make('complexion')
                            ->options(fn () => self::listToOptions(config('reference_data.complexion_list', []))),
                        Forms\Components\Select::make('body_type')
                            ->label('Body Type')
                            ->options(fn () => self::listToOptions(config('reference_data.body_type_list', []))),
                        Forms\Components\Select::make('blood_group')
                            ->label('Blood Group')
                            ->options(fn () => self::listToOptions(config('reference_data.blood_group_list', []))),
                        Forms\Components\Select::make('physical_status')
                            ->label('Physical Status')
                            ->options(fn () => self::listToOptions(config('reference_data.physical_status_list', [])))
                            // profiles.physical_status is NOT NULL — must always
                            // resolve to a value. Default to 'Normal' which is
                            // the same default the registration wizard uses.
                            ->default('Normal')
                            ->required(),
                        Forms\Components\Textarea::make('about_me')->label('About Me')->rows(3)->columnSpanFull(),
                    ]),

                // ── Section 2: Account & Contact ──
                Section::make('Account & Contact')
                    ->icon('heroicon-o-phone')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('user_email')->label('Email')->email()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->unique(table: 'users', column: 'email', ignorable: fn (?Profile $record) => $record?->user),
                        Forms\Components\TextInput::make('user_phone')->label('Phone')->tel()
                            ->unique(table: 'users', column: 'phone', ignorable: fn (?Profile $record) => $record?->user),
                        Forms\Components\TextInput::make('cont_whatsapp')->label('WhatsApp')->maxLength(15),
                        Forms\Components\TextInput::make('cont_custodian_name')->label('Custodian Name')->maxLength(100),
                        Forms\Components\Select::make('cont_custodian_relation')
                            ->label('Custodian Relation')
                            ->options(fn () => self::listToOptions(config('reference_data.custodian_relation_list', []))),
                        Forms\Components\Select::make('cont_preferred_call_time')
                            ->label('Preferred Call Time')
                            ->options(fn () => self::listToOptions(config('reference_data.preferred_call_time_list', []))),
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
                        // Religion drives which sub-fields show. ->live() so the
                        // form reacts the moment staff change it; ->visible()
                        // gates each block by religion so a profile only shows
                        // its relevant fields (no cross-religion clutter). This
                        // is visibility only — values still load + save normally.
                        Forms\Components\Select::make('rel_religion')->label('Religion')
                            // optionsWithCurrent keeps the profile's stored religion selectable
                            // even if it's been deactivated in Dropdown Options, so editing such
                            // a profile here can't blank the religion (and cascade-clear its
                            // sub-fields) on save.
                            ->options(fn (Get $get) => self::optionsWithCurrent(config('reference_data.religion_list', []), $get('rel_religion')))
                            ->searchable()
                            ->live(),
                        // ── Christian ──
                        // Denomination is ->live() so Diocese can cascade off it
                        // by rite (Latin / Syro-Malabar / Syro-Malankara).
                        Forms\Components\Select::make('rel_denomination')->label('Denomination')
                            ->options(fn () => self::listToGroupedOptions(config('reference_data.denomination_list', [])))
                            ->searchable()
                            ->live()
                            ->visible(fn (Get $get) => $get('rel_religion') === 'Christian'),
                        // Free-text: only relevant when Denomination = "Other".
                        Forms\Components\TextInput::make('rel_other_denomination_name')->label('Denomination (specified)')->maxLength(100)
                            ->visible(fn (Get $get) => $get('rel_religion') === 'Christian'),
                        Forms\Components\Select::make('rel_diocese')->label('Diocese')
                            ->options(fn (Get $get) => self::dioceseOptionsFor($get('rel_denomination'), $get('rel_diocese')))
                            ->searchable()
                            // Roman Catholic has 132 Latin dioceses; lift the
                            // default 50-option cap so the full list scrolls
                            // (otherwise it cuts off ~Hyderabad and you'd have to type).
                            ->optionsLimit(200)
                            ->visible(fn (Get $get) => $get('rel_religion') === 'Christian'),
                        // Free-text: only relevant when Diocese = "Other".
                        Forms\Components\TextInput::make('rel_diocese_name')->label('Diocese Name (Other)')->maxLength(100)
                            ->visible(fn (Get $get) => $get('rel_religion') === 'Christian'),
                        Forms\Components\TextInput::make('rel_parish')->label('Parish Name / Place')->maxLength(200)
                            ->visible(fn (Get $get) => $get('rel_religion') === 'Christian'),
                        // ── Hindu / Jain ──
                        // Caste / Sub-Caste options come from the admin-managed
                        // Communities table, filtered to the selected religion
                        // (Hindu vs Jain) so a profile only sees its religion's
                        // communities — the same source + filtering as the public
                        // registration cascade. The record's currently-stored
                        // value is always kept selectable (even if it falls
                        // outside the filtered list, e.g. after a religion
                        // change), so it can never be hidden or wiped on save.
                        Forms\Components\Select::make('rel_caste')->label('Caste / Community')
                            ->options(fn (Get $get) => self::optionsWithCurrent(
                                \App\Models\Community::getCasteList($get('rel_religion')),
                                $get('rel_caste'),
                            ))
                            ->searchable()
                            ->live() // so Sub-Caste re-cascades when the caste changes
                            ->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Set $set) => $set('rel_sub_caste', null))
                            ->visible(fn (Get $get) => in_array($get('rel_religion'), ['Hindu', 'Jain'], true)),
                        // Free-text: only relevant when Caste = "Other (not listed)" / "Other".
                        Forms\Components\TextInput::make('rel_other_caste_name')->label('Caste (specified)')->maxLength(100)
                            ->visible(fn (Get $get) => in_array($get('rel_religion'), ['Hindu', 'Jain'], true)),
                        Forms\Components\Select::make('rel_sub_caste')->label('Sub-Caste')
                            ->options(fn (Get $get) => self::optionsWithCurrent(
                                \App\Models\Community::getSubCommunitiesFor($get('rel_caste'), $get('rel_religion')),
                                $get('rel_sub_caste'),
                            ))
                            ->searchable()
                            ->visible(fn (Get $get) => in_array($get('rel_religion'), ['Hindu', 'Jain'], true)),
                        Forms\Components\Select::make('rel_gotra')->label('Gotra / Gothram')
                            ->options(fn () => self::optionsPlusExisting(config('reference_data.gothram_list', []), 'religious_info', 'gotra'))
                            ->searchable()
                            ->visible(fn (Get $get) => in_array($get('rel_religion'), ['Hindu', 'Jain'], true)),
                        Forms\Components\Select::make('rel_nakshatra')->label('Nakshatra (Star)')
                            ->options(fn () => self::listToOptions(config('reference_data.nakshatra_list', [])))
                            ->searchable()
                            ->visible(fn (Get $get) => in_array($get('rel_religion'), ['Hindu', 'Jain'], true)),
                        Forms\Components\Select::make('rel_rashi')->label('Rashi (Zodiac)')
                            ->options(fn () => self::listToOptions(config('reference_data.rasi_list', [])))
                            ->visible(fn (Get $get) => in_array($get('rel_religion'), ['Hindu', 'Jain'], true)),
                        // Manglik/Dosh — simple yes/no/unknown, no config list.
                        Forms\Components\Select::make('rel_manglik')->label('Manglik / Dosh')
                            ->options(['Yes' => 'Yes', 'No' => 'No', "Don't Know" => "Don't Know"])
                            ->visible(fn (Get $get) => in_array($get('rel_religion'), ['Hindu', 'Jain'], true)),
                        // ── Muslim ──
                        Forms\Components\Select::make('rel_muslim_sect')->label('Muslim Sect')
                            ->options(fn () => self::listToOptions(config('reference_data.muslim_sect_list', [])))
                            ->visible(fn (Get $get) => $get('rel_religion') === 'Muslim'),
                        Forms\Components\Select::make('rel_muslim_community')->label('Muslim Community')
                            ->options(fn () => self::listToOptions(config('reference_data.jamath_list', [])))
                            ->searchable()
                            ->visible(fn (Get $get) => $get('rel_religion') === 'Muslim'),
                        Forms\Components\Select::make('rel_religious_observance')->label('Religious Observance')
                            ->options(fn () => self::listToOptions(config('reference_data.religious_observance_list', [])))
                            ->visible(fn (Get $get) => $get('rel_religion') === 'Muslim'),
                        // ── Jain ──
                        Forms\Components\Select::make('rel_jain_sect')->label('Jain Sect')
                            ->options(fn () => self::listToOptions(config('reference_data.jain_sect_list', [])))
                            ->visible(fn (Get $get) => $get('rel_religion') === 'Jain'),
                        // ── Other ──
                        Forms\Components\TextInput::make('rel_other_religion_name')->label('Other Religion (if "Other")')->maxLength(50)
                            ->visible(fn (Get $get) => $get('rel_religion') === 'Other'),
                        // Always shown (any religion).
                        Forms\Components\TextInput::make('rel_time_of_birth')->label('Time of Birth')->maxLength(20),
                        Forms\Components\TextInput::make('rel_place_of_birth')->label('Place of Birth')->maxLength(100),
                    ]),

                // ── Section 4: Education & Career ──
                Section::make('Education & Career')
                    ->icon('heroicon-o-academic-cap')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('edu_highest_education')->label('Highest Education')
                            ->options(fn () => self::listToGroupedOptions(config('reference_data.educational_qualifications_list', [])))
                            ->searchable(),
                        Forms\Components\Select::make('edu_education_level')->label('Education Level')
                            ->options(fn () => self::listToOptions(config('reference_data.education_level_list', []))),
                        // Free-text: specialization / college / employer.
                        Forms\Components\TextInput::make('edu_education_detail')->label('Education Details')->maxLength(200),
                        Forms\Components\TextInput::make('edu_college_name')->label('College / University')->maxLength(200),
                        Forms\Components\Select::make('edu_occupation')->label('Occupation')
                            ->options(fn () => self::listToGroupedOptions(config('reference_data.occupation_category_list', [])))
                            ->searchable(),
                        Forms\Components\TextInput::make('edu_occupation_detail')->label('Occupation Details')->maxLength(200),
                        Forms\Components\Select::make('edu_employment_category')->label('Employment Category')
                            ->options(fn () => self::listToOptions(config('reference_data.employment_category_list', []))),
                        Forms\Components\TextInput::make('edu_employer_name')->label('Employer Name')->maxLength(200),
                        Forms\Components\Select::make('edu_annual_income')->label('Annual Income')
                            ->options(fn () => self::listToOptions(config('reference_data.annual_income_list', [])))
                            ->searchable(),
                        Forms\Components\Select::make('edu_working_country')->label('Working Country')
                            ->options(fn () => self::listToGroupedOptions(config('reference_data.country_list', [])))
                            ->searchable(),
                        // Free-text: state/district have no canonical list.
                        Forms\Components\TextInput::make('edu_working_state')->label('Working State')->maxLength(100),
                        Forms\Components\TextInput::make('edu_working_district')->label('Working District')->maxLength(100),
                        Forms\Components\TextInput::make('edu_working_city')->label('Working City / Town')->maxLength(100),
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
                        Forms\Components\Select::make('fam_family_status')->label('Family Status')
                            ->options(fn () => self::listToOptions(config('reference_data.family_status_list', []))),
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
                        Forms\Components\Select::make('loc_native_country')->label('Native Country')
                            ->options(fn () => self::listToGroupedOptions(config('reference_data.country_list', [])))
                            ->searchable(),
                        // Free-text: state/district have no canonical list.
                        Forms\Components\TextInput::make('loc_native_state')->label('Native State')->maxLength(100),
                        Forms\Components\TextInput::make('loc_native_district')->label('Native District')->maxLength(100),
                        Forms\Components\TextInput::make('loc_native_place')->label('Native Place / Town / Village')->maxLength(100),
                        Forms\Components\Select::make('loc_residing_country')->label('Residing Country')
                            ->options(fn () => self::listToGroupedOptions(config('reference_data.country_list', [])))
                            ->searchable(),
                        Forms\Components\Select::make('loc_residency_status')->label('Residency Status')
                            ->options(fn () => self::listToOptions(config('reference_data.residency_status_list', []))),
                        Forms\Components\TextInput::make('loc_pin_zip_code')->label('PIN/ZIP Code')->maxLength(10),
                    ]),

                // ── Section 7: Lifestyle ──
                Section::make('Lifestyle')
                    ->icon('heroicon-o-heart')
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('life_diet')->label('Diet')
                            ->options(fn () => self::listToOptions(config('reference_data.eating_habits', []))),
                        Forms\Components\Select::make('life_smoking')->label('Smoking')
                            ->options(fn () => self::listToOptions(config('reference_data.smoking_habits', []))),
                        Forms\Components\Select::make('life_drinking')->label('Drinking')
                            ->options(fn () => self::listToOptions(config('reference_data.drinking_habits', []))),
                        Forms\Components\Select::make('life_cultural_background')->label('Cultural Background')
                            ->options(fn () => self::listToOptions(config('reference_data.cultural_background_list', []))),
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

                // ── Partner Preferences ──
                Section::make('Partner Preferences')
                    ->icon('heroicon-o-sparkles')
                    ->description("What this member is looking for in a partner — the same fields as the member's own Partner Preferences page. Leave a field empty for \"Any\".")
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('pp_age_from')->label('Age From')->numeric()->minValue(18)->maxValue(80)->placeholder('Any'),
                        Forms\Components\TextInput::make('pp_age_to')->label('Age To')->numeric()->minValue(18)->maxValue(80)->placeholder('Any'),
                        Forms\Components\Select::make('pp_height_from')->label('Height From')->searchable()
                            ->options(fn () => self::listToOptions(config('reference_data.height_list', []))),
                        Forms\Components\Select::make('pp_height_to')->label('Height To')->searchable()
                            ->options(fn () => self::listToOptions(config('reference_data.height_list', []))),
                        self::ppMultiSelect('marital_status', 'Marital Status', 'reference_data.marital_status_list', false),
                        self::ppMultiSelect('complexion', 'Complexion', 'reference_data.complexion_list', false),
                        self::ppMultiSelect('body_type', 'Body Type', 'reference_data.body_type_list', false),
                        self::ppMultiSelect('physical_status', 'Physical Status', 'reference_data.physical_status_list', false),
                        self::ppMultiSelect('family_status', 'Family Status', 'reference_data.family_status_list', false),
                        self::ppMultiSelect('religions', 'Religion', 'reference_data.religion_list', false),
                        self::ppMultiSelect('mother_tongues', 'Mother Tongue', 'reference_data.language_list', false, true),
                        self::ppMultiSelect('education_levels', 'Education Level', 'reference_data.educational_qualifications_list', true, true),
                        self::ppMultiSelect('occupations', 'Occupation', 'reference_data.occupation_category_list', true, true),
                        self::ppMultiSelect('working_countries', 'Preferred Working Country(ies)', 'reference_data.country_list', true, true),
                        self::ppMultiSelect('working_states', 'Preferred Working State(s)', 'locations.indian_states', false, true),
                        self::ppMultiSelect('working_districts', 'Preferred Working District(s)', 'locations.state_district_map', true, true),
                        self::ppMultiSelect('native_districts', 'Preferred Native District(s)', 'locations.state_district_map', true, true),
                        Forms\Components\Textarea::make('pp_about_partner')->label('About Partner Expectations')->rows(3)->maxLength(5000)->columnSpanFull(),
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
        // Branch scoping: Branch Manager / Branch Staff see profiles in their branch
        // PLUS any with branch_id IS NULL (defense in depth — AffiliateTracker now
        // stamps every new signup with the first active branch, but if an edge
        // case ever leaves a profile orphan, branch staff still see it and can
        // re-assign rather than being blind to it).
        //
        // Note: this resource manages Profile (member-facing), not the User model directly.
        return parent::getEloquentQuery()
            ->whereNotNull('full_name')
            ->whereHas('user', fn ($q) => $q->whereNull('staff_role_id'))
            ->with(['user', 'user.latestLogin', 'religiousInfo', 'educationDetail', 'locationInfo', 'primaryPhoto'])
            ->withCount('profileNotes')
            ->forUserBranch(null, true);
    }

    /**
     * URL to a member's admin view page — or null when the member is missing or
     * soft-deleted. The view route resolves through getEloquentQuery() (which
     * excludes trashed), so linking to a deleted member 404s. List views pass the
     * related Profile here so the link degrades to plain text instead of a dead
     * link. Pass the relation (null for trashed), NOT the *_profile_id FK (always
     * set, so it would not guard anything).
     */
    public static function profileViewUrl(?\App\Models\Profile $profile): ?string
    {
        return $profile ? static::getUrl('view', ['record' => $profile->getKey()]) : null;
    }

    /**
     * Convert a zero-indexed reference_data list into the [value => label]
     * shape Filament Select expects. Both halves point at the same
     * string — we store and display the option label, not a separate id.
     */
    private static function listToOptions(array $list): array
    {
        return array_combine($list, $list) ?: [];
    }

    /**
     * Convert a GROUPED reference list (['Group' => ['A','B'], ...]) into
     * Filament's grouped-options shape (['Group' => ['A'=>'A','B'=>'B']]).
     * Used for occupation_category_list, country_list, denomination_list,
     * educational_qualifications_list. Stray flat items (no group) are
     * placed at the top level.
     */
    private static function listToGroupedOptions(array $grouped): array
    {
        $out = [];
        foreach ($grouped as $group => $items) {
            if (is_array($items)) {
                $out[$group] = array_combine($items, $items);
            } else {
                $out[$items] = $items; // flat item mixed into a grouped list
            }
        }
        return $out;
    }

    /**
     * A multi-select for a Partner Preference array field (admin member-edit
     * "Partner Preferences" section), mirroring the member's own page. Merges
     * any already-selected values into the options — even ones since
     * deactivated in reference data — so opening + saving a member in admin
     * never silently drops a stored preference (same protection as the
     * member-facing form's mergeWithSelected).
     *
     * @param  string  $field      PartnerPreference column + pp_ form-key suffix.
     * @param  string  $configKey  Dotted config path, e.g. 'reference_data.religion_list'.
     * @param  bool    $grouped    True for grouped lists (education/occupation/country/districts).
     */
    private static function ppMultiSelect(string $field, string $label, string $configKey, bool $grouped, bool $searchable = false): Forms\Components\Select
    {
        return Forms\Components\Select::make('pp_' . $field)
            ->label($label)
            ->multiple()
            ->searchable($searchable)
            ->options(function (?\App\Models\Profile $record) use ($field, $configKey, $grouped) {
                $list = config($configKey, []);
                $options = $grouped ? self::listToGroupedOptions($list) : self::listToOptions($list);

                // Keep already-chosen-but-now-deactivated values visible & saveable.
                $stored = array_map('strval', (array) ($record?->partnerPreference?->{$field} ?? []));
                $present = [];
                array_walk_recursive($options, function ($v, $k) use (&$present) { $present[] = (string) $k; });
                $missing = array_values(array_diff($stored, $present));
                if ($missing) {
                    $options['Currently selected'] = array_combine($missing, $missing);
                }

                return $options;
            });
    }

    /**
     * Format a Partner Preference array value for the read-only View tab —
     * comma-separated, or "Any" when empty.
     */
    private static function ppList($value): string
    {
        $arr = array_values(array_filter((array) ($value ?? []), fn ($v) => $v !== null && $v !== ''));
        return $arr ? implode(', ', $arr) : 'Any';
    }

    /**
     * Options for a field whose stored data may contain values outside the
     * curated list (caste / sub_caste / gotra carry region-specific +
     * legacy values). Merges the curated source with every distinct value
     * already in the column, so a Select can NEVER hide — and on save wipe
     * — an existing value. Searchable so the merged list stays usable.
     *
     * @param  array  $curated  Curated option values (e.g. config list or Community names).
     */
    private static function optionsPlusExisting(array $curated, string $table, string $column): array
    {
        $values = $curated;

        try {
            $existing = \DB::table($table)
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->distinct()
                ->pluck($column)
                ->all();
            $values = array_merge($values, $existing);
        } catch (\Throwable $e) {
            // Table/column unavailable (fresh install) — fall back to curated only.
        }

        // Dedupe, keep curated order first, drop empties.
        $values = array_values(array_unique(array_filter($values, fn ($v) => $v !== null && $v !== '')));

        return array_combine($values, $values) ?: [];
    }

    /**
     * Shape a flat list of option values as [value => value] and guarantee the
     * record's currently-stored value stays selectable even if it's not in the
     * list — e.g. a Caste/Sub-Caste outside the religion-filtered set, or a
     * Religion that's been deactivated in Dropdown Options. Without this a
     * closed Select would hide the value and then blank it on save. Used by the
     * Religion, Caste and Sub-Caste selects.
     *
     * @param  array  $list     The current option values (already filtered, if applicable).
     * @param  mixed  $current  The field's current stored value to preserve.
     */
    private static function optionsWithCurrent(array $list, $current): array
    {
        $opts = array_combine($list, $list) ?: [];

        if (is_string($current) && $current !== '' && ! array_key_exists($current, $opts)) {
            $opts[$current] = $current;
        }

        return $opts;
    }

    /**
     * Diocese options for the admin form, filtered to the selected
     * denomination's rite (config denomination_rite + the diocese rows'
     * cascade_group). Non-Catholic / no denomination → no rite → just keep the
     * record's current value selectable. Full-list fallback if a rite has no
     * tagged dioceses. Always preserves the stored value via optionsWithCurrent.
     */
    private static function dioceseOptionsFor($denomination, $current): array
    {
        $rite = is_string($denomination) && $denomination !== ''
            ? (config('reference_data.denomination_rite')[$denomination] ?? null)
            : null;

        $list = [];
        if ($rite) {
            $base = fn () => \App\Models\ReferenceDataOption::query()
                ->where('category', 'diocese')->where('is_active', true)
                ->orderBy('sort_order')->orderBy('value');
            $list = $base()->where('cascade_group', $rite)->pluck('value')->all();
            if (empty($list)) {
                $list = $base()->pluck('value')->all(); // fallback: all dioceses
            }
        }

        $opts = self::optionsWithCurrent($list, $current);
        // Always offer an "Other" choice. The member forms add this client-side,
        // but the admin Select doesn't — so add it here for every denomination
        // (incl. Non-Catholic). Pairs with the "Diocese Name (Other)" text field.
        $opts['Other'] = 'Other (not listed)';

        return $opts;
    }
}
