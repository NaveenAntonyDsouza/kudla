<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'matri_id',
        'full_name',
        'gender',
        'date_of_birth',
        'created_by',
        'creator_name',
        'creator_contact_number',
        'marital_status',
        'height_cm',
        'weight_kg',
        'physical_status',
        'body_type',
        'complexion',
        'blood_group',
        'about_me',
        'profile_completion_pct',
        'onboarding_completed',
        'onboarding_step_completed',
        'is_active',
        'is_approved',
        'is_verified',
        'id_proof_verified',
        'how_did_you_hear_about_us',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'profile_completion_pct' => 'integer',
            'onboarding_completed' => 'boolean',
            'onboarding_step_completed' => 'integer',
            'is_active' => 'boolean',
            'is_approved' => 'boolean',
            'is_verified' => 'boolean',
            'id_proof_verified' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($profile) {
            if (! $profile->matri_id) {
                $prefix = SiteSetting::getValue('profile_id_prefix', 'AM');
                $last = static::withTrashed()
                    ->whereNotNull('matri_id')
                    ->orderByRaw('CAST(SUBSTRING(matri_id, '.(strlen($prefix) + 1).') AS UNSIGNED) DESC')
                    ->first();
                $next = $last ? intval(substr($last->matri_id, strlen($prefix))) + 1 : 100001;
                $profile->matri_id = $prefix.$next;
            }
        });
    }

    // Accessors

    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date_of_birth ? Carbon::parse($this->date_of_birth)->age : null,
        );
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNull('deleted_at');
    }

    public function scopeGender(Builder $query, string $gender): Builder
    {
        return $query->where('gender', $gender);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function religiousInfo(): HasOne
    {
        return $this->hasOne(ReligiousInfo::class);
    }

    public function educationDetail(): HasOne
    {
        return $this->hasOne(EducationDetail::class);
    }

    public function familyDetail(): HasOne
    {
        return $this->hasOne(FamilyDetail::class);
    }

    public function locationInfo(): HasOne
    {
        return $this->hasOne(LocationInfo::class);
    }

    public function lifestyleInfo(): HasOne
    {
        return $this->hasOne(LifestyleInfo::class);
    }

    public function contactInfo(): HasOne
    {
        return $this->hasOne(ContactInfo::class);
    }

    public function socialMediaLink(): HasOne
    {
        return $this->hasOne(SocialMediaLink::class);
    }

    public function partnerPreference(): HasOne
    {
        return $this->hasOne(PartnerPreference::class);
    }

    public function photoPrivacySetting(): HasOne
    {
        return $this->hasOne(PhotoPrivacySetting::class);
    }

    public function profilePhotos(): HasMany
    {
        return $this->hasMany(ProfilePhoto::class);
    }

    public function idProofs(): HasMany
    {
        return $this->hasMany(IdProof::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function dailyInterestUsages(): HasMany
    {
        return $this->hasMany(DailyInterestUsage::class);
    }

    public function sentInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'sender_profile_id');
    }

    public function receivedInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'receiver_profile_id');
    }

    public function sentPhotoRequests(): HasMany
    {
        return $this->hasMany(PhotoRequest::class, 'requester_profile_id');
    }

    public function receivedPhotoRequests(): HasMany
    {
        return $this->hasMany(PhotoRequest::class, 'target_profile_id');
    }

    public function viewedByOthers(): HasMany
    {
        return $this->hasMany(ProfileView::class, 'viewed_profile_id');
    }

    public function viewedOthers(): HasMany
    {
        return $this->hasMany(ProfileView::class, 'viewer_profile_id');
    }

    public function shortlists(): HasMany
    {
        return $this->hasMany(Shortlist::class);
    }

    public function blockedProfiles(): HasMany
    {
        return $this->hasMany(BlockedProfile::class);
    }

    public function ignoredProfiles(): HasMany
    {
        return $this->hasMany(IgnoredProfile::class);
    }
}
