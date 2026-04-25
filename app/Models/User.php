<?php

namespace App\Models;

use App\Models\Concerns\BranchScopable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use BranchScopable, HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'staff_role_id',
        'branch_id',
        'phone_verified_at',
        'email_verified_at',
        'is_active',
        'last_login_at',
        'last_reengagement_sent_at',
        'reengagement_level',
        'last_weekly_match_sent_at',
        'nudges_sent_count',
        'last_nudge_sent_at',
        'notification_preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'last_reengagement_sent_at' => 'datetime',
            'reengagement_level' => 'integer',
            'last_weekly_match_sent_at' => 'datetime',
            'nudges_sent_count' => 'integer',
            'last_nudge_sent_at' => 'datetime',
            'notification_preferences' => 'array',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function userMemberships(): HasMany
    {
        return $this->hasMany(UserMembership::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function loginHistory(): HasMany
    {
        return $this->hasMany(LoginHistory::class)->latest('logged_in_at');
    }

    public function staffRole(): BelongsTo
    {
        return $this->belongsTo(StaffRole::class, 'staff_role_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /* ------------------------------------------------------------------
     |  Re-engagement helpers (Phase 2.2)
     | ------------------------------------------------------------------ */

    /**
     * Number of days since the user last logged in. Null if they never logged in.
     */
    public function daysInactive(): ?int
    {
        if (!$this->last_login_at) {
            // Never logged in — use account creation date as proxy
            return (int) $this->created_at->diffInDays(now());
        }
        return (int) $this->last_login_at->diffInDays(now());
    }

    /**
     * Whether the user is currently opted-in to receive a given notification preference.
     * Defaults to TRUE for preferences not yet set (explicit opt-in model).
     */
    public function wantsNotification(string $key): bool
    {
        $prefs = $this->notification_preferences ?? [];
        return (bool) ($prefs[$key] ?? true);
    }

    /**
     * Whether this user is eligible to receive re-engagement emails right now.
     * Checks: has email, is active non-staff, hasn't opted out, not rate-limited.
     */
    public function canReceiveReengagement(): bool
    {
        // Must be a member (not staff)
        if ($this->staff_role_id !== null) {
            return false;
        }
        // Must be active + have email
        if (!$this->is_active || !$this->email) {
            return false;
        }
        // Must not have opted out
        if (!$this->wantsNotification('email_reengagement')) {
            return false;
        }
        // Rate limit: don't re-send within 6 days of last re-engagement email
        if ($this->last_reengagement_sent_at && $this->last_reengagement_sent_at->greaterThan(now()->subDays(6))) {
            return false;
        }
        return true;
    }

    /**
     * Generate a signed, one-click unsubscribe URL for a specific preference.
     */
    public function unsubscribeUrl(string $preference): string
    {
        return \Illuminate\Support\Facades\URL::signedRoute(
            'unsubscribe',
            ['user' => $this->id, 'preference' => $preference]
        );
    }

    /**
     * Whether this user is eligible to receive a profile completion nudge (in-app).
     *
     * Checks: member (not staff), active, onboarding_completed, not rate-limited (7 days),
     * under lifetime cap (4 total), not deep-inactive (they're in re-engagement flow).
     *
     * Note: does NOT check completion %; caller should skip users at ≥80% separately.
     */
    public function canReceiveNudge(): bool
    {
        // Must be a member
        if ($this->staff_role_id !== null) {
            return false;
        }
        // Must be active
        if (!$this->is_active) {
            return false;
        }
        // Must have completed onboarding (if not, EnsureProfileComplete middleware handles them)
        if (!$this->profile?->onboarding_completed) {
            return false;
        }
        // Lifetime cap
        if (($this->nudges_sent_count ?? 0) >= 4) {
            return false;
        }
        // Rate limit — 7 days between nudges
        if ($this->last_nudge_sent_at && $this->last_nudge_sent_at->greaterThan(now()->subDays(7))) {
            return false;
        }
        // Deep-inactive users belong to re-engagement flow, not nudge cycle
        if ($this->last_login_at && $this->last_login_at->lessThan(now()->subDays(30))) {
            return false;
        }
        return true;
    }

    /**
     * Whether this user is eligible to receive weekly match suggestion emails.
     * Checks: member (not staff), active, has email, hasn't opted out, not rate-limited,
     * not too inactive (60+ days → they're in re-engagement flow).
     *
     * Note: does NOT check if they have PartnerPreference or any matches —
     * the caller must run the match algorithm and skip empty results.
     */
    public function canReceiveWeeklyMatches(): bool
    {
        // Must be a member
        if ($this->staff_role_id !== null) {
            return false;
        }
        // Must be active + have email
        if (!$this->is_active || !$this->email) {
            return false;
        }
        // Must not have opted out
        if (!$this->wantsNotification('email_weekly_matches')) {
            return false;
        }
        // Rate limit: don't re-send within 5 days of last weekly-match email
        if ($this->last_weekly_match_sent_at && $this->last_weekly_match_sent_at->greaterThan(now()->subDays(5))) {
            return false;
        }
        // Deep-inactive users belong to the re-engagement cycle, not weekly matches
        if ($this->last_login_at && $this->last_login_at->lessThan(now()->subDays(60))) {
            return false;
        }
        return true;
    }

    /**
     * Check if the user has a given permission via their staff role.
     */
    public function hasPermission(string $key): bool
    {
        return $this->staffRole?->hasPermission($key) ?? false;
    }

    /**
     * Get the scope value for a permission ('yes'/'no'/'all'/'own'/'none').
     */
    public function permissionScope(string $key): string
    {
        return $this->staffRole?->permissionScope($key) ?? 'no';
    }

    /**
     * Check if user is Super Admin (shortcut).
     */
    public function isSuperAdmin(): bool
    {
        return $this->staffRole?->isSuperAdmin() ?? false;
    }

    public function isPremium(): bool
    {
        return $this->userMemberships()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->whereHas('plan', function ($query) {
                $query->where('can_view_contact', true);
            })
            ->exists();
    }

    public function activeMembership(): ?UserMembership
    {
        return $this->userMemberships()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->latest('starts_at')
            ->first();
    }

    /**
     * Does this user hold an active membership on a plan with the
     * `allows_free_member_chat` flag set?
     *
     * Used by App\Services\InterestService to decide whether free
     * members on the OTHER side of an interest can send custom-text
     * messages or chat replies to this user. Models the BharatMatrimony
     * Platinum convention — high-end-tier holders accept full expressive
     * contact from free senders.
     *
     * Returns false defensively on any DB failure (matches the
     * test-environment-safe pattern used across the service layer).
     */
    public function activePlanAllowsFreeMemberChat(): bool
    {
        try {
            return $this->userMemberships()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>', now());
                })
                ->whereHas('plan', function ($query) {
                    $query->where('allows_free_member_chat', true);
                })
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Users with any staff_role_id OR with role='admin' (legacy) can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // New role system — any user with a staff role can access
        if ($this->staff_role_id !== null) {
            return true;
        }

        // Legacy fallback — role='admin' still works for backward compat
        return $this->role === 'admin';
    }
}
