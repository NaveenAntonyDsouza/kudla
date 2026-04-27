# Step 1 — Profile Resource Classes

## Goal
Create the three JSON shapes every profile endpoint returns: `ProfileCardResource` (lightweight lists), `ProfileResource` (full profile with 9 sections), `DashboardResource` (homepage data bundle).

## Prerequisites
- [ ] Week 2 acceptance ✅
- [ ] Design reference: [`design/04-profile-api.md`](../../design/04-profile-api.md)

## Procedure

### 1. `ProfileCardResource` — lightweight, used in all list endpoints

`app/Http/Resources/V1/ProfileCardResource.php`:

```php
<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileCardResource extends JsonResource
{
    public function toArray($request): array
    {
        $viewer = $request->user()?->profile;

        return [
            'matri_id' => $this->matri_id,
            'full_name' => $this->full_name,
            'age' => $this->date_of_birth?->age,
            'height_cm' => $this->height,
            'height_label' => $this->formattedHeight(),
            'religion' => $this->religiousInfo?->religion,
            'caste' => $this->religiousInfo?->caste,
            'native_state' => $this->locationInfo?->native_state,
            'occupation' => $this->educationDetail?->occupation,
            'education_short' => $this->educationDetail?->educational_qualification,
            'primary_photo' => $this->primaryPhoto ? new PhotoResource($this->primaryPhoto, viewer: $viewer) : null,
            'badges' => $this->badges(),
            'last_active_label' => $this->user?->last_login_at?->diffForHumans(),
            'match_score' => $this->matchScoreFor($viewer),  // null unless cached
            'is_shortlisted' => $this->isShortlistedBy($viewer),
            'interest_status' => $this->interestStatusWith($viewer),
        ];
    }

    private function formattedHeight(): ?string
    {
        if (! $this->height) return null;
        $cm = (int) $this->height;
        $totalInches = $cm / 2.54;
        $feet = (int) ($totalInches / 12);
        $inches = (int) round($totalInches - ($feet * 12));
        return "{$feet}' {$inches}\"";
    }

    private function badges(): array
    {
        $badges = [];
        if ($this->is_verified) $badges[] = 'verified';
        if ($this->user?->activeMembership) $badges[] = 'premium';
        if ($this->is_vip ?? false) $badges[] = 'vip';
        if ($this->is_featured ?? false) $badges[] = 'featured';
        if ($this->created_at?->diffInDays() < 7) $badges[] = 'new';
        return $badges;
    }

    private function matchScoreFor(?\App\Models\Profile $viewer): ?int
    {
        if (! $viewer) return null;
        return \Illuminate\Support\Facades\Cache::get("match_score:{$viewer->id}:{$this->id}");
    }

    private function isShortlistedBy(?\App\Models\Profile $viewer): bool
    {
        if (! $viewer) return false;
        return \App\Models\Shortlist::where('profile_id', $viewer->id)
            ->where('shortlisted_profile_id', $this->id)
            ->exists();
    }

    private function interestStatusWith(?\App\Models\Profile $viewer): ?string
    {
        if (! $viewer) return null;
        $interest = \App\Models\Interest::where(function ($q) use ($viewer) {
            $q->where(['sender_profile_id' => $viewer->id, 'receiver_profile_id' => $this->id])
              ->orWhere(['sender_profile_id' => $this->id, 'receiver_profile_id' => $viewer->id]);
        })->latest()->first();

        if (! $interest) return null;
        $direction = $interest->sender_profile_id === $viewer->id ? 'sent' : 'received';
        return match ($interest->status) {
            'accepted' => 'accepted',
            'declined' => 'declined',
            'pending' => $direction,
            'expired' => 'expired',
            default => null,
        };
    }
}
```

### 2. `PhotoResource` (stub — full version in step 7)

`app/Http/Resources/V1/PhotoResource.php`:

```php
<?php

namespace App\Http\Resources\V1;

use App\Services\PhotoStorageService;
use Illuminate\Http\Resources\Json\JsonResource;

class PhotoResource extends JsonResource
{
    public function __construct($resource, public ?\App\Models\Profile $viewer = null)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        $svc = app(PhotoStorageService::class);
        $isBlurred = $this->shouldBlurFor($this->viewer);

        return [
            'id' => $this->id,
            'url' => $svc->getUrl($this->resource, 'full'),
            'thumbnail_url' => $svc->getUrl($this->resource, 'thumbnail'),
            'medium_url' => $svc->getUrl($this->resource, 'medium'),
            'photo_type' => $this->photo_type,
            'is_primary' => (bool) $this->is_primary,
            'is_blurred' => $isBlurred,
            'approval_status' => $this->approval_status,
        ];
    }

    private function shouldBlurFor(?\App\Models\Profile $viewer): bool
    {
        if (! $viewer || $viewer->id === $this->profile_id) return false;
        $privacy = $this->profile?->photoPrivacySetting;
        if (! $privacy?->blur_non_premium) return false;
        return ! ($viewer->user?->activeMembership);
    }
}
```

### 3. `ProfileResource` — full, with 9 sections

`app/Http/Resources/V1/ProfileResource.php`:

```php
<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function __construct($resource, public bool $includeContact = false, public ?\App\Models\Profile $viewer = null)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        return [
            'matri_id' => $this->matri_id,
            'full_name' => $this->full_name,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'age' => $this->date_of_birth?->age,
            'marital_status' => $this->marital_status,
            'profile_completion_pct' => $this->profile_completion_pct,
            'is_approved' => (bool) $this->is_approved,
            'is_hidden' => (bool) $this->is_hidden,
            'is_verified' => (bool) $this->is_verified,
            'is_premium' => (bool) $this->user?->activeMembership,
            'created_at' => $this->created_at?->toIso8601String(),
            'last_active_at' => $this->user?->last_login_at?->toIso8601String(),
            'sections' => [
                'primary' => $this->primarySection(),
                'religious' => $this->religiousSection(),
                'education' => $this->educationSection(),
                'family' => $this->familySection(),
                'location' => $this->locationSection(),
                'contact' => $this->includeContact ? $this->contactSection() : null,
                'hobbies' => $this->hobbiesSection(),
                'social' => $this->socialSection(),
                'partner' => $this->partnerSection(),
            ],
            'photos' => [
                'profile' => PhotoResource::collection($this->profilePhotos->where('photo_type', 'profile')->values()),
                'album' => PhotoResource::collection($this->profilePhotos->where('photo_type', 'album')->values()),
                'family' => PhotoResource::collection($this->profilePhotos->where('photo_type', 'family')->values()),
                'photo_privacy' => $this->photoPrivacySetting ? [
                    'gated_premium' => (bool) $this->photoPrivacySetting->gated_premium,
                    'show_watermark' => (bool) $this->photoPrivacySetting->show_watermark,
                    'blur_non_premium' => (bool) $this->photoPrivacySetting->blur_non_premium,
                ] : null,
            ],
        ];
    }

    // Implement each section method — paste field mapping from design doc §4.3
    private function primarySection(): array { return [ 'height_cm' => $this->height, 'complexion' => $this->complexion, /* ... */ ]; }
    private function religiousSection(): array { return $this->religiousInfo?->toArray() ?? []; }
    private function educationSection(): array { return $this->educationDetail?->toArray() ?? []; }
    private function familySection(): array { return $this->familyDetail?->toArray() ?? []; }
    private function locationSection(): array { return $this->locationInfo?->toArray() ?? []; }
    private function contactSection(): array { return $this->contactInfo?->toArray() ?? []; }
    private function hobbiesSection(): array { return $this->lifestyleInfo?->only(['hobbies', 'favorite_music', 'preferred_books', 'preferred_movies', 'sports', 'favorite_cuisine']) ?? []; }
    private function socialSection(): array { return $this->socialMediaLink?->toArray() ?? []; }
    private function partnerSection(): array { return $this->partnerPreference?->toArray() ?? []; }
}
```

> Use `design/04-profile-api.md §4.3` as the exact field list per section.

### 4. `DashboardResource` — placeholder for now

`app/Http/Resources/V1/DashboardResource.php`:

```php
<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray($request): array
    {
        // Assembled in step 3 — DashboardController calls multiple services
        // and wraps result here.
        return $this->resource;  // resource = pre-assembled array
    }
}
```

## Verification

- [ ] Three files exist: `ProfileCardResource.php`, `ProfileResource.php`, `PhotoResource.php`
- [ ] `DashboardResource.php` stub exists

## Commit

```bash
git add app/Http/Resources/V1/
git commit -m "phase-2a wk-03: step-01 Profile + Photo + Dashboard Resource classes"
```

## Next step
→ [step-02-profile-access-service.md](step-02-profile-access-service.md)
