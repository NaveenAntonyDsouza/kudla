<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffResource\Pages;
use App\Models\StaffRole;
use App\Models\User;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StaffResource extends Resource
{
    protected static ?string $model = User::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Staff';
    protected static ?string $modelLabel = 'Staff Member';
    protected static ?string $pluralModelLabel = 'Staff';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('manage_staff');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('manage_staff');
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
     * Only show users who have a staff_role_id (i.e., staff, not regular members).
     * Hide Super Admin from non-Super-Admins.
     */
    public static function getEloquentQuery(): Builder
    {
        // Branch scoping: Branch Manager sees only staff in their branch
        $query = parent::getEloquentQuery()
            ->whereNotNull('staff_role_id')
            ->forUserBranch();

        $currentUser = auth()->user();
        if (!$currentUser?->isSuperAdmin()) {
            $query->whereHas('staffRole', fn ($q) => $q->where('slug', '!=', 'super_admin'));
        }

        return $query;
    }

    public static function form(Schema $form): Schema
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser?->isSuperAdmin() ?? false;

        // Build role options — exclude Super Admin unless current user is Super Admin
        $roleQuery = StaffRole::query()->where('is_active', true);
        if (!$isSuperAdmin) {
            $roleQuery->where('slug', '!=', 'super_admin');
        }
        $roleOptions = $roleQuery->orderBy('sort_order')->pluck('name', 'id')->toArray();

        return $form->schema([
            \Filament\Schemas\Components\Section::make('Account Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('Phone')
                        ->tel()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(15),
                ])
                ->columns(3),

            \Filament\Schemas\Components\Section::make('Role & Branch')
                ->schema([
                    Forms\Components\Select::make('staff_role_id')
                        ->label('Staff Role')
                        ->required()
                        ->options($roleOptions)
                        ->searchable()
                        ->helperText($isSuperAdmin ? 'Select the role for this staff member.' : 'Super Admin cannot be assigned.'),

                    \App\Filament\Forms\BranchFormField::make(
                        helperText: 'Branch this staff member belongs to. Auto-stamped from your branch.',
                    ),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Deactivated staff cannot log in.'),
                ])
                ->columns(3),

            \Filament\Schemas\Components\Section::make('Password')
                ->description('Leave password blank to auto-generate a secure 12-character password.')
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->maxLength(60)
                        ->dehydrated(fn ($state) => filled($state))
                        ->helperText('Leave blank to auto-generate and send via email.'),

                    Forms\Components\Toggle::make('send_credentials')
                        ->label('Send credentials via email')
                        ->default(true)
                        ->dehydrated(false)
                        ->helperText('Emails the login URL, username, and password to the staff member.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->copyable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('staffRole.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn ($record): string => match ($record?->staffRole?->slug) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'manager', 'branch_manager' => 'info',
                        'staff', 'branch_staff' => 'success',
                        'telecaller' => 'success',
                        'moderator' => 'primary',
                        'support_agent' => 'gray',
                        'finance' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->since()
                    ->placeholder('Never')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                \App\Filament\Tables\BranchTableComponents::column(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('staff_role_id')
                    ->label('Role')
                    ->relationship('staffRole', 'name'),

                \App\Filament\Tables\BranchTableComponents::filter(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),

                \Filament\Actions\Action::make('resetPassword')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Password')
                    ->modalDescription(fn (User $record) => "Generate a new temporary password for {$record->name}?")
                    ->action(function (User $record) {
                        $tempPassword = \Illuminate\Support\Str::random(12);
                        $record->update(['password' => \Illuminate\Support\Facades\Hash::make($tempPassword)]);

                        try {
                            \Illuminate\Support\Facades\Mail::to($record->email)
                                ->send(new \App\Mail\StaffCreatedMemberWelcomeMail($record, $tempPassword));
                        } catch (\Throwable $e) {
                            // Email failed, but password was reset — show it in notification
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Password reset successfully')
                            ->body("New password: {$tempPassword} (copy now — won't be shown again)")
                            ->success()
                            ->persistent()
                            ->send();

                        \App\Models\AdminActivityLog::create([
                            'admin_user_id' => auth()->id(),
                            'action' => 'staff_password_reset',
                            'model_type' => class_basename($record),
                            'model_id' => $record->id,
                            'ip_address' => request()->ip(),
                        ]);
                    }),

                \Filament\Actions\Action::make('toggleActive')
                    ->label(fn (User $record): string => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (User $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $wasActive = $record->is_active;
                        $record->update(['is_active' => !$wasActive]);

                        \App\Models\AdminActivityLog::create([
                            'admin_user_id' => auth()->id(),
                            'action' => $wasActive ? 'staff_deactivated' : 'staff_activated',
                            'model_type' => class_basename($record),
                            'model_id' => $record->id,
                            'ip_address' => request()->ip(),
                        ]);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
        ];
    }
}
