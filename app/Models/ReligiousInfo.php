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
