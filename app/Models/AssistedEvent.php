<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of an assisted member's history (Nov 2026): consent given or
 * withdrawn, manager set, a match suggested, the member's Yes / No, an interest
 * sent or answered, a meeting. Shown to the member on their Assisted page.
 * admin_user_id is null when the member did it themselves.
 */
class AssistedEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['profile_id', 'admin_user_id', 'action', 'other_profile_id', 'details'];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function otherProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'other_profile_id')->withTrashed();
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
