<?php

namespace App\Models;

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
    ];

    protected function casts(): array
    {
        return [
            'follow_up_date' => 'date',
        ];
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
}
