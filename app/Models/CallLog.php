<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLog extends Model
{
    protected $fillable = [
        'lead_id',
        'profile_id',
        'called_by_staff_id',
        'call_type',
        'duration_minutes',
        'outcome',
        'notes',
        'follow_up_required',
        'follow_up_date',
        'called_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'follow_up_required' => 'boolean',
            'follow_up_date' => 'date',
            'called_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function calledByStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'called_by_staff_id');
    }

    // Static lookups

    public static function callTypes(): array
    {
        return [
            'outgoing' => 'Outgoing',
            'incoming' => 'Incoming',
        ];
    }

    public static function outcomes(): array
    {
        return [
            'connected' => ['label' => 'Connected', 'color' => 'success'],
            'no_answer' => ['label' => 'No Answer', 'color' => 'gray'],
            'busy' => ['label' => 'Busy', 'color' => 'warning'],
            'voicemail' => ['label' => 'Voicemail', 'color' => 'info'],
            'interested' => ['label' => 'Interested', 'color' => 'success'],
            'not_interested' => ['label' => 'Not Interested', 'color' => 'danger'],
            'follow_up' => ['label' => 'Follow-up Needed', 'color' => 'warning'],
        ];
    }

    public static function outcomeOptions(): array
    {
        return collect(self::outcomes())->mapWithKeys(fn ($s, $k) => [$k => $s['label']])->toArray();
    }
}
