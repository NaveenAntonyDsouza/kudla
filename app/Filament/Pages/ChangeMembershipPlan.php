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
use Illuminate\Support\Collection;

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

    // Search term (Matri ID / phone / name / email) and its results.
    public ?string $search = null;
    public Collection $matches;
    public ?Profile $foundProfile = null;
    public bool $searched = false;

    // Plan-assignment form state. The fields stay hidden until the staff member
    // clicks "Assign / Change Plan" on the found member (or arrives via a
    // deep-link), keeping the page to just a search box until then.
    public bool $showAssignForm = false;
    public ?string $plan_id = null;
    public ?string $duration_override = null;
    public ?string $reason = null;

    /**
     * Allow deep-linking from other admin pages (e.g. the Active-to-Paid list's
     * "Assign Plan" button) via ?matri_id=… — pre-load that member and open the
     * assignment form so a plan can be assigned in one step.
     */
    public function mount(): void
    {
        $this->matches = collect();

        $prefill = request()->query('matri_id');
        if (filled($prefill)) {
            $this->search = (string) $prefill;
            $this->lookupUser();
            if ($this->foundProfile) {
                $this->showAssignForm = true;
            }
        }
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Plan Details')
                ->description('Choose the plan to assign. Leave duration empty to use the plan default.')
                ->columns(2)
                ->schema([
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
                        ->placeholder('Leave empty to use plan default'),

                    Forms\Components\Textarea::make('reason')
                        ->label('Reason / Notes')
                        ->placeholder('e.g., offline UPI payment, complimentary, correction, etc.')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * Search members by Matri ID, phone, name, or email. A single match loads
     * straight into the found-member card; several matches show a pick-list.
     */
    public function lookupUser(): void
    {
        $term = trim((string) $this->search);

        $this->reset(['foundProfile', 'showAssignForm']);
        $this->matches = collect();

        if ($term === '') {
            $this->searched = false;
            return;
        }

        $matches = Profile::query()
            ->whereNotNull('full_name')
            ->where(function ($q) use ($term) {
                $q->where('matri_id', $term)
                    ->orWhere('full_name', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($u) => $u->where('phone', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%"));
            })
            ->with(['user', 'religiousInfo', 'primaryPhoto'])
            ->orderBy('full_name')
            ->limit(20)
            ->get();

        $this->searched = true;

        if ($matches->count() === 1) {
            $this->foundProfile = $matches->first();
        } else {
            $this->matches = $matches;
        }
    }

    /** Pick one member from the multi-result list. */
    public function selectProfile(int $id): void
    {
        $this->foundProfile = Profile::with(['user', 'religiousInfo', 'primaryPhoto'])->find($id);
        $this->matches = collect();
        $this->showAssignForm = false;
    }

    public function startAssign(): void
    {
        $this->showAssignForm = true;
    }

    public function cancelAssign(): void
    {
        $this->showAssignForm = false;
        $this->reset(['plan_id', 'duration_override', 'reason']);
    }

    public function assignPlan(): void
    {
        if (!$this->foundProfile || !$this->plan_id) {
            Notification::make()->title('Please select a member and a plan first.')->danger()->send();
            return;
        }

        $plan = MembershipPlan::find($this->plan_id);
        if (!$plan) {
            Notification::make()->title('Invalid plan selected.')->danger()->send();
            return;
        }

        $durationMonths = $this->duration_override ? (int) $this->duration_override : $plan->duration_months;
        $user = $this->foundProfile->user;

        // Do the membership swap + admin note + member notification atomically.
        // Without a transaction a failure on a later step (e.g. the previously
        // missing notification user_id) committed the new membership but threw
        // afterwards — leaving orphaned membership rows and a 500 in the UI.
        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $plan, $durationMonths) {
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

            // Notify the member. user_id is a NOT NULL FK on notifications —
            // omitting it is what made this action fail.
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'profile_id' => $this->foundProfile->id,
                'type' => 'plan_changed',
                'title' => 'Membership Plan Updated',
                'message' => 'Your membership has been upgraded to ' . $plan->plan_name . ' for ' . $durationMonths . ' months.',
                'is_read' => false,
            ]);
        });

        Notification::make()
            ->title($this->foundProfile->full_name . ' upgraded to ' . $plan->plan_name)
            ->success()
            ->send();

        // Reset back to a clean search box.
        $this->reset(['search', 'plan_id', 'duration_override', 'reason', 'foundProfile', 'searched', 'showAssignForm']);
        $this->matches = collect();
    }
}
