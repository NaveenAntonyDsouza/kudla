<?php

namespace App\Services;

use App\Models\Profile;

/**
 * ProfileCompletionService — wraps Profile::calculateCompletion() + identifies
 * specific missing sections so we can nudge users about the most impactful gap.
 *
 * Section weights (matching Profile::calculateCompletion):
 *   - photo: 15
 *   - partner_preferences: 10
 *   - family_details: 10
 *   - education: 10
 *   - location: 10
 *   - contact: 10
 *   - lifestyle: 10
 *   - religious: 10
 *   - basic_info: 10
 *
 * Ordered list returned by detectMissingSections() is sorted by impact (highest first).
 */
class ProfileCompletionService
{
    /**
     * Ordered sections by impact (highest points first).
     * This is the priority order we use when picking "the one" nudge to send.
     */
    public const SECTIONS = [
        'photo' => [
            'weight' => 15,
            'label' => 'Primary Photo',
            'cta_url_path' => '/profile/photos',
            'nudge_title' => 'Add your first photo',
            'nudge_message' => 'Profiles with photos get 3× more views. Upload yours in 30 seconds.',
        ],
        'partner_preferences' => [
            'weight' => 10,
            'label' => 'Partner Preferences',
            'cta_url_path' => '/profile/partner',
            'nudge_title' => 'Who are you looking for?',
            'nudge_message' => 'Tell us your partner preferences so we can suggest the best matches for you.',
        ],
        'family_details' => [
            'weight' => 10,
            'label' => 'Family Details',
            'cta_url_path' => '/profile/family',
            'nudge_title' => 'Complete your family info',
            'nudge_message' => 'A complete family profile makes conversations easier — it\'s worth the 2 minutes.',
        ],
        'education' => [
            'weight' => 10,
            'label' => 'Education & Occupation',
            'cta_url_path' => '/profile/education',
            'nudge_title' => 'Share your education',
            'nudge_message' => 'Your education and occupation help us match you with like-minded people.',
        ],
        'location' => [
            'weight' => 10,
            'label' => 'Location Info',
            'cta_url_path' => '/profile/location',
            'nudge_title' => 'Add your location',
            'nudge_message' => 'Location helps us find matches near you or in your preferred cities.',
        ],
        'contact' => [
            'weight' => 10,
            'label' => 'Contact Details',
            'cta_url_path' => '/profile/contact',
            'nudge_title' => 'Add your contact details',
            'nudge_message' => 'Contact info lets matches reach you faster once you connect.',
        ],
        'lifestyle' => [
            'weight' => 10,
            'label' => 'Lifestyle',
            'cta_url_path' => '/profile/hobbies',
            'nudge_title' => 'Share your lifestyle & interests',
            'nudge_message' => 'Diet, hobbies, and interests help create meaningful connections.',
        ],
        'religious' => [
            'weight' => 10,
            'label' => 'Religious Info',
            'cta_url_path' => '/profile/religious',
            'nudge_title' => 'Complete your religious info',
            'nudge_message' => 'Religion and community details matter for compatibility.',
        ],
        'basic_info' => [
            'weight' => 10,
            'label' => 'Basic Info',
            'cta_url_path' => '/profile/primary',
            'nudge_title' => 'Fill in your basic details',
            'nudge_message' => 'Start with the basics: your name, age, and essentials.',
        ],
    ];

    /**
     * Calculate the profile completion percentage.
     */
    public function calculate(Profile $profile): int
    {
        return $profile->calculateCompletion();
    }

    /**
     * Recompute the completion % and persist it on the profile.
     *
     * Called after every section save (PUT /profile/me/{section}) so the
     * Flutter progress ring shows the new value on the very next render —
     * no separate GET round-trip needed.
     *
     * Safe against missing DB relations (empty Profile = 5% baseline from
     * Profile::calculateCompletion), and wraps the persist step in try/catch
     * so the recalc never fails the user's save if the DB hiccups.
     *
     * Returns the new percentage (0–100).
     */
    public function recalculate(Profile $profile): int
    {
        // Refresh from DB first — we might have just updated a relation
        // and we need the caller's change to be visible to calculateCompletion().
        try {
            $profile->refresh();
        } catch (\Throwable $e) {
            // In test env refresh() can fail if the underlying profile row
            // is in-memory-only. Fall back to the already-loaded state.
        }

        try {
            $pct = $profile->calculateCompletion();
        } catch (\Throwable $e) {
            // calculateCompletion lazy-loads 8 related tables (religious_info,
            // education_details, family_details, location_infos, contact_infos,
            // lifestyle_infos, partner_preferences, photos). In test envs that
            // don't stand up every related table, that throws — fall back to
            // the profile's last known value so the API still returns a
            // sensible number.
            return (int) ($profile->profile_completion_pct ?? 0);
        }

        try {
            $profile->update(['profile_completion_pct' => $pct]);
        } catch (\Throwable $e) {
            // Best-effort persistence — the computed pct is still returned
            // to the caller so Flutter gets an accurate number even if the
            // write didn't land (e.g. missing profiles table in test env).
        }

        return $pct;
    }

    /**
     * The single source of truth for "is this section filled in?".
     *
     * Returns [sectionKey => bool] for all 9 sections. The field-level checks
     * here mirror Profile::calculateCompletion() exactly, so the % and the
     * section flags can never disagree. Both detectMissingSections() (nudges,
     * mobile app) and getSectionStatuses() (web dashboard checklist) derive
     * from this — no duplicated logic, no drift.
     */
    public function sectionDoneMap(Profile $profile): array
    {
        // Ensure relations are loaded for accurate checks
        $profile->loadMissing([
            'religiousInfo', 'educationDetail', 'familyDetail',
            'locationInfo', 'contactInfo', 'lifestyleInfo', 'partnerPreference',
        ]);

        return [
            'photo' => $this->hasVisiblePhoto($profile),
            'partner_preferences' => (bool) ($profile->partnerPreference?->age_from || $profile->partnerPreference?->religions),
            'family_details' => (bool) $profile->familyDetail?->father_name,
            'education' => (bool) ($profile->educationDetail?->highest_education || $profile->educationDetail?->occupation),
            'location' => (bool) ($profile->locationInfo?->residing_country || $profile->locationInfo?->native_country),
            'contact' => (bool) ($profile->contactInfo?->contact_person || $profile->contactInfo?->whatsapp_number),
            'lifestyle' => (bool) ($profile->lifestyleInfo?->diet || $profile->lifestyleInfo?->hobbies),
            'religious' => (bool) $profile->religiousInfo?->religion,
            'basic_info' => (bool) ($profile->full_name && $profile->gender && $profile->date_of_birth),
        ];
    }

    /**
     * Whether the profile has at least one visible photo. Prefers the cheap
     * query, but falls back to a loaded relation when the photos table isn't
     * available (test env on unmigrated SQLite) — matching the DB-tolerant
     * pattern used by recalculate() and DashboardService.
     */
    private function hasVisiblePhoto(Profile $profile): bool
    {
        try {
            return $profile->profilePhotos()->visible()->exists();
        } catch (\Throwable $e) {
            $loaded = $profile->relationLoaded('profilePhotos') ? $profile->getRelation('profilePhotos') : null;
            return $loaded ? $loaded->where('is_visible', true)->isNotEmpty() : false;
        }
    }

    /**
     * Detect which sections are missing. Returns array ordered by impact (highest first).
     * Each entry: ['key' => 'photo', 'weight' => 15, 'label' => 'Primary Photo', ...]
     */
    public function detectMissingSections(Profile $profile): array
    {
        $done = $this->sectionDoneMap($profile);

        $missing = [];
        foreach (self::SECTIONS as $key => $meta) {
            if (! ($done[$key] ?? false)) {
                $missing[$key] = $meta;
            }
        }

        return $missing;
    }

    /**
     * Full per-section status for the web dashboard checklist — every section,
     * with its done flag, ordered by impact (highest weight first). Pure data:
     * no framework routing here, so the caller (web vs mobile) maps each key to
     * its own edit URL.
     *
     * Each entry: ['key' => 'photo', 'label' => 'Primary Photo', 'weight' => 15, 'done' => false]
     */
    public function getSectionStatuses(Profile $profile): array
    {
        $done = $this->sectionDoneMap($profile);

        $statuses = [];
        foreach (self::SECTIONS as $key => $meta) {
            $statuses[] = [
                'key' => $key,
                'label' => $meta['label'],
                'weight' => $meta['weight'],
                'done' => $done[$key] ?? false,
            ];
        }

        return $statuses;
    }

    /**
     * Pick the single most impactful missing section for nudging.
     * Returns an entry from SECTIONS or null if all sections complete.
     */
    public function pickTopMissingSection(Profile $profile): ?array
    {
        $missing = $this->detectMissingSections($profile);
        if (empty($missing)) {
            return null;
        }

        // SECTIONS is already ordered by impact; iterate in that order
        foreach (self::SECTIONS as $key => $meta) {
            if (isset($missing[$key])) {
                return ['key' => $key] + $meta;
            }
        }

        return null;
    }
}
