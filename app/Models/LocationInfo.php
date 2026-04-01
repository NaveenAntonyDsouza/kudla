<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationInfo extends Model
{
    protected $table = 'location_info';

    protected $fillable = [
        'profile_id',
        'residing_country',
        'native_place',
        'outstation_leave_date_from',
        'outstation_leave_date_to',
        'native_country',
        'native_state',
        'native_district',
        'residency_status',
        'pin_zip_code',
    ];

    protected function casts(): array
    {
        return [
            'outstation_leave_date_from' => 'date',
            'outstation_leave_date_to' => 'date',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
