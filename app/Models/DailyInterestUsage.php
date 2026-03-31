<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyInterestUsage extends Model
{
    protected $table = 'daily_interest_usage';

    protected $fillable = [
        'profile_id',
        'usage_date',
        'count',
    ];

    protected function casts(): array
    {
        return [
            'usage_date' => 'date',
            'count' => 'integer',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
