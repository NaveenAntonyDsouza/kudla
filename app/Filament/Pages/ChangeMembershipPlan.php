<?php

namespace App\Filament\Pages;

use App\Models\MembershipPlan;
use App\Models\Profile;
use App\Models\UserMembership;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ChangeMembershipPlan extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Change Plan';
    protected static \UnitEnum|string|null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Change Membership Plan';
    protected string $view = 'filament.pages.change-membership-plan';

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

    public ?string $matri_id = null;
    public ?string $plan_id = null;
    public ?string $duration_override = null;
    public ?string $reason = null;

    public ?Profile $foundProfile = null;
    public bool $searched = false;

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Assign / Change Membership Plan')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('matri_id')
                        ->label('Matri ID or Phone Number')
                        ->required()
                        ->placeholder('e.g., AM100001 or 9876543210'),

                    Forms\Components\Select::make('plan_id')
                        ->label('Select Plan')
                        ->required()
                        ->options(fn () => MembershipPlan::where('is_active', true)
                            ->where('price_inr', '>', 0)
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn ($plan) => [$plan->id => $plan->plan_name . ' (' . $plan->duration_months . ' months - ₹' . number_format($plan->price_inr) . ')'])
                            ->toArray()
                        ),

                    Forms\Components\TextInput::make('duration_override')
                        ->label('Custom Duration (months)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(24)
                        ->placeholder('Leave empty to use plan default')
                        ->helperText('Override the plan\'s default duration if needed'),

                    Forms\Components\Textarea::make('reason')
                        ->label('Reason / Notes')
                        ->placeholder('Why is the plan being changed? (e.g., offline payment, complimentary, etc.)')
                        ->rows(2),
                ]),
        ]);
    }

    public function lookupUser(): void
    {
        if (!$this->matri_id) return;

        $search = trim($this->matri_id);

        $this->foundProfile = Profile::query()
            ->whereNotNull('full_name')
            ->where(function ($q) use ($search) {
                $q->where('matri_id', $search)
                    ->orWhereHas('user', fn ($u) => $u->where('phone', $search));
            })
            ->with(['user', 'religiousInfo', 'primaryPhoto'])
            ->first();

        $this->searched = true;
    }

    public function assignPlan(): void
    {
        if (!$this->foundProfile || !$this->plan_id) {
            Notification::make()->title('Please search for a user and select a plan first.')->danger()->send();
            return;
        }

        $plan = MembershipPlan::find($this->plan_id);
        if (!$plan) {
            Notification::make()->title('Invalid plan selected.')->danger()->send();
            return;
        }

        $durationMonths = $this->duration_override ? (int) $this->duration_override : $plan->duration_months;
        $user = $this->foundProfile->user;

        // Deactivate existing active memberships
        $user->userMemberships()->where('is_active', true)->update(['is_active' => false]);

        // Create new membership
        UserMembership::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonths($durationMonths),
            'is_active' => true,
        ]);

        // Add admin note
        \App\Models\ProfileNote::create([
            'profile_id' => $this->foundProfile->id,
            'admin_user_id' => auth()->id(),
            'note' => 'Plan changed to ' . $plan->plan_name . ' (' . $durationMonths . ' months)' . ($this->reason ? '. Reason: ' . $this->reason : ''),
        ]);

        // Send notification to user
        \App\Models\Notification::create([
            'profile_id' => $this->foundProfile->id,
            'type' => 'plan_changed',
            'title' => 'Membership Plan Updated',
            'message' => 'Your membership has been upgraded to ' . $plan->plan_name . ' for ' . $durationMonths . ' months.',
            'is_read' => false,
        ]);

        Notification::make()
            ->title($this->foundProfile->full_name . ' upgraded to ' . $plan->plan_name)
            ->success()
            ->send();

        // Reset form
        $this->reset(['matri_id', 'plan_id', 'duration_override', 'reason', 'foundProfile', 'searched']);
    }
}
