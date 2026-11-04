<?php

namespace Tests\PrivacyBench;

use App\Models\BlockedProfile;
use App\Models\IdProof;
use App\Models\IgnoredProfile;
use App\Models\Interest;
use App\Models\PartnerPreference;
use App\Models\PhotoPrivacySetting;
use App\Models\PhotoRequest;
use App\Models\Profile;
use App\Models\ProfileNote;
use App\Models\ProfilePhoto;
use App\Models\Shortlist;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\InterestService;
use App\Services\MemberCreationService;
use App\Services\ProfileAccessService;
use App\Support\PhotoVisibility;
use Illuminate\Support\Facades\DB;

/**
 * Privacy test-member bench (Nov 2026).
 *
 * Builds ten test members whose private details are unique "canary" values, and the
 * interactions between them (interests, messages, blocks, views, photo requests),
 * through the app's own code. scan() then looks for every member's canaries in a page,
 * an app answer or an email produced for one viewer, and reports each one that viewer
 * may not see:
 *   never   — reference contact, creator's number, present address, ID proof (number and
 *             file), staff note, horoscope file, original photo: the member only
 *   contact — phones, WhatsApp, emails, address, contact person, social link: the member,
 *             or a viewer the contact rule opens to (ProfileAccessService::canViewContact)
 *   message — an interest message: the two members of that interest
 *   photo   — a photo URL: whoever PhotoVisibility shows it to
 * listingViolations() checks every Matri ID in the output: the viewer must be allowed
 * to see that member, or have a reason to know them (an interest, a block, an ignore,
 * a photo request).
 */
final class Bench
{
    /** key => [n, gender, religion fields, mother tongue, profile overrides, photo privacy] */
    private const MEMBERS = [
        'T' => [1, 'female', ['religion' => 'Christian', 'denomination' => 'Roman Catholic'], 'Konkani', [], 'visible_to_all'],
        'TP' => [2, 'female', ['religion' => 'Christian', 'denomination' => 'Roman Catholic'], 'Konkani', [], 'interest_accepted'],
        'TH' => [3, 'female', ['religion' => 'Christian', 'denomination' => 'Roman Catholic'], 'Konkani', ['is_hidden' => true], 'visible_to_all'],
        'TL' => [4, 'female', ['religion' => 'Hindu', 'caste' => 'Billava'], 'Tulu', ['only_same_mother_tongue' => true], 'visible_to_all'],
        'V' => [5, 'male', ['religion' => 'Christian', 'denomination' => 'Roman Catholic'], 'Konkani', [], 'visible_to_all'],
        'VA' => [6, 'male', ['religion' => 'Christian', 'denomination' => 'Roman Catholic'], 'Konkani', [], 'visible_to_all'],
        'VS' => [7, 'male', ['religion' => 'Christian', 'denomination' => 'Roman Catholic'], 'Konkani', [], 'visible_to_all'],
        'VB' => [8, 'male', ['religion' => 'Christian', 'denomination' => 'Roman Catholic'], 'Konkani', [], 'visible_to_all'],
        'C' => [9, 'male', ['religion' => 'Christian', 'denomination' => 'Roman Catholic'], 'Konkani', [], 'visible_to_all'],
        'W' => [10, 'female', ['religion' => 'Christian', 'denomination' => 'Roman Catholic'], 'Konkani', [], 'visible_to_all'],
    ];

    /** @var array<string, Profile> */
    public array $m = [];

    /** canary value => [owner profile id, class, participant ids for messages] */
    public array $canaries = [];

    /** @var array<string, Interest> */
    public array $interests = [];

    public User $staff;

    public static function guard(): void
    {
        $db = DB::connection()->getDatabaseName();
        if ($db !== 'matrimony_privacy_bench') {
            throw new \RuntimeException("The privacy bench refuses to run on database '{$db}'.");
        }
    }

    public static function phone(int $n, int $k): string
    {
        return sprintf('97%02d00000%d', $n, $k);
    }

    public static function build(): self
    {
        self::guard();
        $b = new self;
        SiteSetting::setValue('free_membership_enabled', '1'); // like Kudla: everyone has full access
        SiteSetting::setValue('weekly_matches_min_score', '0');

        $b->staff = User::create(['name' => 'Bench Staff', 'email' => 'bench-staff@bench.test', 'password' => bcrypt('bench-staff-pass'), 'role' => 'admin', 'is_active' => true, 'email_verified_at' => now()]);

        foreach (self::MEMBERS as $key => [$n, $gender, $religion, $tongue, $overrides, $photoPrivacy]) {
            $b->m[$key] = $b->member($key, $n, $gender, $religion, $tongue, $overrides, $photoPrivacy);
        }

        $svc = app(InterestService::class);
        $send = function (string $from, string $to, string $text) use ($b, $svc) {
            $i = $svc->send($b->m[$from], $b->m[$to], null, $text);
            $b->message($text, $b->m[$from], $b->m[$to]);

            return $i;
        };

        // TH (hidden) writes to V first, before she hid: V may see her
        $b->m['TH']->update(['is_hidden' => false]);
        $b->interests['TH_V'] = $send('TH', 'V', 'CNYMSG0305A hello from three');
        $b->m['TH']->update(['is_hidden' => true]);

        // VA <-> T accepted, with a chat
        $b->interests['VA_T'] = $send('VA', 'T', 'CNYMSG0601A first note');
        $svc->accept($b->interests['VA_T']->fresh(), null, 'CNYMSG0106B accepted note');
        $b->message('CNYMSG0106B accepted note', $b->m['T'], $b->m['VA']);
        foreach ([['VA', 'CNYMSG0601C chat from six'], ['T', 'CNYMSG0106D chat from one']] as [$who, $text]) {
            $svc->sendMessage($b->interests['VA_T']->fresh(), $b->m[$who], $text);
            $b->message($text, $b->m['T'], $b->m['VA']);
        }

        // C <-> T accepted, with a chat (C is the third member nobody else may read)
        $b->interests['C_T'] = $send('C', 'T', 'CNYMSG0901A note from nine');
        $svc->accept($b->interests['C_T']->fresh(), null, null);
        $svc->sendMessage($b->interests['C_T']->fresh(), $b->m['T'], 'CNYMSG0109C chat to nine');
        $b->message('CNYMSG0109C chat to nine', $b->m['T'], $b->m['C']);

        // VS -> T pending; TL -> V declined later; C -> TP pending
        $b->interests['VS_T'] = $send('VS', 'T', 'CNYMSG0701A pending note');
        $b->interests['C_TP'] = $send('C', 'TP', 'CNYMSG0902A note to two');

        // T blocked VB; V ignored nobody; V shortlisted T and TH (TH hid afterwards)
        BlockedProfile::create(['profile_id' => $b->m['T']->id, 'blocked_profile_id' => $b->m['VB']->id]);
        Shortlist::create(['profile_id' => $b->m['V']->id, 'shortlisted_profile_id' => $b->m['T']->id]);
        Shortlist::create(['profile_id' => $b->m['V']->id, 'shortlisted_profile_id' => $b->m['TH']->id]);

        // views: C viewed T; TH (hidden) and TL (same mother tongue only) viewed V
        foreach ([['C', 'T'], ['TH', 'V'], ['TL', 'V'], ['V', 'T'], ['VB', 'T']] as [$viewer, $viewed]) {
            \App\Http\Controllers\ProfileViewController::track($b->m[$viewer]->id, $b->m[$viewed]->id);
        }

        // V asked TP for photo access
        PhotoRequest::create(['requester_profile_id' => $b->m['V']->id, 'target_profile_id' => $b->m['TP']->id, 'status' => 'pending']);

        PhotoVisibility::invalidate();

        return $b;
    }

    private function member(string $key, int $n, string $gender, array $religion, string $tongue, array $overrides, string $photoPrivacy): Profile
    {
        $nn = sprintf('%02d', $n);
        $created = app(MemberCreationService::class)->create(array_merge([
            'full_name' => "Bench {$key}",
            'gender' => $gender,
            'date_of_birth' => $gender === 'female' ? '1996-04-04' : '1993-02-02',
            'email' => "cny{$nn}login@bench.test",
            'phone' => self::phone($n, 1),
            'mother_tongue' => $tongue,
            'whatsapp_number' => self::phone($n, 3),
            'contact_person' => "Cny{$nn}Person",
            'contact_relationship' => 'Father',
            'preferred_call_time' => 'Evening',
            'communication_address' => "CNY{$nn}ADDRESS Lane",
            'reference_name' => "CNY{$nn}REFNAME",
            'reference_mobile' => self::phone($n, 6),
            'instagram_url' => "https://instagram.com/cny{$nn}insta",
            'native_country' => 'India', 'native_state' => 'Karnataka', 'native_district' => 'Dakshina Kannada',
            'about_me' => "Bench member {$nn} likes music.",
            'created_by' => 'Self',
        ], $religion), ['temp_password' => 'bench-pass-123']);

        /** @var Profile $p */
        $p = $created['profile'];
        $p->user->forceFill(['last_login_at' => now()->subDay()])->save();
        $p->contactInfo->update([
            'primary_phone' => self::phone($n, 2), 'secondary_phone' => self::phone($n, 4), 'residential_phone_number' => self::phone($n, 5),
            'email' => "cny{$nn}contact@bench.test", 'alternate_email' => "cny{$nn}alt@bench.test", 'present_address' => "CNY{$nn}PRESENT Road",
        ]);
        $p->forceFill(array_merge(['creator_name' => "CNY{$nn}CREATOR", 'creator_contact_number' => self::phone($n, 7)], $overrides))->save();
        $p->religiousInfo?->update(['jathakam_upload_url' => "jathakam/cny{$nn}jathakam.pdf"]);
        PartnerPreference::updateOrCreate(['profile_id' => $p->id], ['age_from' => 20, 'age_to' => 45]);
        IdProof::create(['profile_id' => $p->id, 'document_type' => 'Aadhaar', 'document_number' => "CNY{$nn}DOCNUM", 'document_url' => "id-proofs/{$p->id}/cny{$nn}idproof.jpg", 'verification_status' => 'approved']);
        ProfileNote::create(['profile_id' => $p->id, 'admin_user_id' => $this->staff->id, 'note_type' => 'general', 'note' => "CNY{$nn}STAFFNOTE call later"]);
        ProfilePhoto::create([
            'profile_id' => $p->id, 'photo_type' => 'profile', 'storage_driver' => 'public', 'is_primary' => true, 'is_visible' => true, 'approval_status' => 'approved',
            'photo_url' => "photos/{$p->id}/full/cny{$nn}photo.webp", 'medium_url' => "photos/{$p->id}/medium/cny{$nn}photo.webp",
            'thumbnail_url' => "photos/{$p->id}/thumb/cny{$nn}photo.webp", 'original_url' => "photos/{$p->id}/original/cny{$nn}orig.jpg",
        ]);
        PhotoPrivacySetting::updateOrCreate(['profile_id' => $p->id], ['privacy_level' => $photoPrivacy, 'profile_photo_privacy' => $photoPrivacy]);

        foreach ([6, 7] as $k) {
            $this->canary(self::phone($n, $k), $p, 'never');
        }
        foreach (["CNY{$nn}REFNAME", "CNY{$nn}PRESENT", "CNY{$nn}CREATOR", "CNY{$nn}DOCNUM", "cny{$nn}idproof", "CNY{$nn}STAFFNOTE", "cny{$nn}jathakam", "cny{$nn}orig"] as $v) {
            $this->canary($v, $p, 'never');
        }
        foreach ([1, 2, 3, 4, 5] as $k) {
            $this->canary(self::phone($n, $k), $p, 'contact');
        }
        foreach (["cny{$nn}login@", "cny{$nn}contact@", "cny{$nn}alt@", "CNY{$nn}ADDRESS", "Cny{$nn}Person", "cny{$nn}insta"] as $v) {
            $this->canary($v, $p, 'contact');
        }
        $this->canary("cny{$nn}photo.webp", $p, 'photo');

        return $p->fresh();
    }

    private function canary(string $value, Profile $owner, string $class, array $participants = []): void
    {
        $this->canaries[$value] = [$owner->id, $class, $participants];
    }

    private function message(string $text, Profile $a, Profile $b): void
    {
        // the marker word (CNYMSG + 4 digits + letter) is enough to find it
        $this->canary(strtok($text, ' '), $a, 'message', [$a->id, $b->id]);
    }

    public function keyOf(int $profileId): string
    {
        foreach ($this->m as $key => $p) {
            if ($p->id === $profileId) {
                return $key;
            }
        }

        return "#{$profileId}";
    }

    /** Canary leaks in one output produced for $viewer (null = a visitor). */
    public function scan(string $body, ?Profile $viewer, string $surface): array
    {
        $flat = preg_replace('/\D/', '', $body);
        $lower = mb_strtolower($body);
        $out = [];
        foreach ($this->canaries as $value => [$ownerId, $class, $participants]) {
            $hit = ctype_digit((string) $value) ? str_contains($flat, (string) $value) : str_contains($lower, mb_strtolower((string) $value));
            if ($hit && ! $this->allowed($viewer, $ownerId, $class, $participants)) {
                $out[] = sprintf('%s — %s sees %s %s detail (%s)', $surface, $viewer ? $this->keyOf($viewer->id) : 'visitor', $this->keyOf($ownerId), $class, $value);
            }
        }

        return $out;
    }

    private function allowed(?Profile $viewer, int $ownerId, string $class, array $participants): bool
    {
        if ($viewer && $viewer->id === $ownerId) {
            return true;
        }
        $owner = Profile::find($ownerId);

        return match ($class) {
            'never' => false,
            'contact' => $viewer !== null && app(ProfileAccessService::class)->canViewContact($viewer->fresh(), $owner),
            'message' => $viewer !== null && in_array($viewer->id, $participants, true),
            'photo' => (PhotoVisibility::invalidate() || true) && PhotoVisibility::url($owner, $viewer?->fresh()) !== null,
            default => false,
        };
    }

    /** Members named in an output the viewer has no reason to know. */
    public function listingViolations(string $body, ?Profile $viewer, string $surface, bool $listOnly = false): array
    {
        // a list page: the members in its cards (links to their profiles), not the header's
        // notifications; any other page: every Matri ID on it
        preg_match_all($listOnly ? '#/profile/([A-Z]{2,4}\d{5,})\b#' : '#\b([A-Z]{2,4}\d{5,})\b#', $body, $m);
        $out = [];
        foreach (array_unique($m[1]) as $matri) {
            $t = Profile::where('matri_id', $matri)->first();
            if (! $t || ! in_array($t->id, array_map(fn ($p) => $p->id, $this->m), true) || ($viewer && $t->id === $viewer->id)) {
                continue;
            }
            if (str_contains($surface, $matri)) {
                continue; // the member the viewer asked for by address (e.g. /profile/ID, /report/ID)
            }
            if (! $this->mayKnow($viewer, $t, $listOnly)) {
                $out[] = sprintf('%s — %s is shown %s', $surface, $viewer ? $this->keyOf($viewer->id) : 'visitor', $this->keyOf($t->id));
            }
        }

        return $out;
    }

    private function mayKnow(?Profile $viewer, Profile $t, bool $listOnly): bool
    {
        if (! $viewer) {
            return \App\Support\ProfileVisibility::forVisitors(Profile::query())->whereKey($t->id)->exists();
        }
        $access = app(ProfileAccessService::class);
        if ($listOnly) {
            return \App\Support\ProfileVisibility::forMember(Profile::query(), $viewer->fresh())->whereKey($t->id)->exists();
        }

        return $access->canAccess($viewer->fresh(), $t)
            || $access->hasAnyInterest($viewer, $t)
            || BlockedProfile::where('profile_id', $viewer->id)->where('blocked_profile_id', $t->id)->exists()
            || IgnoredProfile::where('profile_id', $viewer->id)->where('ignored_profile_id', $t->id)->exists()
            || PhotoRequest::where(fn ($q) => $q->where('requester_profile_id', $viewer->id)->where('target_profile_id', $t->id))
                ->orWhere(fn ($q) => $q->where('requester_profile_id', $t->id)->where('target_profile_id', $viewer->id))->exists();
    }
}
