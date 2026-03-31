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
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
