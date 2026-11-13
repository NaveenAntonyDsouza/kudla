<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One new member's verification call (12 Nov 2026): who it is given to, where it
 * stands, how many tries. The rules are in App\Support\VerificationCalls; every try
 * is also a Call note in the member's history.
 */
class VerificationCall extends Model
{
    /** Still to be called. */
    public const OPEN = ['waiting', 'call_back'];

    /** status => [label, pill colour] */
    public const STATUSES = [
        'waiting' => ['To call', 'warning'],
        'call_back' => ['Call back', 'info'],
        'verified' => ['Verified', 'success'],
        // added by staff from the console: they spoke to the family then, so no call is needed
        'staff_added' => ['Added by staff', 'success'],
        'not_reached' => ['Could not reach', 'gray'],
        'wrong_number' => ['Wrong number', 'danger'],
        'mismatch' => ['Details did not match', 'danger'],
        'not_looking' => ['Not looking any more', 'gray'],
    ];

    protected $fillable = [
        'profile_id',
        'assigned_to',
        'assigned_at',
        'status',
        'attempts',
        'no_answers',
        'last_attempt_at',
        'last_attempt_by',
        'due_at',
        'done_at',
        'done_by',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'due_at' => 'datetime',
            'done_at' => 'datetime',
            'attempts' => 'integer',
            'no_answers' => 'integer',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function label(): string
    {
        return self::STATUSES[$this->status][0] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function color(): string
    {
        return self::STATUSES[$this->status][1] ?? 'gray';
    }
}
