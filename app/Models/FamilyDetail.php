<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyDetail extends Model
{
    protected $fillable = [
        'profile_id',
        'father_name',
        'father_occupation',
        'mother_name',
        'mother_occupation',
        'family_type',
        'family_values',
        'family_status',
        'num_brothers',
        'brothers_married',
        'num_sisters',
        'sisters_married',
        'family_living_in',
        'about_family',
        'father_house_name',
        'father_native_place',
        'mother_house_name',
        'mother_native_place',
        'brothers_unmarried',
        'brothers_priest',
        'sisters_unmarried',
        'sisters_nun',
        'candidate_asset_details',
        'about_candidate_family',
    ];

    protected function casts(): array
    {
        return [
            'num_brothers' => 'integer',
            'brothers_married' => 'integer',
            'num_sisters' => 'integer',
            'sisters_married' => 'integer',
            'brothers_unmarried' => 'integer',
            'brothers_priest' => 'integer',
            'sisters_unmarried' => 'integer',
            'sisters_nun' => 'integer',
        ];
    }

    /**
     * The sibling-count columns are NOT NULL DEFAULT 0 in the schema, but forms
     * submit blank counts as NULL (via ConvertEmptyStringsToNull). An explicit
     * NULL into a NOT NULL column errors regardless of SQL strict mode, so coerce
     * these to 0 on every save (admin edit, registration, API).
     */
    protected static function booted(): void
    {
        static::saving(function (self $family): void {
            foreach ([
                'num_brothers', 'brothers_married', 'brothers_unmarried', 'brothers_priest',
                'num_sisters', 'sisters_married', 'sisters_unmarried', 'sisters_nun',
            ] as $column) {
                if (is_null($family->{$column})) {
                    $family->{$column} = 0;
                }
            }
        });
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
