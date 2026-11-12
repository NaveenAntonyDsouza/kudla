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
        // 'email' when the member pressed Accept or Decline in the interest
        // email; null when they answered on the site or in the app
        'answered_via',
        // assisted service (Nov 2026): staff answered on the member's instruction
        'replied_by_admin_id',
        'member_instruction',
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
