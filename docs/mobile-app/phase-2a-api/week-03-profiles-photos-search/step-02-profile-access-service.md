# Step 2 — `ProfileAccessService` (7-gate privacy checks)

## Goal
Centralize the 7 visibility gates from design §4.4 in one service so every endpoint that shows a profile applies them consistently.

## Prerequisites
- [ ] [step-01 — Profile Resources](step-01-profile-resources.md) complete
- [ ] Design reference: [`design/04-profile-api.md §4.4`](../../design/04-profile-api.md)

## Procedure

### 1. Create the service

`app/Services/ProfileAccessService.php`:

```php
<?php

namespace App\Services;

use App\Models\BlockedProfile;
use App\Models\Interest;
use App\Models\Profile;

class ProfileAccessService
{
    public const REASON_SELF = 'self';
    public const REASON_OK = 'ok';
    public const REASON_SAME_GENDER = 'gender_mismatch';
    public const REASON_BLOCKED = 'blocked';
    public const REASON_HIDDEN = 'hidden';
    public const REASON_VISIBILITY = 'visibility_restricted';
    public const REASON_SUSPENDED = 'suspended';

    /**
     * Check whether viewer can see target.
     * Returns one of the REASON_* constants.
     */
    public function check(Profile $viewer, Profile $target): string
    {
        if ($viewer->id === $target->id) return self::REASON_SELF;

        if ($viewer->gender === $target->gender) return self::REASON_SAME_GENDER;

        if ($this->isBlocked($viewer, $target)) return self::REASON_BLOCKED;

        if ($target->suspension_status && $target->suspension_status !== 'active') {
            return self::REASON_SUSPENDED;
        }

        if ($target->is_hidden && ! $this->hasExistingInterest($viewer, $target)) {
            return self::REASON_HIDDEN;
        }

        if (! $this->passesVisibility($viewer, $target)) {
            return self::REASON_VISIBILITY;
        }

        return self::REASON_OK;
    }

    public function canViewContact(Profile $viewer, Profile $target): bool
    {
        return (bool) $viewer->user?->activeMembership
            && $this->hasAcceptedInterest($viewer, $target);
    }

    private function isBlocked(Profile $a, Profile $b): bool
    {
        return BlockedProfile::where(function ($q) use ($a, $b) {
            $q->where(['blocker_profile_id' => $a->id, 'blocked_profile_id' => $b->id])
              ->orWhere(['blocker_profile_id' => $b->id, 'blocked_profile_id' => $a->id]);
        })->exists();
    }

    private function hasExistingInterest(Profile $a, Profile $b): bool
    {
        return Interest::where(function ($q) use ($a, $b) {
            $q->where(['sender_profile_id' => $a->id, 'receiver_profile_id' => $b->id])
              ->orWhere(['sender_profile_id' => $b->id, 'receiver_profile_id' => $a->id]);
        })->exists();
    }

    private function hasAcceptedInterest(Profile $a, Profile $b): bool
    {
        return Interest::where(function ($q) use ($a, $b) {
            $q->where(['sender_profile_id' => $a->id, 'receiver_profile_id' => $b->id])
              ->orWhere(['sender_profile_id' => $b->id, 'receiver_profile_id' => $a->id]);
        })->where('status', 'accepted')->exists();
    }

    private function passesVisibility(Profile $viewer, Profile $target): bool
    {
        $visibility = $target->show_profile_to ?? 'all';

        return match ($visibility) {
            'all' => true,
            'premium' => (bool) $viewer->user?->activeMembership,
            'matches' => $this->matchScoreAbove($viewer, $target, 70),
            default => true,
        };
    }

    private function matchScoreAbove(Profile $viewer, Profile $target, int $threshold): bool
    {
        $score = app(MatchingService::class)->calculateScore($target, $viewer->partnerPreference);
        return ($score['score'] ?? 0) >= $threshold;
    }
}
```

### 2. Write Pest tests

`tests/Unit/Services/ProfileAccessServiceTest.php`:

```php
<?php

use App\Models\BlockedProfile;
use App\Models\Profile;
use App\Services\ProfileAccessService;

it('blocks same gender view', function () {
    $m1 = Profile::factory()->create(['gender' => 'Male']);
    $m2 = Profile::factory()->create(['gender' => 'Male']);

    expect(app(ProfileAccessService::class)->check($m1, $m2))
        ->toBe(ProfileAccessService::REASON_SAME_GENDER);
});

it('allows opposite gender view', function () {
    $male = Profile::factory()->create(['gender' => 'Male']);
    $female = Profile::factory()->create(['gender' => 'Female']);

    expect(app(ProfileAccessService::class)->check($male, $female))
        ->toBe(ProfileAccessService::REASON_OK);
});

it('blocks when one blocks the other', function () {
    $a = Profile::factory()->create(['gender' => 'Male']);
    $b = Profile::factory()->create(['gender' => 'Female']);
    BlockedProfile::create(['blocker_profile_id' => $a->id, 'blocked_profile_id' => $b->id]);

    expect(app(ProfileAccessService::class)->check($a, $b))
        ->toBe(ProfileAccessService::REASON_BLOCKED);
});

it('hides profile with is_hidden=true unless interest exists', function () {
    $a = Profile::factory()->create(['gender' => 'Male']);
    $b = Profile::factory()->create(['gender' => 'Female', 'is_hidden' => true]);

    expect(app(ProfileAccessService::class)->check($a, $b))
        ->toBe(ProfileAccessService::REASON_HIDDEN);
});
```

## Verification

- [ ] Service exists, 7 reasons covered
- [ ] 4 Pest tests green
- [ ] `canViewContact()` returns true only when premium + accepted interest

## Commit

```bash
git add app/Services/ProfileAccessService.php tests/Unit/Services/
git commit -m "phase-2a wk-03: step-02 ProfileAccessService with 7 privacy gates"
```

## Next step
→ [step-03-dashboard-endpoint.md](step-03-dashboard-endpoint.md)
