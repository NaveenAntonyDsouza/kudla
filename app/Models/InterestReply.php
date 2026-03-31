<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterestReply extends Model
{
    protected $fillable = [
        'interest_id',
        'replier_profile_id',
        'reply_type',
        'template_id',
        'custom_message',
        'is_silent_decline',
    ];

    protected function casts(): array
    {
        return [
            'is_silent_decline' => 'boolean',
        ];
    }

    public function interest(): BelongsTo
    {
        return $this->belongsTo(Interest::class);
    }

    public function replierProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'replier_profile_id');
    }
}
