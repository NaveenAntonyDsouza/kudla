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
        // simpler staff screens (owner, 25 Nov 2026): one way to add a member
        if (\App\Support\AdminModules::simpleStaffScreens()) {
            return [$this->addMemberAction()];
        }

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
                        ->maxDate(fn (\Filament\Schemas\Components\Utilities\Get $get) => \App\Support\MinimumAge::latestBirthDate($get('gender')))
                        ->helperText(fn (\Filament\Schemas\Components\Utilities\Get $get) => \App\Support\MinimumAge::message($get('gender'))),

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
                            'registration_source' => \App\Support\DeviceDetector::ADMIN,
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

    /**
     * "Add member" (simpler staff screens, 25 Nov 2026) in place of New Profile and Register on
     * Behalf: the few facts every profile needs, then the member page for the rest.
     *   - "Email the login details" ticked: the member uses the site and finishes their own
     *     profile at first sign-in, as Register on Behalf did.
     *   - Not ticked: the office fills it in on the member page and the profile counts as
     *     complete, as New Profile did (Crasta's offline families).
     * Active on and Approved per the auto-approve setting, as on New Profile since the Crasta
     * fix; staff-added members need no verification call; every add is in the Activity Log.
     */
    private function addMemberAction(): Actions\Action
    {
        return Actions\Action::make('addMember')
            ->label('Add member')
            ->icon('heroicon-o-user-plus')
            ->color('success')
            ->visible(fn (): bool => (bool) (auth()->user()?->isSuperAdmin() || auth()->user()?->hasPermission('register_on_behalf')))
            ->modalDescription('Add the member now and fill in the rest on their page.')
            ->form([
                Forms\Components\TextInput::make('full_name')->label('Full name')->required()->maxLength(100),
                Forms\Components\Select::make('gender')->label('Gender')->required()
                    ->options(['male' => 'Groom (Male)', 'female' => 'Bride (Female)']),
                Forms\Components\DatePicker::make('date_of_birth')->label('Date of birth')->required()
                    ->maxDate(fn (\Filament\Schemas\Components\Utilities\Get $get) => \App\Support\MinimumAge::latestBirthDate($get('gender')))
                    ->helperText(fn (\Filament\Schemas\Components\Utilities\Get $get) => \App\Support\MinimumAge::message($get('gender'))),
                Forms\Components\TextInput::make('phone')->label('Phone')->tel()->required()->unique('users', 'phone')->maxLength(15),
                // owner, 27 Nov 2026: who the profile is for, as web sign-up asks
                Forms\Components\Select::make('created_by')->label('Profile created by (optional)')
                    ->options(fn (): array => array_combine($l = config('reference_data.created_by_list', []), $l)),
                Forms\Components\TextInput::make('email')->label('Email (optional)')->email()->unique('users', 'email')->maxLength(255)
                    ->live(onBlur: true),
                // owner, 27 Nov 2026: staff can give an office-run member their password on the phone
                Forms\Components\TextInput::make('password')->label('Password (optional)')->password()->revealable()
                    ->minLength(8)->maxLength(72)
                    ->helperText('Leave it empty for a random one (the member can use "Forgot password").'),
                Forms\Components\Toggle::make('send_login')
                    ->label('Email the login details to the member')
                    ->helperText('They then finish their own profile when they first sign in. Leave it off when the office fills the profile in.')
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => filled($get('email'))),
            ])
            ->modalSubmitActionLabel('Add member')
            ->action(function (array $data): void {
                $email = filled($data['email'] ?? null) ? trim((string) $data['email']) : null;
                $sendLogin = $email !== null && ! empty($data['send_login']);
                $chosen = filled($data['password'] ?? null);
                $password = $chosen ? (string) $data['password'] : Str::random(12);
                $autoApprove = SiteSetting::getValue('auto_approve_profiles', '1') === '1';

                $profile = DB::transaction(function () use ($data, $email, $sendLogin, $password, $autoApprove): Profile {
                    $user = User::create([
                        'name' => $data['full_name'],
                        'email' => $email,
                        'phone' => $data['phone'],
                        // raw: the User model's "hashed" cast hashes it once
                        'password' => $password,
                        'role' => 'user',
                        'is_active' => true,
                    ]);
                    if (! $sendLogin) {
                        // an office-run profile: nobody has to confirm the address
                        $user->forceFill(['email_verified_at' => now()])->save();
                    }
                    if (method_exists($user, 'assignRole')) {
                        try {
                            $user->assignRole('User');
                        } catch (\Throwable) {
                            // the role may not exist on every site, as on New Profile
                        }
                    }

                    return Profile::create([
                        'user_id' => $user->id,
                        'full_name' => $data['full_name'],
                        'gender' => $data['gender'],
                        'date_of_birth' => $data['date_of_birth'],
                        'created_by' => filled($data['created_by'] ?? null) ? $data['created_by'] : 'staff',
                        'creator_name' => auth()->user()?->name,
                        'created_by_staff_id' => auth()->id(),
                        'registration_source' => \App\Support\DeviceDetector::ADMIN,
                        'is_active' => true,
                        'is_approved' => $autoApprove,
                        'onboarding_completed' => ! $sendLogin,
                        'onboarding_step_completed' => $sendLogin ? 1 : 5,
                    ]);
                });

                if ($sendLogin) {
                    try {
                        Mail::to($email)->send(new StaffCreatedMemberWelcomeMail($profile->user, $password));
                    } catch (\Throwable $e) {
                        report($e);   // the member can still use "Forgot password"
                    }
                }
                \App\Support\StaffActionLog::record('member_registered_by_staff', $profile->id, ['matri_id' => $profile->matri_id, 'login_emailed' => $sendLogin, 'password_set_by_staff' => $chosen]);

                Notification::make()
                    ->title('Added '.$profile->matri_id.'. Fill in the rest of the profile here.')
                    ->body($sendLogin ? 'The login details were emailed to the member.' : null)
                    ->success()
                    ->send();
                $this->redirect(UserResource::getUrl('edit', ['record' => $profile]));
            });
    }

    public function getTabs(): array
    {
        // Members who deleted their account are listed only under Deleted (6 Nov 2026, owner's
        // choice: on the website the profile stays, inactive and hidden, until it is erased 90
        // days later, and it showed as APPROVED in All Members). Profile::withoutDeletedAccounts.
        $members = fn (): Builder => Profile::query()->withoutDeletedAccounts();
        $listed = fn (Builder $query): Builder => $query->withoutDeletedAccounts();
        $paidNow = function ($q) {
            $q->where('is_active', true)
                ->where(fn ($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>', now()));
        };
        $expiring = function ($q) {
            $q->where('is_active', true)
                ->whereBetween('ends_at', [now(), now()->addDays(7)]);
        };
        $away30 = function ($q) {
            $q->where(function ($q2) {
                $q2->where('last_login_at', '<', now()->subDays(30))
                    ->orWhereNull('last_login_at');
            });
        };
        $offOrHidden = function ($q) {
            $q->where('is_active', false)->orWhere('is_hidden', true);
        };

        return [
            'all' => Tab::make('All Members')
                ->icon('heroicon-o-users')
                ->modifyQueryUsing($listed)
                ->badge(fn () => $members()->count())
                ->badgeColor('primary'),

            'pending' => Tab::make('Pending Approval')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $listed($query)->where('is_approved', false))
                ->badge(fn () => $members()->whereNotNull('full_name')->where('is_approved', false)->count())
                ->badgeColor('warning'),

            'incomplete' => Tab::make('Incomplete')
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $listed($query)->where('profile_completion_pct', '<', 60))
                ->badge(fn () => $members()->where('profile_completion_pct', '<', 60)->count())
                ->badgeColor('warning'),

            'nophoto' => Tab::make('No Photo')
                ->icon('heroicon-o-photo')
                ->modifyQueryUsing(fn (Builder $query) => $listed($query)->whereDoesntHave('primaryPhoto'))
                ->badge(fn () => $members()->whereNotNull('full_name')->whereDoesntHave('primaryPhoto')->count())
                ->badgeColor('gray'),

            'premium' => Tab::make('Premium')
                ->icon('heroicon-o-star')
                ->modifyQueryUsing(fn (Builder $query) => $listed($query)->whereHas('user.userMemberships', $paidNow))
                ->badge(fn () => $members()->whereHas('user.userMemberships', $paidNow)->count())
                ->badgeColor('success'),

            'vip' => Tab::make('VIP')
                ->icon('heroicon-o-trophy')
                ->modifyQueryUsing(fn (Builder $query) => $listed($query)->where('is_vip', true))
                ->badge(fn () => $members()->where('is_vip', true)->count())
                ->badgeColor('warning'),

            'featured' => Tab::make('Featured')
                ->icon('heroicon-o-sparkles')
                ->modifyQueryUsing(fn (Builder $query) => $listed($query)->where('is_featured', true))
                ->badge(fn () => $members()->where('is_featured', true)->count())
                ->badgeColor('info'),

            'free' => Tab::make('Free Users')
                ->icon('heroicon-o-user')
                ->modifyQueryUsing(fn (Builder $query) => $listed($query)->whereDoesntHave('user.userMemberships', $paidNow))
                ->badge(fn () => $members()->whereDoesntHave('user.userMemberships', $paidNow)->count())
                ->badgeColor('gray'),

            'expiring' => Tab::make('Expiring Soon')
                ->icon('heroicon-o-exclamation-circle')
                ->modifyQueryUsing(fn (Builder $query) => $listed($query)->whereHas('user.userMemberships', $expiring))
                ->badge(fn () => $members()->whereHas('user.userMemberships', $expiring)->count())
                ->badgeColor('warning'),

            'recent' => Tab::make('Recent (7 days)')
                ->icon('heroicon-o-sparkles')
                ->modifyQueryUsing(fn (Builder $query) => $listed($query)->where('created_at', '>=', now()->subDays(7)))
                ->badge(fn () => $members()->where('created_at', '>=', now()->subDays(7))->count())
                ->badgeColor('success'),

            'inactive' => Tab::make('Inactive (30+ days)')
                ->icon('heroicon-o-moon')
                ->modifyQueryUsing(fn (Builder $query) => $listed($query)->whereHas('user', $away30))
                ->badge(fn () => $members()->whereHas('user', $away30)->count())
                ->badgeColor('gray'),

            'moderated' => Tab::make('Suspended / Banned')
                ->icon('heroicon-o-shield-exclamation')
                ->modifyQueryUsing(fn (Builder $query) => $listed($query)->whereIn('suspension_status', ['suspended', 'banned']))
                ->badge(fn () => $members()->whereIn('suspension_status', ['suspended', 'banned'])->count())
                ->badgeColor('danger'),

            'deactivated' => Tab::make('Blocked / Deactivated')
                ->icon('heroicon-o-no-symbol')
                ->modifyQueryUsing(fn (Builder $query) => $listed($query)->where($offOrHidden))
                ->badge(fn () => $members()->where($offOrHidden)->count())
                ->badgeColor('gray'),

            // deleted by the member (website or app) or by staff
            'deleted' => Tab::make('Deleted')
                ->icon('heroicon-o-trash')
                ->modifyQueryUsing(fn (Builder $query) => $query->deletedAccounts()->whereNotNull('full_name'))
                ->badge(fn () => Profile::query()->deletedAccounts()->whereNotNull('full_name')->count())
                ->badgeColor('danger'),
        ];
    }
}
