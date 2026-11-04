<?php

namespace App\Support;

use App\Models\Profile;
use App\Services\MatchingService;
use App\Services\ProfileAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Who may see whom: one rule for every list, the profile page and the app
 * (privacy check, 4 Nov 2026).
 *
 * Before this, lists built their own queries and several skipped what members
 * chose under Settings → "Show my profile to": Discover showed 77 Kudla members
 * who chose "premium only", "matches only" or "same religion / caste / mother
 * tongue only" to every visitor; "matches only" was checked the wrong way round
 * (her against HIS preferences); an empty caste counted as "the same caste"; and
 * "same sect only" compared denomination instead of sect.
 *
 * - forVisitors(): signed-out visitors see only members who set no limit.
 * - forMember():   a member's lists: active, approved, not hidden, not suspended,
 *                  other gender, no block either way, not ignored, and every limit
 *                  the member chose that this viewer meets.
 * - limitsAllow(): the same limits in PHP, for opening one profile
 *                  (ProfileAccessService::check, which adds the interest exception).
 */
final class ProfileVisibility
{
    /** Never listed (ProfileAccessService refuses their pages too). */
    public const NOT_LISTED_STATUSES = ['suspended', 'banned'];

    /** "Only to those who match my Partner Preferences": the viewer must score at least this. */
    public const MATCH_THRESHOLD = 70;

    /** Religions whose "same … only" box compares one field (as on the Settings page). */
    private const COMMUNITY_FIELD = ['christian' => 'denomination', 'hindu' => 'caste', 'jain' => 'caste', 'muslim' => 'muslim_sect'];

    /** Active, approved, not hidden, not suspended or banned. */
    public static function live(Builder $query): Builder
    {
        return $query->where('profiles.is_active', true)
            ->where('profiles.is_approved', true)
            ->where(fn ($w) => $w->where('profiles.is_hidden', false)->orWhereNull('profiles.is_hidden'))
            ->where(fn ($w) => $w->whereNull('profiles.suspension_status')->orWhereNotIn('profiles.suspension_status', self::NOT_LISTED_STATUSES));
    }

    /** Visitors (not signed in) meet no limit, so they see only members who set none. */
    public static function forVisitors(Builder $query): Builder
    {
        return self::live($query)
            ->where(fn ($w) => $w->whereNull('profiles.show_profile_to')->orWhereNotIn('profiles.show_profile_to', ['premium', 'matches']))
            ->where(fn ($w) => $w->where('profiles.only_same_religion', false)->orWhereNull('profiles.only_same_religion'))
            ->where(fn ($w) => $w->where('profiles.only_same_denomination', false)->orWhereNull('profiles.only_same_denomination'))
            ->where(fn ($w) => $w->where('profiles.only_same_mother_tongue', false)->orWhereNull('profiles.only_same_mother_tongue'));
    }

    /** Everything a member's list needs (ProfileQueryFilters::baseQuery adds ordering and eager loads). */
    public static function forMember(Builder $query, Profile $viewer): Builder
    {
        self::live($query)
            ->where('profiles.id', '!=', $viewer->id)
            ->where('profiles.gender', '!=', $viewer->gender)
            // no block either way, and not ignored by the viewer
            ->whereDoesntHave('blockedByOthers', fn ($q) => $q->where('profile_id', $viewer->id))
            ->whereDoesntHave('blockedProfiles', fn ($q) => $q->where('blocked_profile_id', $viewer->id))
            ->whereDoesntHave('ignoredByOthers', fn ($q) => $q->where('profile_id', $viewer->id));

        return self::limitsFor($query, $viewer);
    }

    /** The limits members chose, as SQL: only the members whose limits this viewer meets. */
    public static function limitsFor(Builder $query, Profile $viewer): Builder
    {
        $ri = self::religiousInfoOf($viewer);
        $premium = self::isPremium($viewer);
        $matchIds = self::matchesAllowedIds($viewer);

        return $query
            ->where(function ($w) use ($premium, $matchIds) {
                $w->whereNull('profiles.show_profile_to')->orWhereNotIn('profiles.show_profile_to', ['premium', 'matches']);
                if ($premium) {
                    $w->orWhere('profiles.show_profile_to', 'premium');
                }
                if ($matchIds !== []) {
                    $w->orWhere(fn ($m) => $m->where('profiles.show_profile_to', 'matches')->whereIn('profiles.id', $matchIds));
                }
            })
            ->where(function ($w) use ($ri) {
                $w->where('profiles.only_same_religion', false)->orWhereNull('profiles.only_same_religion');
                if (self::norm($ri?->religion) !== '') {
                    $w->orWhereHas('religiousInfo', fn ($r) => $r->where('religion', trim((string) $ri->religion)));
                }
            })
            ->where(function ($w) use ($ri) {
                $w->where('profiles.only_same_denomination', false)->orWhereNull('profiles.only_same_denomination');
                // a viewer with no denomination, caste or sect is never "the same"
                if (self::norm($ri?->denomination) !== '' || self::norm($ri?->caste) !== '' || self::norm($ri?->muslim_sect) !== '') {
                    $w->orWhereHas('religiousInfo', fn ($r) => self::sameCommunitySql($r, $ri));
                }
            })
            ->where(function ($w) use ($viewer) {
                $w->where('profiles.only_same_mother_tongue', false)->orWhereNull('profiles.only_same_mother_tongue');
                if (self::norm($viewer->mother_tongue) !== '') {
                    $w->orWhere('profiles.mother_tongue', trim((string) $viewer->mother_tongue));
                }
            });
    }

    /**
     * The limits the target chose, in PHP: null when this viewer meets them, else the
     * ProfileAccessService reason. Same rules as limitsFor().
     */
    public static function limitsAllow(Profile $viewer, Profile $target): ?string
    {
        $show = $target->show_profile_to ?? 'all';
        if ($show === 'premium' && ! self::isPremium($viewer)) {
            return ProfileAccessService::REASON_VISIBILITY_PREMIUM;
        }
        if ($show === 'matches' && ! self::viewerMatches($viewer, $target)) {
            return ProfileAccessService::REASON_VISIBILITY_MATCHES;
        }
        if (! self::communityAllows($viewer, $target)) {
            return ProfileAccessService::REASON_VISIBILITY_COMMUNITY;
        }

        return null;
    }

    /** "Same religion / denomination / caste / sect / mother tongue only": an empty value never counts as the same. */
    public static function communityAllows(Profile $viewer, Profile $target): bool
    {
        if (! $target->only_same_religion && ! $target->only_same_denomination && ! $target->only_same_mother_tongue) {
            return true;
        }
        $v = self::religiousInfoOf($viewer);
        $t = self::religiousInfoOf($target);

        if ($target->only_same_religion && ! self::same($v?->religion, $t?->religion)) {
            return false;
        }
        if ($target->only_same_denomination) {
            $field = self::COMMUNITY_FIELD[self::norm($t?->religion)] ?? null;
            $ok = $field
                ? self::same($v?->{$field}, $t?->{$field})
                : self::same($v?->denomination, $t?->denomination) || self::same($v?->caste, $t?->caste);
            if (! $ok) {
                return false;
            }
        }
        if ($target->only_same_mother_tongue && ! self::same($viewer->mother_tongue, $target->mother_tongue)) {
            return false;
        }

        return true;
    }

    /**
     * Does the VIEWER match the TARGET's partner preferences? With nothing saved,
     * nobody can be said to match; with every preference "Any", everyone does.
     */
    public static function viewerMatches(Profile $viewer, Profile $target): bool
    {
        $prefs = $target->partnerPreference;
        if (! $prefs) {
            return false;
        }

        try {
            $result = app(MatchingService::class)->calculateScore($viewer, $prefs);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        return ($result['breakdown'] ?? []) === [] || (int) ($result['score'] ?? 0) >= self::MATCH_THRESHOLD;
    }

    /** The "matches only" members this viewer matches (ids), cached 10 minutes per viewer. */
    public static function matchesAllowedIds(Profile $viewer): array
    {
        try {
            return Cache::remember("visibility.matches.{$viewer->id}", 600, fn () => Profile::query()
                ->where('show_profile_to', 'matches')
                ->where('gender', '!=', $viewer->gender)
                ->where('is_active', true)
                ->with('partnerPreference')
                ->get()
                ->filter(fn (Profile $t) => self::viewerMatches($viewer, $t))
                ->pluck('id')->values()->all());
        } catch (\Throwable $e) {
            report($e);

            return []; // can't tell: "matches only" members stay out of the list
        }
    }

    /** Keep only the profiles this viewer may open (for short lists built in PHP). */
    public static function visibleTo(Profile $viewer, iterable $profiles): Collection
    {
        $access = app(ProfileAccessService::class);

        return collect($profiles)->filter(fn ($p) => $p instanceof Profile && $access->canAccess($viewer, $p))->values();
    }

    private static function sameCommunitySql(Builder $r, $ri): void
    {
        $d = trim((string) $ri?->denomination);
        $c = trim((string) $ri?->caste);
        $s = trim((string) $ri?->muslim_sect);
        $known = array_map('ucfirst', array_keys(self::COMMUNITY_FIELD));

        $r->where(function ($any) use ($d, $c, $s, $known) {
            $any->whereRaw('1 = 0');
            if ($d !== '') {
                $any->orWhere(fn ($x) => $x->where('religion', 'Christian')->where('denomination', $d));
            }
            if ($c !== '') {
                $any->orWhere(fn ($x) => $x->whereIn('religion', ['Hindu', 'Jain'])->where('caste', $c));
            }
            if ($s !== '') {
                $any->orWhere(fn ($x) => $x->where('religion', 'Muslim')->where('muslim_sect', $s));
            }
            // any other religion: caste or denomination
            $other = fn ($x) => $x->where(fn ($y) => $y->whereNull('religion')->orWhereNotIn('religion', $known));
            if ($d !== '') {
                $any->orWhere(fn ($x) => $other($x)->where('denomination', $d));
            }
            if ($c !== '') {
                $any->orWhere(fn ($x) => $other($x)->where('caste', $c));
            }
        });
    }

    /** The member's religion details, or null (none saved, or a test setup without the table). */
    private static function religiousInfoOf(Profile $profile)
    {
        try {
            return $profile->religiousInfo;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function isPremium(Profile $viewer): bool
    {
        try {
            return (bool) $viewer->user?->isPremium();
        } catch (\Throwable) {
            return false;
        }
    }

    private static function norm($value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private static function same($a, $b): bool
    {
        $a = self::norm($a);

        return $a !== '' && $a === self::norm($b);
    }
}
