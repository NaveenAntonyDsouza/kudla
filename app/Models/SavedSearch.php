<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    protected $fillable = [
        'profile_id',
        'search_name',
        'criteria',
    ];

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
