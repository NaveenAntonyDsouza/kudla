<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Profile\UpdateContactSectionRequest;
use App\Http\Requests\Api\V1\Profile\UpdateEducationSectionRequest;
use App\Http\Requests\Api\V1\Profile\UpdateFamilySectionRequest;
use App\Http\Requests\Api\V1\Profile\UpdateHobbiesSectionRequest;
use App\Http\Requests\Api\V1\Profile\UpdateLocationSectionRequest;
use App\Http\Requests\Api\V1\Profile\UpdatePartnerSectionRequest;
use App\Http\Requests\Api\V1\Profile\UpdatePrimarySectionRequest;
use App\Http\Requests\Api\V1\Profile\UpdateReligiousSectionRequest;
use App\Http\Requests\Api\V1\Profile\UpdateSocialSectionRequest;
use App\Http\Resources\V1\ProfileResource;
use App\Http\Responses\ApiResponse;
use App\Models\Interest;
use App\Models\LifestyleInfo;
use App\Models\PartnerPreference;
use App\Models\PhotoRequest;
use App\Models\Profile;
use App\Models\Shortlist;
use App\Services\MatchingService;
use App\Services\ProfileAccessService;
use App\Services\ProfileCompletionService;
use App\Services\ProfileViewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Single-profile endpoints (own + other).
 *
 *   me()            — GET /api/v1/profile/me              (step 4)
 *   show()          — GET /api/v1/profiles/{matriId}      (step 5)
 *   updateSection() — PUT /api/v1/profile/me/sections/... (step 6)
 *
 * me() is unrestricted self-view. show() runs every profile through
 * the 7 privacy gates in ProfileAccessService and returns a stable
 * 403/404 envelope on failures (404s are used as anti-enumeration —
 * indistinguishable from "profile doesn't exist at all").
 *
 * Design reference:
 *   - docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-04-profile-me-endpoint.md
 *   - docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-05-view-other-profile.md
 *   - docs/mobile-app/design/04-profile-api.md §4.3
 */
class ProfileController extends BaseApiController
{
    /**
     * The 11 relations the full ProfileResource touches. Kept as a
     * constant so me() and show() share one eager-load list and
     * stay in sync as ProfileResource evolves.
     *
     * `user.userMemberships` is nested — it lets is_premium resolve to
     * an accurate boolean (ProfileResource::isPremiumSafely returns
     * false when the relation isn't loaded).
     */
    public const PROFILE_EAGER_LOADS = [
        'user.userMemberships',
        'religiousInfo',
        'educationDetail',
        'familyDetail',
        'locationInfo',
        'contactInfo',
        'lifestyleInfo',
        'partnerPreference',
        'socialMediaLink',
        'photoPrivacySetting',
        'profilePhotos',
    ];

    /** Section names accepted by updateSection(). */
    public const UPDATABLE_SECTIONS = [
        'primary', 'religious', 'education', 'family', 'location',
        'contact', 'hobbies', 'social', 'partner',
    ];

    public function __construct(
        private ProfileAccessService $access,
        private MatchingService $matching,
        private ProfileViewService $profileViewer,
        private ProfileCompletionService $completion,
    ) {}

    /* ==================================================================
     |  me() — own profile
     | ================================================================== */

    /**
     * Return the authenticated user's own profile with all 9 sections,
     * contact populated, photos grouped by type.
     *
     * @authenticated
     *
     * @group Profile
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "profile": {
     *       "matri_id": "AM100042",
     *       "full_name": "Priya Rani",
     *       "gender": "female",
     *       "age": 29,
     *       "is_premium": true,
     *       "sections": {
     *         "primary": {}, "religious": {}, "education": {}, "family": {},
     *         "location": {}, "contact": {}, "hobbies": {}, "social": {}, "partner": {}
     *       },
     *       "photos": {"profile": [], "album": [], "family": [], "photo_privacy": null}
     *     }
     *   }
     * }
     *
     * @response 401 scenario="unauthenticated" {
     *   "success": false,
     *   "error": {"code": "UNAUTHENTICATED", "message": "Unauthenticated."}
     * }
     *
     * @response 422 scenario="no-profile" {
     *   "success": false,
     *   "error": {"code": "PROFILE_REQUIRED", "message": "Complete registration before viewing your profile."}
     * }
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile;

        if (! $profile) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before viewing your profile.',
                null,
                422,
            );
        }

        $profile->loadMissing(self::PROFILE_EAGER_LOADS);

        return ApiResponse::ok([
            'profile' => (new ProfileResource(
                $profile,
                includeContact: true,
                viewer: $profile,
            ))->resolve(),
        ]);
    }

    /* ==================================================================
     |  show() — view another profile
     | ================================================================== */

    /**
     * View another user's profile. Applies all 7 ProfileAccessService
     * gates, tracks a deduped ProfileView, returns viewer-context fields
     * (match score, interest status, shortlist state, etc.).
     *
     * @authenticated
     *
     * @group Profile
     *
     * @urlParam matriId string required The profile's matri_id (e.g. AM100042).
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "profile": {"matri_id": "AM100042", "full_name": "Priya Rani", "sections": {"contact": null}},
     *     "match_score": {"score": 85, "badge": "great", "breakdown": []},
     *     "interest_status": "sent",
     *     "is_shortlisted": false,
     *     "is_blocked": false,
     *     "photo_request_status": null,
     *     "can_view_contact": false
     *   }
     * }
     *
     * @response 403 scenario="same-gender" {
     *   "success": false,
     *   "error": {"code": "GENDER_MISMATCH", "message": "Cannot view same-gender profile."}
     * }
     *
     * @response 403 scenario="premium-only" {
     *   "success": false,
     *   "error": {"code": "PREMIUM_REQUIRED", "message": "This profile is visible to premium members only."}
     * }
     *
     * @response 404 scenario="not-found-or-restricted" {
     *   "success": false,
     *   "error": {"code": "NOT_FOUND", "message": "Profile not available."}
     * }
     *
     * @response 422 scenario="no-viewer-profile" {
     *   "success": false,
     *   "error": {"code": "PROFILE_REQUIRED", "message": "Complete registration before browsing profiles."}
     * }
     */
    public function show(Request $request, string $matriId): JsonResponse
    {
        $target = $this->findTargetByMatriId($matriId);
        if (! $target) {
            return $this->notFound();
        }

        $viewer = $request->user()->profile;
        if (! $viewer) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before browsing profiles.',
                null,
                422,
            );
        }

        $reason = $this->access->check($viewer, $target);

        // Only REASON_OK and REASON_SELF proceed. Everything else is an error.
        if ($reason !== ProfileAccessService::REASON_OK
            && $reason !== ProfileAccessService::REASON_SELF) {
            return $this->accessError($reason);
        }

        $target->loadMissing(self::PROFILE_EAGER_LOADS);

        // Record the view (best-effort — never blocks the response).
        $this->profileViewer->track($viewer, $target);

        $isSelf = $reason === ProfileAccessService::REASON_SELF;
        $canViewContact = $isSelf ? true : $this->access->canViewContact($viewer, $target);

        return ApiResponse::ok([
            'profile' => (new ProfileResource(
                $target,
                includeContact: $canViewContact,
                viewer: $viewer,
            ))->resolve(),
            'match_score'          => $isSelf ? null : $this->computeMatchScore($viewer, $target),
            'interest_status'      => $isSelf ? null : $this->interestStatus($viewer, $target),
            'is_shortlisted'       => $isSelf ? false : $this->isShortlistedBy($viewer, $target),
            'is_blocked'           => false,  // by definition — a block would have returned 404 above
            'photo_request_status' => $isSelf ? null : $this->photoRequestStatus($viewer, $target),
            'can_view_contact'     => $canViewContact,
        ]);
    }

    /* ==================================================================
     |  updateSection() — edit one of 9 sections of own profile
     | ================================================================== */

    /**
     * Persist the authenticated user's edits to a single profile section.
     *
     * Each section has its own FormRequest for validation (9 classes in
     * App\Http\Requests\Api\V1\Profile\*). After the save, the completion
     * percentage is recomputed and returned so Flutter's progress ring
     * refreshes without a follow-up GET.
     *
     * @authenticated
     *
     * @group Profile
     *
     * @urlParam section string required One of: primary, religious,
     *   education, family, location, contact, hobbies, social, partner.
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "section": "primary",
     *     "updated_fields": ["about_me", "languages_known"],
     *     "profile_completion_pct": 68
     *   }
     * }
     *
     * @response 404 scenario="unknown-section" {
     *   "success": false,
     *   "error": {"code": "NOT_FOUND", "message": "Profile not available."}
     * }
     *
     * @response 422 scenario="validation-failed" {
     *   "success": false,
     *   "error": {
     *     "code": "VALIDATION_FAILED",
     *     "message": "Please check the fields below.",
     *     "fields": {"mother_tongue": ["The mother tongue field is required."]}
     *   }
     * }
     *
     * @response 422 scenario="no-profile" {
     *   "success": false,
     *   "error": {"code": "PROFILE_REQUIRED", "message": "Complete registration before updating your profile."}
     * }
     *
     * @response 429 scenario="throttled" {
     *   "success": false,
     *   "error": {"code": "THROTTLED", "message": "Too many requests. Try again in 60 seconds."}
     * }
     */
    public function updateSection(Request $request, string $section): JsonResponse
    {
        if (! in_array($section, self::UPDATABLE_SECTIONS, true)) {
            // Same 404 as anti-enumeration path — never reveal allowed sections
            // beyond what the route's whereIn() already enforces.
            return $this->notFound();
        }

        $profile = $request->user()->profile;
        if (! $profile) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before updating your profile.',
                null,
                422,
            );
        }

        // Validate against the section's FormRequest rules. Laravel's
        // ValidationException flows through ApiExceptionHandler and comes
        // out as 422 VALIDATION_FAILED with per-field errors.
        $validated = $request->validate($this->rulesForSection($section));

        $updatedFields = $this->applySection($profile, $section, $validated);
        $pct = $this->completion->recalculate($profile);

        return ApiResponse::ok([
            'section' => $section,
            'updated_fields' => $updatedFields,
            'profile_completion_pct' => $pct,
        ]);
    }

    /**
     * Section → FormRequest rules lookup. Kept as a method so tests can
     * verify the mapping is complete.
     */
    private function rulesForSection(string $section): array
    {
        $cls = match ($section) {
            'primary' => UpdatePrimarySectionRequest::class,
            'religious' => UpdateReligiousSectionRequest::class,
            'education' => UpdateEducationSectionRequest::class,
            'family' => UpdateFamilySectionRequest::class,
            'location' => UpdateLocationSectionRequest::class,
            'contact' => UpdateContactSectionRequest::class,
            'hobbies' => UpdateHobbiesSectionRequest::class,
            'social' => UpdateSocialSectionRequest::class,
            'partner' => UpdatePartnerSectionRequest::class,
        };

        return (new $cls())->rules();
    }

    /**
     * Dispatch validated data to the right persistence path. Returns the
     * list of field names that were actually touched (in API-side naming,
     * not DB column naming — e.g. 'manglik' not 'dosh').
     */
    private function applySection(Profile $profile, string $section, array $validated): array
    {
        return match ($section) {
            'primary' => $this->applyPrimary($profile, $validated),
            'religious' => $this->applyReligious($profile, $validated),
            'hobbies' => $this->applyHobbies($profile, $validated),
            'partner' => $this->applyPartner($profile, $validated),

            // Plain HasOne relations — no transformations needed.
            'education' => $this->applyHasOne($profile, 'educationDetail', $validated),
            'family' => $this->applyHasOne($profile, 'familyDetail', $validated),
            'location' => $this->applyHasOne($profile, 'locationInfo', $validated),
            'contact' => $this->applyHasOne($profile, 'contactInfo', $validated),
            'social' => $this->applyHasOne($profile, 'socialMediaLink', $validated),
        };
    }

    /**
     * Generic updateOrCreate for HasOne relations where API field names
     * match DB columns. Used by education / family / location / contact /
     * social sections.
     */
    private function applyHasOne(Profile $profile, string $relation, array $data): array
    {
        $profile->{$relation}()->updateOrCreate(
            ['profile_id' => $profile->id],
            $data,
        );

        return array_keys($data);
    }

    /**
     * "Primary" is a split section:
     *   - Most fields (weight_kg, blood_group, …) live on profiles itself
     *   - `languages_known` lives on lifestyle_info (web controller convention)
     */
    private function applyPrimary(Profile $profile, array $validated): array
    {
        $profileFields = array_intersect_key($validated, array_flip([
            'weight_kg', 'blood_group', 'mother_tongue',
            'complexion', 'body_type', 'about_me',
        ]));

        if (! empty($profileFields)) {
            $profile->update($profileFields);
        }

        $updated = array_keys($profileFields);

        if (array_key_exists('languages_known', $validated)) {
            LifestyleInfo::updateOrCreate(
                ['profile_id' => $profile->id],
                ['languages_known' => $validated['languages_known'] ?: []],
            );
            $updated[] = 'languages_known';
        }

        return $updated;
    }

    /**
     * Religious section has one API-to-DB name translation: the API
     * uses the user-facing `manglik` field, but the column is `dosh`
     * (legacy naming). Swap before persisting.
     */
    private function applyReligious(Profile $profile, array $validated): array
    {
        $data = $validated;
        if (array_key_exists('manglik', $data)) {
            $data['dosh'] = $data['manglik'];
            unset($data['manglik']);
        }

        $profile->religiousInfo()->updateOrCreate(
            ['profile_id' => $profile->id],
            $data,
        );

        return array_keys($validated);  // API field names, not DB columns
    }

    /**
     * Hobbies section has two twists:
     *   1. Deselected array fields (omitted from payload) must be nulled
     *      so the next GET returns [], not the stale previous selection.
     *   2. `languages_known` is owned by the "primary" section — preserve
     *      it from the existing lifestyle_info row so saving hobbies
     *      doesn't wipe the user's language list.
     */
    private function applyHobbies(Profile $profile, array $validated): array
    {
        $arrayFields = [
            'hobbies', 'favorite_music', 'preferred_books',
            'preferred_movies', 'sports_fitness_games', 'favorite_cuisine',
        ];
        foreach ($arrayFields as $field) {
            if (! array_key_exists($field, $validated)) {
                $validated[$field] = null;
            }
        }

        $existing = $profile->lifestyleInfo;

        LifestyleInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            array_merge($validated, [
                'languages_known' => $existing?->languages_known,
            ]),
        );

        return array_keys($validated);
    }

    /**
     * Partner-preference arrays may contain the literal string 'Any'
     * which the web form uses for "no preference". Strip it, and null
     * any array that ends up empty so downstream matching treats the
     * field as unset rather than "must match []".
     */
    private function applyPartner(Profile $profile, array $validated): array
    {
        foreach ($validated as $key => $value) {
            if (is_array($value)) {
                $filtered = array_values(array_filter(
                    $value,
                    fn ($v) => $v !== 'Any',
                ));
                $validated[$key] = empty($filtered) ? null : $filtered;
            }
        }

        PartnerPreference::updateOrCreate(
            ['profile_id' => $profile->id],
            $validated,
        );

        return array_keys($validated);
    }

    /* ==================================================================
     |  Helpers (private + one protected seam for tests)
     | ================================================================== */

    /**
     * Look up a Profile by its matri_id. Extracted as a `protected` seam
     * so DB-free tests can override via an anonymous subclass, returning
     * a pre-built in-memory Profile without hitting the database.
     */
    protected function findTargetByMatriId(string $matriId): ?Profile
    {
        try {
            return Profile::where('matri_id', $matriId)->first();
        } catch (\Throwable $e) {
            // Missing profiles table (test env) → 404 via the same path as
            // "truly not found". Production always has the table.
            return null;
        }
    }

    /** Single NOT_FOUND response used for truly-missing + anti-enumeration paths. */
    private function notFound(): JsonResponse
    {
        return ApiResponse::error('NOT_FOUND', 'Profile not available.', null, 404);
    }

    /** Map a ProfileAccessService REASON_* to an envelope error response. */
    private function accessError(string $reason): JsonResponse
    {
        return match ($reason) {
            ProfileAccessService::REASON_SAME_GENDER => ApiResponse::error(
                'GENDER_MISMATCH',
                'Cannot view same-gender profile.',
                null,
                403,
            ),

            // 404 for blocked / hidden / suspended — anti-enumeration.
            // A malicious caller probing for real matri_ids shouldn't be able
            // to distinguish "doesn't exist" from "you can't see this".
            ProfileAccessService::REASON_BLOCKED,
            ProfileAccessService::REASON_HIDDEN,
            ProfileAccessService::REASON_SUSPENDED => $this->notFound(),

            ProfileAccessService::REASON_VISIBILITY_PREMIUM => ApiResponse::error(
                'PREMIUM_REQUIRED',
                'This profile is visible to premium members only.',
                null,
                403,
            ),

            ProfileAccessService::REASON_VISIBILITY_MATCHES => ApiResponse::error(
                'LOW_MATCH_SCORE',
                'This profile is visible to high-match members only.',
                null,
                403,
            ),

            default => $this->notFound(),  // safety net for any future reason
        };
    }

    /**
     * Compute match score for viewer → target. Returns null when the viewer
     * hasn't saved partner preferences yet (MatchingService needs them).
     */
    private function computeMatchScore(Profile $viewer, Profile $target): ?array
    {
        $prefs = $viewer->partnerPreference;
        if (! $prefs) {
            return null;
        }

        try {
            return $this->matching->calculateScore($target, $prefs);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Five-valued interest state from the viewer's perspective:
     *   'sent'      — viewer → target, still pending
     *   'received'  — target → viewer, still pending
     *   'accepted' | 'declined' | 'cancelled' | 'expired' — terminal states
     *   null        — no interest record exists either direction
     */
    private function interestStatus(Profile $viewer, Profile $target): ?string
    {
        try {
            $interest = Interest::where(function ($q) use ($viewer, $target) {
                $q->where([
                    'sender_profile_id' => $viewer->id,
                    'receiver_profile_id' => $target->id,
                ])->orWhere([
                    'sender_profile_id' => $target->id,
                    'receiver_profile_id' => $viewer->id,
                ]);
            })->latest()->first();

            if (! $interest) {
                return null;
            }

            // Terminal statuses are exposed as-is.
            if (in_array($interest->status, ['accepted', 'declined', 'cancelled', 'expired'], true)) {
                return (string) $interest->status;
            }

            // Pending → direction-aware label.
            return $interest->sender_profile_id === $viewer->id ? 'sent' : 'received';
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Has the viewer shortlisted the target? */
    private function isShortlistedBy(Profile $viewer, Profile $target): bool
    {
        try {
            return Shortlist::where('profile_id', $viewer->id)
                ->where('shortlisted_profile_id', $target->id)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Latest photo request status from viewer → target:
     *   'pending' | 'approved' | 'ignored' | null
     *
     * Flutter uses this to decide whether to show "Request Photos",
     * "Request Pending", or unblur private photos.
     */
    private function photoRequestStatus(Profile $viewer, Profile $target): ?string
    {
        try {
            $req = PhotoRequest::where('requester_profile_id', $viewer->id)
                ->where('target_profile_id', $target->id)
                ->latest()
                ->first();

            return $req?->status;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
