<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Mail\StaffCreatedMemberWelcomeMail;
use App\Models\AdminActivityLog;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Profile'),

            Actions\Action::make('registerOnBehalf')
                ->label('Register on Behalf')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->visible(fn () => auth()->user()?->isSuperAdmin() || auth()->user()?->hasPermission('register_on_behalf'))
                ->form([
                    Forms\Components\TextInput::make('full_name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\Select::make('gender')
                        ->label('Gender')
                        ->required()
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
                        ->unique('users', 'email')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('Phone')
                        ->tel()
                        ->required()
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
                ->action(function (array $data) {
                    $tempPassword = $data['password'] ?: Str::random(12);
                    $autoApprove = SiteSetting::getValue('auto_approve_profiles', '1') === '1';

                    $profile = DB::transaction(function () use ($data, $tempPassword, $autoApprove) {
                        $user = User::create([
                            'name' => $data['full_name'],
                            'email' => $data['email'],
                            'phone' => $data['phone'],
                            'password' => Hash::make($tempPassword),
                            'role' => 'user',
                            'is_active' => true,
                        ]);

                        return Profile::create([
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
                    });

                    // Send welcome email with credentials
                    if ($data['send_credentials'] ?? true) {
                        try {
                            Mail::to($profile->user->email)->send(new StaffCreatedMemberWelcomeMail($profile->user, $tempPassword));
                        } catch (\Throwable $e) {
                            // Silently fail — password is still shown in notification
                        }
                    }

                    // Log activity
                    try {
                        AdminActivityLog::create([
                            'admin_user_id' => auth()->id(),
                            'action' => 'member_registered_by_staff',
                            'model_type' => 'Profile',
                            'model_id' => $profile->id,
                            'changes' => ['matri_id' => $profile->matri_id, 'email' => $profile->user->email],
                            'ip_address' => request()->ip(),
                        ]);
                    } catch (\Throwable $e) {
                        // Silently fail
                    }

                    Notification::make()
                        ->title('Member registered successfully')
                        ->body("Matri ID: {$profile->matri_id} | Temp Password: {$tempPassword}")
                        ->success()
                        ->persistent()
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Members')
                ->icon('heroicon-o-users'),

            'pending' => Tab::make('Pending Approval')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_approved', false))
                ->badge(fn () => \App\Models\Profile::whereNotNull('full_name')->where('is_approved', false)->count())
                ->badgeColor('warning'),

            'incomplete' => Tab::make('Incomplete')
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('profile_completion_pct', '<', 60)),

            'premium' => Tab::make('Premium')
                ->icon('heroicon-o-star')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user.userMemberships', function ($q) {
                    $q->where('is_active', true)
                        ->where(fn ($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>', now()));
                })),

            'vip' => Tab::make('VIP')
                ->icon('heroicon-o-trophy')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_vip', true))
                ->badge(fn () => \App\Models\Profile::where('is_vip', true)->count() ?: null)
                ->badgeColor('warning'),

            'featured' => Tab::make('Featured')
                ->icon('heroicon-o-sparkles')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_featured', true))
                ->badge(fn () => \App\Models\Profile::where('is_featured', true)->count() ?: null)
                ->badgeColor('info'),

            'free' => Tab::make('Free Users')
                ->icon('heroicon-o-user')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDoesntHave('user.userMemberships', function ($q) {
                    $q->where('is_active', true)
                        ->where(fn ($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>', now()));
                })),

            'expiring' => Tab::make('Expiring Soon')
                ->icon('heroicon-o-exclamation-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user.userMemberships', function ($q) {
                    $q->where('is_active', true)
                        ->whereBetween('ends_at', [now(), now()->addDays(7)]);
                })),

            'recent' => Tab::make('Recent (7 days)')
                ->icon('heroicon-o-sparkles')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7))),

            'inactive' => Tab::make('Inactive (30+ days)')
                ->icon('heroicon-o-moon')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user', function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('last_login_at', '<', now()->subDays(30))
                            ->orWhereNull('last_login_at');
                    });
                })),

            'deactivated' => Tab::make('Blocked / Deactivated')
                ->icon('heroicon-o-no-symbol')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                    $q->where('is_active', false)->orWhere('is_hidden', true);
                })),

            'deleted' => Tab::make('Deleted')
                ->icon('heroicon-o-trash')
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes()->withTrashed()->whereNotNull('deleted_at')->whereNotNull('full_name'))
                ->badge(fn () => \App\Models\Profile::onlyTrashed()->whereNotNull('full_name')->count() ?: null)
                ->badgeColor('danger'),
        ];
    }
}
