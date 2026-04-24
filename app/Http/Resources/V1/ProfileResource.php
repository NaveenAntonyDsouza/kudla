<?php

namespace App\Http\Resources\V1;

use App\Models\Profile;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full Profile shape for single-profile endpoints.
 *
 * Used by:
 *   - GET /api/v1/profile/me         — own profile (viewer == owner, all sections incl. contact)
 *   - GET /api/v1/profiles/{matriId} — other profile (contact gated by ProfileAccessService)
 *
 * The contact section is nullable by design:
 *   - If caller passes `includeContact: true` (default false), contact is populated
 *   - If false, contact is explicitly null in the response (NOT omitted) so the
 *     Flutter DTO shape stays stable — user.contact?.phone handles the null cleanly.
 *
 * UI-safe API contract points this class enforces:
 *   1. Timestamps       → ISO 8601 (created_at, last_active_at, etc.)
 *   2. Booleans         → real bool (is_approved, is_verified, is_premium, etc.)
 *   3. Arrays           → [] when empty (hobbies, languages_known, partner arrays)
 *   4. Optional fields  → always present with null (contact, primary_photo, match_score)
 *   5. Photo URLs       → delegated to PhotoResource (absolute via model accessors)
 *
 * Design references:
 *   - docs/mobile-app/reference/ui-safe-api-checklist.md
 *   - docs/mobile-app/design/04-profile-api.md §4.3
 */
class ProfileResource extends JsonResource
{
    /**
     * @param  Profile  $resource
     * @param  bool  $includeContact  Whether the contact section should be populated.
     *         Default false. ProfileAccessService decides this upstream based on
     *         membership + interest status.
     * @param  Profile|null  $viewer  The viewing profile. Used for photo-blur
     *         decisions and any per-viewer metadata.
     */
    public function __construct(
        $resource,
        public bool $includeContact = false,
        public ?Profile $viewer = null,
    ) {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        /** @var Profile $profile */
        $profile = $this->resource;

        return [
            // ── Identity ────────────────────────────────────────────
            'matri_id'                 => (string) $profile->matri_id,
            'full_name'                => (string) ($profile->full_name ?? ''),
            'gender'                   => $profile->gender,
            'date_of_birth'            => $profile->date_of_birth?->toDateString(),
            'age'                      => $this->computeAge($profile),
            'marital_status'           => $profile->marital_status,
            'profile_completion_pct'   => (int) ($profile->profile_completion_pct ?? 0),

            // ── Flags (all real booleans) ──────────────────────────
            'is_approved'              => (bool) $profile->is_approved,
            'is_active'                => (bool) $profile->is_active,
            'is_hidden'                => (bool) $profile->is_hidden,
            'is_verified'              => (bool) ($profile->is_verified ?? false),
            'is_vip'                   => (bool) ($profile->is_vip ?? false),
            'is_featured'              => (bool) ($profile->is_featured ?? false),
            'is_premium'               => $this->isPremiumSafely($profile),
            'suspension_status'        => $profile->suspension_status ?? 'active',

            // ── Timestamps (ISO 8601) ──────────────────────────────
            'created_at'               => $profile->created_at?->toIso8601String(),
            'last_active_at'           => $profile->user?->last_login_at?->toIso8601String(),

            // ── 9 editable sections ────────────────────────────────
            'sections' => [
                'primary'    => $this->primarySection($profile),
                'religious'  => $this->religiousSection($profile),
                'education'  => $this->educationSection($profile),
                'family'     => $this->familySection($profile),
                'location'   => $this->locationSection($profile),
                'contact'    => $this->includeContact ? $this->contactSection($profile) : null,
                'hobbies'    => $this->hobbiesSection($profile),
                'social'     => $this->socialSection($profile),
                'partner'    => $this->partnerSection($profile),
            ],

            // ── Photos (grouped) ───────────────────────────────────
            'photos' => $this->photosBlock($profile),
        ];
    }

    /* ------------------------------------------------------------------
     |  Section builders — each always returns a non-null array
     | ------------------------------------------------------------------ */

    private function primarySection(Profile $profile): array
    {
        return [
            'height_label'    => $profile->height ?: null,
            'height_cm'       => $this->computeHeightCm($profile),
            'weight_kg'       => $profile->weight_kg !== null ? (int) $profile->weight_kg : null,
            'complexion'      => $profile->complexion,
            'body_type'       => $profile->body_type,
            'blood_group'     => $profile->blood_group,
            'mother_tongue'   => $profile->mother_tongue,
            'languages_known' => $this->arrayField($profile->lifestyleInfo?->languages_known),
            'physical_status' => $profile->physical_status,
            'about_me'        => $profile->about_me,
        ];
    }

    private function religiousSection(Profile $profile): array
    {
        $r = $profile->religiousInfo;
        if (! $r) {
            return $this->emptyReligiousShape();
        }

        return [
            'religion'              => $r->religion,
            'caste'                 => $r->caste,
            'sub_caste'             => $r->sub_caste,
            'gotra'                 => $r->gotra,
            'nakshatra'             => $r->nakshatra,
            'rashi'                 => $r->rashi,
            'manglik'               => $r->dosh,
            'denomination'          => $r->denomination,
            'diocese'               => $r->diocese,
            'diocese_name'          => $r->diocese_name,
            'parish_name_place'     => $r->parish_name_place,
            'time_of_birth'         => $r->time_of_birth,
            'place_of_birth'        => $r->place_of_birth,
            'muslim_sect'           => $r->muslim_sect,
            'muslim_community'      => $r->muslim_community,
            'religious_observance'  => $r->religious_observance,
            'jain_sect'             => $r->jain_sect,
            'other_religion_name'   => $r->other_religion_name,
            'jathakam_url'          => $r->jathakam_upload_url
                                          ? url($r->jathakam_upload_url)
                                          : null,
        ];
    }

    private function emptyReligiousShape(): array
    {
        // Stable shape even when no religious_info row exists.
        return [
            'religion' => null, 'caste' => null, 'sub_caste' => null,
            'gotra' => null, 'nakshatra' => null, 'rashi' => null,
            'manglik' => null, 'denomination' => null, 'diocese' => null,
            'diocese_name' => null, 'parish_name_place' => null,
            'time_of_birth' => null, 'place_of_birth' => null,
            'muslim_sect' => null, 'muslim_community' => null,
            'religious_observance' => null, 'jain_sect' => null,
            'other_religion_name' => null, 'jathakam_url' => null,
        ];
    }

    private function educationSection(Profile $profile): array
    {
        $e = $profile->educationDetail;

        return [
            'education_level'              => $e->education_level ?? null,
            'highest_education'            => $e->highest_education ?? null,
            'education_detail'             => $e->education_detail ?? null,
            'college_name'                 => $e->college_name ?? null,
            'occupation'                   => $e->occupation ?? null,
            'occupation_detail'            => $e->occupation_detail ?? null,
            'employment_category'          => $e->employment_category ?? null,
            'employer_name'                => $e->employer_name ?? null,
            'annual_income'                => $e->annual_income ?? null,
            'working_country'              => $e->working_country ?? null,
            'working_state'                => $e->working_state ?? null,
            'working_district'             => $e->working_district ?? null,
        ];
    }

    private function familySection(Profile $profile): array
    {
        $f = $profile->familyDetail;

        return [
            'family_status'        => $f->family_status ?? null,
            'father_name'          => $f->father_name ?? null,
            'father_occupation'    => $f->father_occupation ?? null,
            'mother_name'          => $f->mother_name ?? null,
            'mother_occupation'    => $f->mother_occupation ?? null,
            'brothers_total'       => $f->brothers_total ?? null,
            'brothers_married'     => $f->brothers_married ?? null,
            'sisters_total'        => $f->sisters_total ?? null,
            'sisters_married'      => $f->sisters_married ?? null,
            'family_origin'        => $f->family_origin ?? null,
            'asset_details'        => $f->asset_details ?? null,
            'about_family'         => $f->about_family ?? null,
        ];
    }

    private function locationSection(Profile $profile): array
    {
        $l = $profile->locationInfo;

        return [
            'native_country'         => $l->native_country ?? null,
            'native_state'           => $l->native_state ?? null,
            'native_district'        => $l->native_district ?? null,
            'pin_zip_code'           => $l->pin_zip_code ?? null,
            'residing_country'       => $l->residing_country ?? null,
            'residing_state'         => $l->residing_state ?? null,
            'residing_city'          => $l->residing_city ?? null,
            'residency_status'       => $l->residency_status ?? null,
            'is_outstation'          => (bool) ($l->is_outstation ?? false),
            'outstation_from'        => $l->outstation_from ?? null,
            'outstation_to'          => $l->outstation_to ?? null,
            'outstation_reason'      => $l->outstation_reason ?? null,
        ];
    }

    private function contactSection(Profile $profile): array
    {
        $c = $profile->contactInfo;

        return [
            'phone'                 => $profile->user?->phone,
            'email'                 => $profile->user?->email,
            'whatsapp_number'       => $c->whatsapp_number ?? null,
            'primary_phone'         => $c->primary_phone ?? null,
            'secondary_phone'       => $c->secondary_phone ?? null,
            'residential_phone'     => $c->residential_phone ?? null,
            'alternate_email'       => $c->alternate_email ?? null,
            'contact_person'        => $c->contact_person ?? null,
            'contact_relationship'  => $c->contact_relationship ?? null,
            'preferred_call_time'   => $c->preferred_call_time ?? null,
            'communication_address' => $c->communication_address ?? null,
            'pincode'               => $c->pincode ?? null,
        ];
    }

    private function hobbiesSection(Profile $profile): array
    {
        $l = $profile->lifestyleInfo;

        return [
            'diet'             => $l->diet ?? null,
            'drinking'         => $l->drinking ?? null,
            'smoking'          => $l->smoking ?? null,
            'cultural_background' => $l->cultural_background ?? null,
            'hobbies'          => $this->arrayField($l->hobbies ?? null),
            'favorite_music'   => $this->arrayField($l->favorite_music ?? null),
            'preferred_books'  => $this->arrayField($l->preferred_books ?? null),
            'preferred_movies' => $this->arrayField($l->preferred_movies ?? null),
            'sports'           => $this->arrayField($l->sports ?? null),
            'favorite_cuisine' => $this->arrayField($l->favorite_cuisine ?? null),
        ];
    }

    private function socialSection(Profile $profile): array
    {
        $s = $profile->socialMediaLink;

        return [
            'facebook_url'   => $s->facebook_url ?? null,
            'instagram_url'  => $s->instagram_url ?? null,
            'linkedin_url'   => $s->linkedin_url ?? null,
            'youtube_url'    => $s->youtube_url ?? null,
            'website_url'    => $s->website_url ?? null,
        ];
    }

    private function partnerSection(Profile $profile): array
    {
        $p = $profile->partnerPreference;

        return [
            'age_from'            => $p->age_from ?? null,
            'age_to'              => $p->age_to ?? null,
            'height_from_cm'      => $p->height_from ?? null,
            'height_to_cm'        => $p->height_to ?? null,
            'complexion'          => $this->arrayField($p->complexion ?? null),
            'body_type'           => $this->arrayField($p->body_type ?? null),
            'marital_status'      => $this->arrayField($p->marital_status ?? null),
            'physical_status'     => $this->arrayField($p->physical_status ?? null),
            'religions'           => $this->arrayField($p->religions ?? null),
            'castes'              => $this->arrayField($p->castes ?? null),
            'sub_castes'          => $this->arrayField($p->sub_castes ?? null),
            'denominations'       => $this->arrayField($p->denominations ?? null),
            'mother_tongues'      => $this->arrayField($p->mother_tongues ?? null),
            'education_levels'    => $this->arrayField($p->education_levels ?? null),
            'occupations'         => $this->arrayField($p->occupations ?? null),
            'income_range'        => $p->income_range ?? null,
            'working_countries'   => $this->arrayField($p->working_countries ?? null),
            'native_states'       => $this->arrayField($p->native_states ?? null),
            'family_status'       => $this->arrayField($p->family_status ?? null),
            'diet'                => $this->arrayField($p->diet ?? null),
            'drinking'            => $this->arrayField($p->drinking ?? null),
            'smoking'             => $this->arrayField($p->smoking ?? null),
            'manglik'             => $p->manglik ?? null,
            'about_partner'       => $p->about_partner ?? null,
        ];
    }

    /* ------------------------------------------------------------------
     |  Photos block
     | ------------------------------------------------------------------ */

    private function photosBlock(Profile $profile): array
    {
        $all = $profile->profilePhotos ?? collect();

        return [
            'profile' => $this->photoCollection($all->where('photo_type', 'profile')->where('is_visible', true)->where('approval_status', 'approved')->values()),
            'album'   => $this->photoCollection($all->where('photo_type', 'album')->where('is_visible', true)->where('approval_status', 'approved')->values()),
            'family'  => $this->photoCollection($all->where('photo_type', 'family')->where('is_visible', true)->where('approval_status', 'approved')->values()),
            'photo_privacy' => $this->photoPrivacyShape($profile),
        ];
    }

    private function photoCollection($photos): array
    {
        return $photos->map(
            fn ($p) => (new PhotoResource($p, viewer: $this->viewer))->resolve()
        )->all();
    }

    private function photoPrivacyShape(Profile $profile): ?array
    {
        $pp = $profile->photoPrivacySetting;
        if (! $pp) {
            return null;
        }

        return [
            'gated_premium'    => (bool) ($pp->gated_premium ?? false),
            'show_watermark'   => (bool) ($pp->show_watermark ?? false),
            'blur_non_premium' => (bool) ($pp->blur_non_premium ?? false),
        ];
    }

    /* ------------------------------------------------------------------
     |  Helpers
     | ------------------------------------------------------------------ */

    /**
     * Avoid N+1 in list views + DB-free in tests: only call isPremium()
     * when the userMemberships relation is already loaded. Otherwise
     * return false. Controllers that need accurate is_premium must
     * preload ->user->userMemberships.
     */
    private function isPremiumSafely(Profile $profile): bool
    {
        if (! $profile->user) {
            return false;
        }
        if (! $profile->user->relationLoaded('userMemberships')) {
            return false;
        }
        return (bool) $profile->user->isPremium();
    }

    private function computeAge(Profile $profile): ?int
    {
        if (! $profile->date_of_birth) {
            return null;
        }
        $age = (int) $profile->date_of_birth->age;
        return $age >= 0 ? $age : null;
    }

    private function computeHeightCm(Profile $profile): ?int
    {
        if (! $profile->height) {
            return null;
        }
        if (preg_match('/(\d+)\s*cm/', $profile->height, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    /**
     * Coerce a potentially-null / JSON-casted array field to a concrete
     * PHP array. Honours UI-safe rule #3: always return [] when empty,
     * never null, never missing.
     *
     * Handles three input shapes:
     *   - null         -> []
     *   - array        -> as-is
     *   - JSON string  -> decoded (rare, but defensive for legacy rows)
     */
    private function arrayField(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return array_values($value);
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values($decoded);
            }
        }

        return [];
    }
}
