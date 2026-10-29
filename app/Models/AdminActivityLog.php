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
