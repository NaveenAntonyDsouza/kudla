<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileNote extends Model
{
    protected $fillable = [
        'profile_id',
        'admin_user_id',
        'note_type',
        'note',
        'follow_up_date',
        'follow_up_completed_at',
        'follow_up_completed_by',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_date' => 'date',
            'follow_up_completed_at' => 'datetime',
        ];
    }

    /**
     * One active follow-up per member.
     *
     * A member's follow-up date lives on individual notes, so without this a
     * member could pile up several pending follow-ups and show multiple times
     * in the Follow-up Report. When a note schedules a follow-up, we supersede
     * (clear the date on) every OTHER still-pending follow-up for the same
     * member — the most recently scheduled one wins. Runs on create regardless
     * of which screen logged the note (Notes popup, Done dialog, future code),
     * so the invariant holds everywhere.
     *
     * Superseded notes keep their text in the interaction log; only their
     * (now-replaced) follow-up date is cleared. We DON'T stamp them completed —
     * they weren't actioned, so they must not appear in "Completed Today".
     *
     * The sweep uses a query-builder update (no model events) so it can't
     * recurse and stays a single query.
     */
    protected static function booted(): void
    {
        static::created(function (ProfileNote $note): void {
            if ($note->follow_up_date === null || $note->follow_up_completed_at !== null) {
                return;
            }

            static::query()
                ->where('profile_id', $note->profile_id)
                ->where('id', '!=', $note->id)
                ->whereNotNull('follow_up_date')
                ->whereNull('follow_up_completed_at')
                ->update(['follow_up_date' => null]);
        });
    }

    /**
     * Notes with a follow-up still outstanding: a date is set AND it hasn't been
     * marked done. The Follow-up Report, its menu badge, and the dashboard
     * "Upcoming Follow-ups" widget all build on this so a completed follow-up
     * drops out of every list at once.
     */
    public function scopePendingFollowUp(Builder $query): Builder
    {
        return $query->whereNotNull('follow_up_date')
            ->whereNull('follow_up_completed_at');
    }

    /** Interaction type → label (for the Notes log + add form). */
    public const NOTE_TYPES = [
        'call' => 'Call',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'meeting' => 'Meeting',
        'walk_in' => 'Walk-in',
        'general' => 'General',
    ];

    /**
     * Interaction type → colour. Uses only the semantic colours that BOTH the
     * <x-admin.pill> component and Filament badges understand
     * (gray|primary|success|warning|danger|info).
     */
    public const NOTE_TYPE_COLORS = [
        'call' => 'info',
        'whatsapp' => 'success',
        'email' => 'warning',
        'meeting' => 'primary',
        'walk_in' => 'gray',
        'general' => 'gray',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /** Staff member who marked this follow-up done (null until completed). */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follow_up_completed_by');
    }
}
