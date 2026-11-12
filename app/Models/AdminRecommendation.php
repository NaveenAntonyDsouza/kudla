<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminRecommendation extends Model
{
    protected $fillable = [
        'for_profile_id',
        'recommended_profile_id',
        'admin_user_id',
        'admin_note',
        'priority',
        'is_viewed',
        'interest_sent',
        // assisted service (Nov 2026): the member's Yes / No, the interest it sent, a meeting
        'member_decision',
        'decided_at',
        'interest_id',
        'meeting_on',
        'meeting_note',
    ];

    protected function casts(): array
    {
        return [
            'is_viewed' => 'boolean',
            'interest_sent' => 'boolean',
            'decided_at' => 'datetime',
            'meeting_on' => 'date',
        ];
    }

    public function interest(): BelongsTo
    {
        return $this->belongsTo(Interest::class);
    }

    /**
     * Where the suggestion stands, in the member's words: waiting for the member,
     * they said no, waiting to be sent, interest sent / accepted / declined, meeting.
     */
    public function stage(): string
    {
        if ($this->meeting_on) {
            return 'meeting';
        }
        if ($this->member_decision === 'no') {
            return 'member_no';
        }
        if ($this->member_decision === null) {
            return 'waiting_member';
        }
        if (! $this->interest_id) {
            return 'to_send';
        }

        return match ($this->interest?->status) {
            'accepted' => 'accepted',
            'declined' => 'declined',
            'cancelled', 'expired' => 'closed',
            default => 'sent',
        };
    }

    public const STAGES = [
        'waiting_member' => 'Waiting for your answer',
        'member_no' => 'You said no',
        'to_send' => 'You said yes: being sent',
        'sent' => 'Interest sent',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
        'closed' => 'Closed',
        'meeting' => 'Meeting arranged',
    ];

    public function forProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'for_profile_id');
    }

    public function recommendedProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'recommended_profile_id');
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
