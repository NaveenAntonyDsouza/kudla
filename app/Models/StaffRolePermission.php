<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffRolePermission extends Model
{
    protected $fillable = [
        'staff_role_id',
        'permission_key',
        'scope',
    ];

    public function staffRole(): BelongsTo
    {
        return $this->belongsTo(StaffRole::class);
    }
}
