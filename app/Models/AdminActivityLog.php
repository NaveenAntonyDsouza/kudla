<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActivityLog extends Model
{
    protected $table = 'admin_activity_log';

    protected $fillable = [
        'admin_user_id',
        'action',
        'model_type',
        'model_id',
        'changes',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'model_id' => 'integer',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * The member a "Profile" entry is about (deleted ones too), so the log can say
     * "Sita Iyer (KM100050)" instead of "Profile #50". Only meaningful when
     * model_type is Profile: use targetProfile() rather than this directly.
     */
    public function profileTarget(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'model_id')->withTrashed();
    }

    public function targetProfile(): ?Profile
    {
        return $this->model_type === 'Profile' ? $this->profileTarget : null;
    }

    /** "Sita Iyer (KM100050)", or "Lead #12" for other records, or null. */
    public function getTargetLabelAttribute(): ?string
    {
        if (! $this->model_type) {
            return null;
        }
        $p = $this->targetProfile();
        if ($p) {
            return trim(($p->full_name ?: 'Member').' ('.$p->matri_id.')').($p->trashed() ? ' — deleted' : '');
        }

        return $this->model_type.' #'.$this->model_id;
    }

    /**
     * Actions the site takes by itself (scheduled jobs, a member answering an
     * email) have no admin: admin_user_id stays empty and the Activity Log
     * shows "System" (29 Oct 2026; until then they were filed under the main
     * admin account, so the log couldn't tell a person from a job).
     */
    public function getActorNameAttribute(): string
    {
        return $this->admin?->name ?? 'System';
    }
}
