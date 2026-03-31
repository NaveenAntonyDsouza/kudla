<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactInfo extends Model
{
    protected $table = 'contact_info';

    protected $fillable = [
        'profile_id',
        'contact_person',
        'contact_relationship',
        'primary_phone',
        'secondary_phone',
        'email',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'pincode',
        'whatsapp_number',
        'communication_address',
        'residential_phone_number',
        'preferred_call_time',
        'alternate_email',
        'reference_name',
        'reference_relationship',
        'reference_mobile',
        'present_address_same_as_comm',
        'present_address',
        'present_pin_zip_code',
        'permanent_address_same_as_comm',
        'permanent_address_same_as_present',
        'permanent_address',
        'permanent_pin_zip_code',
    ];

    protected function casts(): array
    {
        return [
            'present_address_same_as_comm' => 'boolean',
            'permanent_address_same_as_comm' => 'boolean',
            'permanent_address_same_as_present' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
