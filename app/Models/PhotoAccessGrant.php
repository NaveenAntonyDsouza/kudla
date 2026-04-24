<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A binary "grantee may see grantor's gated photos" record.
 *
 * Written by App\Services\PhotoAccessService (grant / revoke). Read by
 * PhotoResource::shouldBlurFor and its eventual step-9+ expansion.
 *
 * Unique on (grantor_profile_id, grantee_profile_id) — duplicate grants
 * are impossible at the DB layer, so `updateOrCreate` is safe.
 *
 * Single timestamp (`granted_at`) instead of created_at/updated_at —
 * a grant is immutable: either it exists (user has access) or it's been
 * deleted (access revoked). There's no "updated" state.
 */
class PhotoAccessGrant extends Model
{
    protected $fillable = [
        'grantor_profile_id',
        'grantee_profile_id',
        'granted_at',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
        ];
    }

    /** Disable created_at/updated_at — we only track granted_at. */
    public $timestamps = false;

    /**
     * The profile whose photos are being unlocked.
     */
    public function grantor(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'grantor_profile_id');
    }

    /**
     * The profile that now has access.
     */
    public function grantee(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'grantee_profile_id');
    }
}
