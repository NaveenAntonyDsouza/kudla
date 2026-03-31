<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DifferentlyAbledInfo extends Model
{
    protected $table = 'differently_abled_info';

    protected $fillable = [
        'profile_id',
        'category',
        'specify',
        'description',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
