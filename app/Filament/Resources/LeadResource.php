<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\LeadResource\RelationManagers;
use App\Mail\StaffCreatedMemberWelcomeMail;
use App\Models\AdminActivityLog;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\Profile;
use App\Models\SiteSetting;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Leads';
    protected static ?string $modelLabel = 'Lead';
    protected static ?string $pluralModelLabel = 'Leads';
    protected static \UnitEnum|string|null $navigationGroup = 'Leads';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return Permissions::can('view_lead');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('view_lead');
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

    public static function getNavigationBadge(): ?string
    {
        $query = Lead::overdue();

        $user = auth()->user();
        if ($user && !$user->isSuperAdmin() && $user->permissionScope('view_lead') === 'own') {
            $query->where('assigned_to_staff_id', $user->id);
        }

        $count = $query->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        // Branch scoping: applied first so Branch Manager/Staff see only their branch.
        $query = parent::getEloquentQuery()
            ->with(['assignedStaff', 'createdByStaff'])
            ->forUserBranch();

        $user = auth()->user();
        if ($user && !$user->isSuperAdmin()) {
            $scope = $user->permissionScope('view_lead');
            if ($scope === 'own') {
                // Branch Manager / Branch Staff have role-based access via the branch filter
                // above — they don't need an additional assigned_to_me filter.
                // Other roles (Telecaller) DO need the assigned_to_me filter.
                $isBranchRole = in_array($user->staffRole?->slug, ['branch_manager', 'branch_staff'], true);
                if (!$isBranchRole) {
                    $query->where('assigned_to_staff_id', $user->id);
                }
            } elseif ($scope === 'none') {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    public static function form(Schema $form): Schema
    {
        $canAssign = Permissions::can('assign_lead');

        return $form->schema([
            \Filament\Schemas\Components\Section::make('Basic Information')
                ->schema([
                    Forms\Components\TextInput::make('full_name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(150),

                    Forms\Components\TextInput::make('phone')
                        ->label('Phone')
                        ->tel()
                        ->required()
                        ->maxLength(20),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(150),

                    Forms\Components\Select::make('gender')
                        ->label('Gender')
                        ->options(['male' => 'Male (Groom)', 'female' => 'Female (Bride)']),

                    Forms\Components\TextInput::make('age')
                        ->label('Age')
                        ->numeric()
                        ->minValue(18)
                        ->maxValue(90),
                ])
                ->columns(2),

            \Filament\Schemas\Components\Section::make('Lead Details')
                ->schema([
                    Forms\Components\Select::make('source')
                        ->label('Source')
                        ->required()
                        ->options(Lead::sources())
                        ->default('walk_in'),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->required()
                        ->options(Lead::statusOptions())
                        ->default('new'),

                    Forms\Components\DatePicker::make('follow_up_date')
                        ->label('Follow-up Date')
                        ->minDate(today())
                        ->helperText('When should we call this lead again?'),
                ])
                ->columns(3),

            \Filament\Schemas\Components\Section::make('Branch')
                ->schema([
                    \App\Filament\Forms\BranchFormField::make(
                        helperText: 'The branch this lead belongs to. Auto-stamped from your branch when omitted.',
                    ),
                ])
                ->columns(1),

            \Filament\Schemas\Components\Section::make('Assignment')
                ->schema([
                    Forms\Components\Select::make('assigned_to_staff_id')
                        ->label('Assigned To')
                        ->options(
                            User::whereNotNull('staff_role_id')
                                ->whereHas('staffRole', fn ($q) => $q->whereNotIn('slug', ['super_admin']))
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->helperText('Staff member who will follow up with this lead.'),
                ])
                ->visible($canAssign),

            \Filament\Schemas\Components\Section::make('Notes')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(4)
                        ->maxLength(5000)
                        ->columnSpanFull(),
                ])
                ->collapsed(fn (string $operation) => $operation === 'edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->formatStateUsing(fn (string $state): string => Lead::sources()[$state] ?? $state)
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => Lead::statuses()[$state]['label'] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => Lead::statuses()[$state]['color'] ?? 'gray'),

                Tables\Columns\TextColumn::make('assignedStaff.name')
                    ->label('Assigned To')
                    ->badge()
                    ->color('warning')
                    ->placeholder('Unassigned'),

                Tables\Columns\TextColumn::make('follow_up_date')
                    ->label('Follow-up')
                    ->date('M j, Y')
                    ->color(function (Lead $record): ?string {
                        if (!$record->follow_up_date) return 'gray';
                        if ($record->is_overdue) return 'danger';
                        if ($record->follow_up_date->isToday()) return 'warning';
                        return 'success';
                    })
                    ->weight(fn (Lead $record): ?string => $record->is_overdue ? 'bold' : null)
                    ->placeholder('—'),

                \App\Filament\Tables\BranchTableComponents::column(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->since()
                    ->toggleable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Lead::statusOptions()),

                Tables\Filters\SelectFilter::make('source')
                    ->options(Lead::sources()),

                Tables\Filters\SelectFilter::make('assigned_to_staff_id')
                    ->label('Assigned To')
                    ->options(fn () => User::whereNotNull('staff_role_id')->pluck('name', 'id')->toArray()),

                \App\Filament\Tables\BranchTableComponents::filter(),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Follow-ups')
                    ->query(fn (Builder $query) => $query->overdue()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->visible(fn () => Permissions::can('edit_lead')),

                Actions\Action::make('addCallLog')
                    ->label('Add Call Log')
                    ->icon('heroicon-o-phone')
                    ->color('info')
                    ->visible(fn () => Permissions::can('manage_call_log'))
                    ->form([
                        Forms\Components\Select::make('call_type')
                            ->label('Call Type')
                            ->required()
                            ->options(CallLog::callTypes())
                            ->default('outgoing'),

                        Forms\Components\Select::make('outcome')
                            ->label('Outcome')
                            ->required()
                            ->options(CallLog::outcomeOptions())
                            ->default('connected'),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(2000),

                        Forms\Components\Toggle::make('follow_up_required')
                            ->label('Follow-up Required')
                            ->live()
                            ->default(false),

                        Forms\Components\DatePicker::make('follow_up_date')
                            ->label('Follow-up Date')
                            ->minDate(today())
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('follow_up_required')),

                        Forms\Components\DateTimePicker::make('called_at')
                            ->label('Called At')
                            ->required()
                            ->default(now()),
                    ])
                    ->action(function (Lead $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $callLog = $record->callLogs()->create([
                                'called_by_staff_id' => auth()->id(),
                                'call_type' => $data['call_type'],
                                'outcome' => $data['outcome'],
                                'duration_minutes' => $data['duration_minutes'] ?? 0,
                                'notes' => $data['notes'] ?? null,
                                'follow_up_required' => $data['follow_up_required'] ?? false,
                                'follow_up_date' => $data['follow_up_date'] ?? null,
                                'called_at' => $data['called_at'],
                            ]);

                            // If follow-up required, update the lead's follow_up_date
                            if (!empty($data['follow_up_required']) && !empty($data['follow_up_date'])) {
                                $record->update(['follow_up_date' => $data['follow_up_date']]);
                            }

                            // Log activity
                            try {
                                AdminActivityLog::create([
                                    'admin_user_id' => auth()->id(),
                                    'action' => 'call_log_created',
                                    'model_type' => 'Lead',
                                    'model_id' => $record->id,
                                    'changes' => ['outcome' => $data['outcome'], 'call_log_id' => $callLog->id],
                                    'ip_address' => request()->ip(),
                                ]);
                            } catch (\Throwable $e) {
                                // silently fail
                            }
                        });

                        Notification::make()
                            ->title('Call log added')
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('convertToMember')
                    ->label('Convert to Member')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (Lead $record): bool =>
                        $record->status !== 'registered'
                        && Permissions::can('register_on_behalf')
                    )
                    ->form(fn (Lead $record) => [
                        Forms\Components\TextInput::make('full_name')
                            ->label('Full Name')
                            ->required()
                            ->default($record->full_name)
                            ->maxLength(150),

                        Forms\Components\Select::make('gender')
                            ->label('Gender')
                            ->required()
                            ->default($record->gender)
                            ->options(['male' => 'Groom (Male)', 'female' => 'Bride (Female)']),

                        Forms\Components\DatePicker::make('date_of_birth')
                            ->label('Date of Birth')
                            ->required()
                            ->maxDate(now()->subYears(18))
                            ->helperText('Must be at least 18 years old.'),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->default($record->email)
                            ->unique('users', 'email')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->required()
                            ->default($record->phone)
                            ->unique('users', 'phone')
                            ->maxLength(15),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->helperText('Leave blank to auto-generate a secure password.'),

                        Forms\Components\Toggle::make('send_credentials')
                            ->label('Send credentials via email')
                            ->default(true),
                    ])
                    ->action(function (Lead $record, array $data) {
                        $tempPassword = $data['password'] ?: Str::random(12);
                        $autoApprove = SiteSetting::getValue('auto_approve_profiles', '1') === '1';

                        $profile = DB::transaction(function () use ($record, $data, $tempPassword, $autoApprove) {
                            $user = User::create([
                                'name' => $data['full_name'],
                                'email' => $data['email'],
                                'phone' => $data['phone'],
                                'password' => Hash::make($tempPassword),
                                'role' => 'user',
                                'is_active' => true,
                            ]);

                            $profile = Profile::create([
                                'user_id' => $user->id,
                                'full_name' => $data['full_name'],
                                'gender' => $data['gender'],
                                'date_of_birth' => $data['date_of_birth'],
                                'created_by' => 'staff',
                                'creator_name' => auth()->user()->name,
                                'created_by_staff_id' => auth()->id(),
                                'is_active' => true,
                                'is_approved' => $autoApprove,
                                'onboarding_completed' => false,
                                'onboarding_step_completed' => 1,
                            ]);

                            // Update the lead with conversion info
                            $record->update([
                                'profile_id' => $profile->id,
                                'converted_at' => now(),
                                'converted_by_staff_id' => auth()->id(),
                                'status' => 'registered',
                            ]);

                            return $profile;
                        });

                        // Send welcome email
                        if ($data['send_credentials'] ?? true) {
                            try {
                                Mail::to($profile->user->email)->send(new StaffCreatedMemberWelcomeMail($profile->user, $tempPassword));
                            } catch (\Throwable $e) {
                                // silently fail
                            }
                        }

                        // Log activity
                        try {
                            AdminActivityLog::create([
                                'admin_user_id' => auth()->id(),
                                'action' => 'lead_converted_to_member',
                                'model_type' => 'Lead',
                                'model_id' => $record->id,
                                'changes' => ['matri_id' => $profile->matri_id, 'profile_id' => $profile->id],
                                'ip_address' => request()->ip(),
                            ]);
                        } catch (\Throwable $e) {
                            // silently fail
                        }

                        Notification::make()
                            ->title('Lead converted to member')
                            ->body("Matri ID: {$profile->matri_id} | Temp Password: {$tempPassword}")
                            ->success()
                            ->persistent()
                            ->send();
                    }),

                Actions\DeleteAction::make()
                    ->visible(fn () => Permissions::can('delete_lead')),

                Actions\RestoreAction::make()
                    ->visible(fn () => Permissions::can('delete_lead')),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('assign')
                        ->label('Assign to Staff')
                        ->icon('heroicon-o-user-plus')
                        ->color('warning')
                        ->visible(fn () => Permissions::can('assign_lead'))
                        ->form([
                            Forms\Components\Select::make('staff_id')
                                ->label('Assign to')
                                ->required()
                                ->options(
                                    User::whereNotNull('staff_role_id')
                                        ->whereHas('staffRole', fn ($q) => $q->whereNotIn('slug', ['super_admin']))
                                        ->pluck('name', 'id')
                                )
                                ->searchable(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update(['assigned_to_staff_id' => $data['staff_id']]);

                            try {
                                AdminActivityLog::create([
                                    'admin_user_id' => auth()->id(),
                                    'action' => 'leads_bulk_assigned',
                                    'changes' => ['count' => $records->count(), 'staff_id' => $data['staff_id']],
                                    'ip_address' => request()->ip(),
                                ]);
                            } catch (\Throwable $e) {}

                            Notification::make()
                                ->title("Assigned {$records->count()} leads")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Actions\DeleteBulkAction::make()
                        ->visible(fn () => Permissions::can('delete_lead')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CallLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
