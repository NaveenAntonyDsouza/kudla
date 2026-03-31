<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationInfo extends Model
{
    protected $table = 'location_info';

    protected $fillable = [
        'profile_id',
        'country',
        'state',
        'city',
        'native_place',
        'citizenship',
        'residency_status',
        'grew_up_in',
        'is_nri',
        'outstation_leave_date_from',
        'outstation_leave_date_to',
    ];

    protected function casts(): array
    {
        return [
            'is_nri' => 'boolean',
            'outstation_leave_date_from' => 'date',
            'outstation_leave_date_to' => 'date',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
