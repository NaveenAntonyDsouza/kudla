<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReligiousInfo extends Model
{
    protected $table = 'religious_info';

    protected $fillable = [
        'profile_id',
        'religion',
        'caste',
        'other_caste_name',
        'sub_caste',
        'gotra',
        'nakshatra',
        'rashi',
        'dosh',
        'denomination',
        'other_denomination_name',
        'diocese',
        'diocese_name',
        'parish_name_place',
        'time_of_birth',
        'place_of_birth',
        'jathakam_upload_url',
        // 12 Nov 2026: missing here, so the admin's Approve / Reject on horoscopes and
        // baptism certificates saved nothing (the member still got the notice)
        'jathakam_approval_status',
        'jathakam_rejection_reason',
        'jathakam_approved_by',
        'jathakam_approved_at',
        'muslim_sect',
        'muslim_community',
        'religious_observance',
        'jain_sect',
        'other_religion_name',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Display-friendly denomination: returns the typed specify text when the
     * member picked "Other", else the canonical denomination. Use this in
     * compact contexts (cards, list rows, summary lines) where you want
     * "Coptic Catholic" instead of a bare "Other". Detailed views (full
     * profile, edit) keep showing both rows so the categorical distinction
     * remains visible.
     */
    public function getDisplayDenominationAttribute(): ?string
    {
        if ($this->denomination === 'Other' && $this->other_denomination_name) {
            return $this->other_denomination_name;
        }
        return $this->denomination;
    }

    /**
     * Every spelling of the "Other" caste sentinel across the app's data
     * sources: the Communities table seeds it as "Other / Not Listed", the
     * config caste_list uses "Other", and earlier UI used "Other (not listed)".
     * Match all so the Specify box + display work regardless of source.
     */
    public const OTHER_CASTE_VALUES = ['Other / Not Listed', 'Other (not listed)', 'Other'];

    /** True when $value is any "Other" caste sentinel. */
    public static function isOtherCaste(?string $value): bool
    {
        return in_array($value, self::OTHER_CASTE_VALUES, true);
    }

    /**
     * Typed-in answers and the pick they belong to: [typed column => [pick column, its "Other" values]].
     * A typed answer waits for a list entry only while its pick is still "Other" (or empty): once
     * staff pick a listed value it is answered (6 Nov 2026: Kudla still listed 3 such members on
     * Unlisted Communities, the Work Queue and the members-list filter).
     */
    public const TYPED_ENTRIES = [
        'other_caste_name' => ['caste', self::OTHER_CASTE_VALUES],
        'other_religion_name' => ['religion', ['Other']],
        'other_denomination_name' => ['denomination', ['Other']],
    ];

    /** Rows whose typed $typedColumn still waits for a list entry (see TYPED_ENTRIES). */
    public function scopeUnlistedIn(\Illuminate\Database\Eloquent\Builder $query, string $typedColumn): \Illuminate\Database\Eloquent\Builder
    {
        [$pick, $otherValues] = self::TYPED_ENTRIES[$typedColumn];

        return $query->whereNotNull($typedColumn)->where($typedColumn, '!=', '')
            ->where(fn ($w) => $w->whereNull($pick)->orWhere($pick, '')->orWhereIn($pick, $otherValues));
    }

    /** Rows with any typed caste / religion / denomination still waiting for a list entry. */
    public function scopeWithUnlistedEntry(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function ($w) {
            foreach (array_keys(self::TYPED_ENTRIES) as $typedColumn) {
                $w->orWhere(fn ($o) => $o->unlistedIn($typedColumn));
            }
        });
    }

    /**
     * Display-friendly caste: returns the typed specify text when the member
     * picked an "Other" sentinel, else the canonical caste. Use in compact
     * contexts (cards, list rows) where you want "Mogaveera" not "Other".
     */
    public function getDisplayCasteAttribute(): ?string
    {
        if (self::isOtherCaste($this->caste) && $this->other_caste_name) {
            return $this->other_caste_name;
        }
        return $this->caste;
    }

    /**
     * Map of religion-specific columns to the religion(s) they belong to.
     * Columns NOT listed here (religion, time_of_birth, place_of_birth,
     * jathakam_upload_url) are religion-agnostic and never cleared.
     *
     * @var array<string, array<int, string>>
     */
    public const RELIGION_FIELDS = [
        'caste' => ['Hindu', 'Jain'],
        'other_caste_name' => ['Hindu', 'Jain'],
        'sub_caste' => ['Hindu', 'Jain'],
        'gotra' => ['Hindu', 'Jain'],
        'nakshatra' => ['Hindu', 'Jain'],
        'rashi' => ['Hindu', 'Jain'],
        'dosh' => ['Hindu', 'Jain'],
        'jain_sect' => ['Jain'],
        'denomination' => ['Christian'],
        'other_denomination_name' => ['Christian'],
        'diocese' => ['Christian'],
        'diocese_name' => ['Christian'],
        'parish_name_place' => ['Christian'],
        'muslim_sect' => ['Muslim'],
        'muslim_community' => ['Muslim'],
        'religious_observance' => ['Muslim'],
        'other_religion_name' => ['Other'],
    ];

    /**
     * Null out any religion-specific field that doesn't belong to the given
     * religion, so changing a profile's religion never leaves stale data
     * behind (e.g. an old caste after a Hindu → Christian switch). Mutates
     * and returns the attribute array that's about to be saved; keys for
     * mismatched fields are explicitly set to null so updateOrCreate clears
     * them even when the form didn't submit that field.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function clearFieldsForReligion(array $data, ?string $religion): array
    {
        foreach (self::RELIGION_FIELDS as $field => $religions) {
            if (! in_array($religion, $religions, true)) {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
