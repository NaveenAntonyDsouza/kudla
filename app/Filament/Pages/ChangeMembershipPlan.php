<?php

namespace App\Filament\Pages;

use App\Models\MembershipPlan;
use App\Models\Profile;
use App\Models\Subscription;
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
    public ?string $payment_method = null;
    public ?string $reason = null;

    /**
     * How an offline / manually-recorded payment was received. Stored on the
     * Subscription's `gateway` column so manual sales show up in Payment History
     * alongside the online gateways (razorpay/phonepe/…).
     */
    public const PAYMENT_METHODS = [
        'gpay' => 'GPay / Google Pay',
        'cash' => 'Cash (branch)',
        'neft' => 'NEFT / Bank Transfer',
        'razorpay_manual' => 'Razorpay (manual)',
        'phonepe_manual' => 'PhonePe (manual)',
        'cheque' => 'Cheque',
        'complimentary' => 'Complimentary (₹0)',
        'other' => 'Other',
    ];

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

                    Forms\Components\Select::make('payment_method')
                        ->label('Payment Method')
                        ->required()
                        ->options(self::PAYMENT_METHODS)
                        ->placeholder('How was payment received?')
                        ->helperText('Recorded as a paid transaction so it appears in Payment History.'),

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
        $this->reset(['plan_id', 'duration_override', 'payment_method', 'reason']);
    }

    public function assignPlan(): void
    {
        if (!$this->foundProfile || !$this->plan_id || !$this->payment_method) {
            Notification::make()->title('Please select a member, a plan, and a payment method first.')->danger()->send();
            return;
        }

        $plan = MembershipPlan::find($this->plan_id);
        if (!$plan) {
            Notification::make()->title('Invalid plan selected.')->danger()->send();
            return;
        }

        $durationMonths = $this->duration_override ? (int) $this->duration_override : $plan->duration_months;
        $user = $this->foundProfile->user;
        $paymentMethod = $this->payment_method;
        // Subscription.amount is stored in paise. Complimentary assignments
        // record ₹0; everything else records the plan's list price.
        $amountPaise = $paymentMethod === 'complimentary' ? 0 : (int) round(((float) ($plan->price_inr ?? 0)) * 100);

        // Do the membership swap + paid sales record + admin note + member
        // notification atomically. Without a transaction a failure on a later
        // step (e.g. the previously missing notification user_id) committed the
        // new membership but threw afterwards — leaving orphaned rows + a 500.
        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $plan, $durationMonths, $paymentMethod, $amountPaise) {
            // Deactivate existing active memberships
            $user->userMemberships()->where('is_active', true)->update(['is_active' => false]);

            // Create new membership (the access grant)
            UserMembership::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'starts_at' => now(),
                'ends_at' => now()->addMonths($durationMonths),
                'is_active' => true,
            ]);

            // Record the payment as a paid Subscription so this manual/offline
            // sale appears in Payment History and revenue reports with its
            // payment mode (gateway). Attributed to the member's branch.
            Subscription::create([
                'user_id' => $user->id,
                'branch_id' => $user->branch_id,
                'plan_id' => $plan->id,
                'plan_name' => $plan->plan_name,
                'amount' => $amountPaise,
                'original_amount' => $amountPaise,
                'discount_amount' => 0,
                'gateway' => $paymentMethod,
                'gateway_metadata' => [
                    'manual' => true,
                    'assigned_by_admin_id' => auth()->id(),
                    'assigned_by_admin_name' => auth()->user()?->name,
                    'duration_months' => $durationMonths,
                    'note' => $this->reason,
                ],
                'payment_status' => 'paid',
                'starts_at' => now(),
                'expires_at' => now()->addMonths($durationMonths),
                'is_active' => true,
            ]);

            // Add admin note
            \App\Models\ProfileNote::create([
                'profile_id' => $this->foundProfile->id,
                'admin_user_id' => auth()->id(),
                'note' => 'Plan set to ' . $plan->plan_name . ' (' . $durationMonths . ' months) via ' . (self::PAYMENT_METHODS[$paymentMethod] ?? $paymentMethod) . ($this->reason ? '. Note: ' . $this->reason : ''),
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
        $this->reset(['search', 'plan_id', 'duration_override', 'payment_method', 'reason', 'foundProfile', 'searched', 'showAssignForm']);
        $this->matches = collect();
    }
}
